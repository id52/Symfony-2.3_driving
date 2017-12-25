<?php

/**
 * Added in crontab
 * 0 2 * * *
 */

namespace My\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendNotifiesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:send-notifies')
            ->addOption('cron', 'c', InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $router_cntxt = $this->getContainer()->get('router')->getContext();
        $host = $this->getContainer()->getParameter('host');
        $router_cntxt->setHost($host);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $notify = $this->getContainer()->get('app.notify');

        $settings_repository = $em->getRepository('AppBundle:Setting');
        $settings = $settings_repository->getAllData();

        $now = new \DateTime();

        $limit = clone $now;
        $limit->sub(new \DateInterval('P'.$settings['access_time_after_1_payment'].'D'));

        $qb = $em->getRepository('AppBundle:User')->createQueryBuilder('u')
            ->andWhere('u.payment_1_paid IS NULL')
            ->andWhere('u.payment_2_paid IS NULL')
        ;
        $where = '';
        for ($i = 1; $i <= 5; $i ++) {
            $date = clone $now;
            $date->sub(new \DateInterval('P'.$settings['notify_no_payments_'.$i].'D'));
            $qb->setParameter(':date'.$i, $date->format('Y-m-d'));
            $where .= 'DATE(u.created_at) = :date'.$i.' OR ';
        }
        $qb->andWhere(substr($where, 0, -4));
        // only users who didn't unsubscribed
        $qb->andWhere('u.overdue_unsubscribed = :overdue_unsubscribed')->setParameter(':overdue_unsubscribed', 0);

        $cnt = 0;
        $users = $qb->getQuery()->execute();
        foreach ($users as $user) {
            $notify->sendNoPayments($user, $this->getContainer()->get('app.promo'));
            $cnt ++;
        }
        if ($cnt) {
            $c = $input->getOption('cron') ? date('Y-m-d H:i:s').' | ' : '';
            $output->writeln($c.'Sended <info>'.$cnt.'</info> notifies for who haven\'t pay.');
        }

        $qb = $em->getRepository('AppBundle:User')->createQueryBuilder('u');
        $qb->andWhere('u.payment_1_paid IS NOT NULL');
        $qb->andWhere('u.payment_2_paid IS NULL');
        $where = '';
        for ($i = 1; $i <= 16; $i ++) {
            $date = clone $limit;
            $date->add(new \DateInterval('P'.$settings['access_time_end_notify_after_1_payment_'.$i].'D'));
            $qb->setParameter(':date'.$i, $date->format('Y-m-d'));
            $where .= 'u.payment_1_paid = :date'.$i.' OR ';
        }
        $qb->andWhere(substr($where, 0, -4));
        $qb->andWhere('u.payment_1_paid != :limit')->setParameter(':limit', $limit->format('Y-m-d'));

        $cnt = 0;
        $users = $qb->getQuery()->execute();
        foreach ($users as $user) {
            $notify->sendBeforeAccessTimeEndAfter1Payment($user);
            $cnt ++;
        }
        if ($cnt) {
            $c = $input->getOption('cron') ? date('Y-m-d H:i:s').' | ' : '';
            $output->writeln($c.'Sended <info>'.$cnt.'</info> notifies before end of access time after 1 payment.');
        }

        $qb = $em->getRepository('AppBundle:User')->createQueryBuilder('u');
        $qb->andWhere('u.payment_1_paid_not_notify = 0');
        $qb->andWhere('u.payment_1_paid IS NOT NULL');
        $qb->andWhere('u.payment_2_paid IS NULL');
        $qb->andWhere('u.payment_1_paid <= :limit')->setParameter(':limit', $limit->format('Y-m-d'));

        $cnt = 0;
        $users = $qb->getQuery()->execute();
        foreach ($users as $user) {
            /** @var $user \My\AppBundle\Entity\User */

            $diff = $user->getPayment1Paid()->diff($limit)->days;
            if ($diff % 7 == 0) {
                $notify->sendAfterAccessTimeEndAfter1Payment($user);
                $cnt ++;
            }
        }
        if ($cnt) {
            $c = $input->getOption('cron') ? date('Y-m-d H:i:s').' | ' : '';
            $output->writeln($c.'Sended <info>'.$cnt.'</info> notifies after end of access time after 1 payment.');
        }

        $limit = clone $now;
        $limit->sub(new \DateInterval('P'.$settings['access_time_after_2_payment'].'D'));

        $qb = $em->getRepository('AppBundle:User')->createQueryBuilder('u');
        $qb->andWhere('u.payment_2_paid IS NOT NULL');
        $where = '';
        for ($i = 1; $i <= 16; $i ++) {
            $date = clone $limit;
            $date->add(new \DateInterval('P'.$settings['access_time_end_notify_after_2_payment_'.$i].'D'));
            $qb->setParameter(':date'.$i, $date->format('Y-m-d'));
            $where .= 'u.payment_1_paid = :date'.$i.' OR ';
        }
        $qb->andWhere(substr($where, 0, -4));
        $qb->andWhere('u.payment_2_paid != :limit')->setParameter(':limit', $limit->format('Y-m-d'));

        $cnt = 0;
        $users = $qb->getQuery()->execute();
        foreach ($users as $user) {
            $notify->sendBeforeAccessTimeEndAfter2Payment($user);
            $cnt ++;
        }
        if ($cnt) {
            $c = $input->getOption('cron') ? date('Y-m-d H:i:s').' | ' : '';
            $output->writeln($c.'Sended <info>'.$cnt.'</info> notifies before end of access time after 2 payment.');
        }

        $qb = $em->getRepository('AppBundle:User')->createQueryBuilder('u');
        $qb->andWhere('u.payment_2_paid_not_notify = 0');
        $qb->andWhere('u.payment_2_paid IS NOT NULL');
        $qb->andWhere('u.payment_2_paid <= :limit')->setParameter(':limit', $limit->format('Y-m-d'));

        $cnt = 0;
        $users = $qb->getQuery()->execute();
        foreach ($users as $user) {
            /** @var $user \My\AppBundle\Entity\User */

            $diff = $user->getPayment2Paid()->diff($limit)->days;
            if ($diff % 7 == 0) {
                $notify->sendAfterAccessTimeEndAfter2Payment($user);
                $cnt ++;
            }
        }
        if ($cnt) {
            $c = $input->getOption('cron') ? date('Y-m-d H:i:s').' | ' : '';
            $output->writeln($c.'Sended <info>'.$cnt.'</info> notifies after end of access time after 2 payment.');
        }

        $now = new \DateTime();
        $qb = $em->getRepository('AppBundle:User')->createQueryBuilder('u');
        $qb->andWhere('u.not_paid_primary_boosting_is_send = 0');
        $qb->andWhere('u.payment_2_paid IS NOT NULL');
        $qb->andWhere('u.owe_stage_end IS NOT NULL AND u.owe_stage_end < :now')->setParameter('now', $now);
        $users = $qb->getQuery()->execute();

        $cnt = 0;
        foreach ($users as $user) { /** @var $user \My\AppBundle\Entity\User */
            $user->setNotPaidPrimaryBoostingIsSend(true);
            $em->persist($user);
            $em->flush();

            $notify->sendUserNotPaidPrimaryBoostingNotPaidDriving($user);
            $cnt ++;
        }

        if ($cnt) {
            $c = $input->getOption('cron') ? date('Y-m-d H:i:s').' | ' : '';
            $output->writeln($c.'Sended <info>'.$cnt.'</info> notifies for user not paid primary boosting'
                .'not paid driving.');
        }
    }
}
