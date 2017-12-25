<?php

/**
 * Added in crontab
 * 0 5 * * 1
 */

namespace My\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClearImagesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:clear-images')
            ->addOption('cron', 'c', InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $cnt = 0;
        $images = $em->getRepository('AppBundle:Image')->createQueryBuilder('i')
            ->andWhere('i.question IS NULL')
            ->andWhere('i.category IS NULL')
            ->andWhere('i.subject IS NULL')
            ->andWhere('i.flash_block_item IS NULL')
            ->getQuery()->execute();
        foreach ($images as $image) {
            $em->remove($image);
            $em->flush();

            $cnt++;
        }

        if ($cnt) {
            $c = $input->getOption('cron') ? date('Y-m-d H:i:s').' | ' : '';
            $output->writeln($c.'Removed <info>'.$cnt.'</info> images.');
        }

        $cnt = 0;
        $docs = $em->getRepository('AppBundle:Document')->createQueryBuilder('d')
            ->andWhere('d.user IS NULL')
            ->getQuery()->execute();
        foreach ($docs as $doc) {
            $em->remove($doc);
            $em->flush();

            $cnt++;
        }

        if ($cnt) {
            $c = $input->getOption('cron') ? date('Y-m-d H:i:s').' | ' : '';
            $output->writeln($c.'Removed <info>'.$cnt.'</info> documents.');
        }
    }
}
