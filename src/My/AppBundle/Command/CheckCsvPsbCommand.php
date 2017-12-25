<?php

namespace My\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCsvPsbCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:check-csv-psb')
            ->addArgument('file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getArgument('file');
        if (!file_exists($filename)) {
            throw new \RuntimeException('File '.$filename.' is not exists');
        }

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $logsRepo = $em->getRepository('PaymentBundle:Log');

        $isFirst   = true;
        $indexRrn  = null;
        $indexSum  = null;

        if (($handle = fopen($filename, 'r')) !== false) {
            $cnt = 0;
            while (($data = fgetcsv($handle)) !== false) {
                if ($isFirst) {
                    foreach ($data as $index => $field) {
                        if ($field == 'RRN') {
                            $indexRrn = $index;
                        }

                        if ($field == 'Сумма операции') {
                            $indexSum = $index;
                        }
                    }

                    if ($indexRrn === null || $indexSum === null) {
                        throw new \RuntimeException('Not found required fields');
                    }

                    $isFirst = false;
                } elseif (!empty($data[$indexRrn]) && !empty($data[$indexSum])) {
                    $rrn  = $data[$indexRrn];
                    $sum  = intval($data[$indexSum]);

                    if ($sum > 0) {
                        $log = $logsRepo->findOneBy([
                            's_id' => $rrn,
                            'sum'  => $sum,
                        ]);

                        if (!$log) {
                            $output->writeln('Not found in DB. RRN: '.$rrn.' SUM: '.$sum);
                        }
                    }
                }

                $cnt ++;
            }

            fclose($handle);
        }

        $paids = $logsRepo->createQueryBuilder('pl')
            ->select('pl.s_id, pl.sum')
            ->andWhere('pl.updated_at <= :date')->setParameter('date', new \DateTime('2017-02-02'))
            ->andWhere('pl.s_type = :api OR pl.s_type = :psb')
            ->setParameter('api', "api")
            ->setParameter('psb', "psb")
            ->andWhere('pl.s_id IS NOT NULL')
            ->andWhere('pl.paid = 1')
            ->getQuery()->getArrayResult();
        foreach ($paids as $paid) {
            if (!$this->isDataInCsv($filename, $indexRrn, $paid['s_id'], $indexSum, $paid['sum'])) {
                $output->writeln('Not found in CSV. RRN: '.$paid['s_id'].' SUM: '.$paid['sum']);
            }
        }

        $output->writeln('Done!');
    }

    protected function isDataInCsv($filename, $indexRrn, $rrn, $indexSum, $sum)
    {
        $isFirst = true;
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                if ($isFirst) {
                    $isFirst = false;
                } elseif ($data[$indexRrn] == $rrn && intval($data[$indexSum]) == $sum) {
                    return true;
                }
            }

            fclose($handle);
        }

        return false;
    }
}
