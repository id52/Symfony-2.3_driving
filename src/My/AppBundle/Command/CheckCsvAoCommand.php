<?php

namespace My\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCsvAoCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:check-csv-ao')
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

        $isFirst = true;

        if (($handle = fopen($filename, 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                if ($isFirst) {
                    $isFirst = false;
                } elseif (isset($data[2]) && $data[2] && isset($data[3]) && $data[3]) {
                    /** @var $log \My\PaymentBundle\Entity\Log */
                    $log = $logsRepo->findOneBy([
                        'user'   => $data[0],
                        's_type' => 'api',
                    ]);

                    if ($log) {
                        $log->setSId($data[2]);

                        $date = new \DateTime($data[3]);
                        if ($date) {
                            $log->setUpdatedAt($date);
                        }

                        $em->persist($log);
                        $em->flush();
                    } else {
                        $output->writeln('Not found DOID: '.$data[0]);
                    }
                }
            }

            fclose($handle);
        }

        $output->writeln('Done!');
    }
}
