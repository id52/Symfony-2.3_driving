<?php

namespace My\AppBundle\Command;

use My\AppBundle\Entity\SecondPaymentPost;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendSecondPaymentPostCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:send-second-payment')
            ->setDescription('Повторная отправка второй оплаты, не дошедшей в АО.'
                .'Первой будет отправлятся самая давняя запись')
            ->addOption('cron', 'c', InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $c = $input->getOption('cron') ? date('Y-m-d H:i:s').' | ' : '';

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var $secondPaymentPost SecondPaymentPost */
        $secondPaymentPost = $em->getRepository('AppBundle:SecondPaymentPost')->createQueryBuilder('spl')
            ->andWhere('spl.arrived_at IS NULL')
            ->orderBy('spl.sended_at')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if ($secondPaymentPost) {
            $this->getContainer()->get('app.second_payment_post')->processPayment($secondPaymentPost);
            $output->writeln($c.'Обработана запись: <info>'.strval($secondPaymentPost->getId()).'</info>');
        } else {
            $output->writeln($c.'Нет платежей в очереди');
        }
    }
}
