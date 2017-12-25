<?php

namespace My\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendUnpaidDrivingCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:send-unpaid-driving')
            ->setDescription('')
            ->addOption('cron', 'c', InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $c = $input->getOption('cron') ? date('Y-m-d H:i:s').' | ' : '';

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var $settings \My\AppBundle\Repository\SettingRepository */
        $settings     = $em->getRepository('AppBundle:Setting');
        $emailEnabled = $settings->get('unpaid_driving_email_enabled');
        $smsEnabled   = $settings->get('unpaid_driving_sms_enabled');

        if ($emailEnabled || $smsEnabled) {
            $now     = new \DateTime();
            $daysAgo = $now->sub(new \DateInterval('P45D'));

            $unpaidDrivingUsers = $em->getRepository('AppBundle:User')->createQueryBuilder('u')
                ->andWhere('u.payment_2_paid = :daysAgo')->setParameter(':daysAgo', $daysAgo->format('Y-m-d'))
                ->getQuery()->getResult();

            if ($unpaidDrivingUsers) {
                $notify       = $this->getContainer()->get('app.notify');
                $notifiesSent = 0;

                foreach ($unpaidDrivingUsers as $user) {
                    $notify->sendUnpaidDriving($user);
                    $notifiesSent++;
                }

                $output->writeln($c.'Отправлено уведомлений: <info>'.$notifiesSent.'</info>');
            } else {
                $output->writeln($c.'Пользователи не найдены!');
            }
        } else {
            $output->writeln($c.'Отправка уведомлений отключена!');
        }
    }
}
