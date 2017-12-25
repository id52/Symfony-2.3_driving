<?php

namespace My\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use My\AppBundle\Entity\ReservistStat;

class SendHurryCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:hurry-send')
            ->addOption('cron', 'c', InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $notify = $this->getContainer()->get('app.notify');
        $dateTime = new \DateTime();
        $findDateTime = $dateTime->sub(new \DateInterval('PT2H'));

        $triedEnters = $em->getRepository('AppBundle:TriedEnters')->createQueryBuilder('te')
            ->andWhere('te.created <= :find_time')->setParameter('find_time', $findDateTime)
            ->leftJoin('te.user', 'user')
            ->andWhere('user.hurry_is_send = :status')->setParameter('status', false)
            ->getQuery()->execute();

        $sendTriedsFirst = 0;
        $sendTriedsSecond = 0;
        foreach ($triedEnters as $tried) { /** @var $tried \My\AppBundle\Entity\TriedEnters */
            $user = $tried->getUser();
            if ($user->getHurryIsSend()) {
                continue;
            }
            $key = $tried->getPromoKey();
            $roles = $user->getRoles();
            $reservistStat = new ReservistStat();
            $reservistStat->setUser($user);

            if (!in_array('ROLE_USER_PAID', $roles)) {
                $sum = $this->getSiteAccessSum($user);
                $sendTriedsFirst++;
                $reservistStat->setType(0);
            } else {
                $sum = $this->getTrainingAccessSum($user);
                $sendTriedsSecond++;
                $reservistStat->setType(1);
            }

            $em->persist($reservistStat);
            $notify->sendHurryEmail($user, $key, $sum);
            $user->setHurryIsSend(true);
            $em->persist($user);
            $em->flush();
        }

        $paymentlogs = $em->getRepository('PaymentBundle:Log')->createQueryBuilder('pl')
            ->andWhere('pl.promoKey IS NOT NULL')
            ->andWhere('pl.paid = :lfp')->setParameter('lfp', false)
            ->andWhere('pl.created_at <= :find_time')->setParameter('find_time', $findDateTime)
            ->leftJoin('pl.user', 'user')
            ->andWhere('user.hurry_is_send = :status')->setParameter('status', false)
            ->getQuery()->execute();

        $sendReservsFirst = 0;
        $sendReservsSecond = 0;
        foreach ($paymentlogs as $log) { /** @var $log \My\PaymentBundle\Entity\Log */
            $user = $log->getUser();
            if ($user->getHurryIsSend()) {
                continue;
            }
            $key = $log->getPromoKey();
            $roles = $user->getRoles();
            $reservistStat = new ReservistStat();
            $reservistStat->setUser($user);

            if (!in_array('ROLE_USER_PAID', $roles)) {
                $sum = $this->getSiteAccessSum($user);
                $sendReservsFirst++;
                $reservistStat->setType(0);
            } else {
                $sum = $this->getTrainingAccessSum($user);
                $sendReservsSecond++;
                $reservistStat->setType(1);
            }

            $em->persist($reservistStat);
            $notify->sendHurryEmail($user, $key, $sum);
            $user->setHurryIsSend(true);
            $em->persist($user);
            $em->flush();
        }

        if ($sendTriedsFirst || $sendTriedsSecond || $sendReservsFirst || $sendReservsSecond) {
            $c = $input->getOption('cron') ? date('Y-m-d H:i:s').' | ' : '';
            $output->writeln($c.'Отправленно <info>'.($sendTriedsFirst + $sendTriedsSecond).'</info> первичным'
                .' и <info>'.($sendReservsFirst + $sendReservsSecond).'</info> резервистам.');
        }
    }

    protected function getSiteAccessSum($user)
    {
        /** @var  $user \My\AppBundle\Entity\User */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $categories_prices = $em->getRepository('AppBundle:CategoryPrice')->createQueryBuilder('cp')
            ->andWhere('cp.region = :region')->setParameter(':region', $user->getRegion())
            ->andWhere('cp.category = :category')->setParameter(':category', $user->getCategory())
            ->getQuery()->execute();
        $categories_prices_sum = 0;
        foreach ($categories_prices as $price) { /** @var $price \My\AppBundle\Entity\CategoryPrice */
            $categories_prices_sum += $price->getPrice();
        }
        return $categories_prices_sum;
    }

    protected function getTrainingAccessSum($user)
    {
        /** @var  $user \My\AppBundle\Entity\User */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $services = array();
        $services_orig = $em->getRepository('AppBundle:Service')->createQueryBuilder('s')
            ->addSelect('rp.price')
            ->leftJoin('s.regions_prices', 'rp')
            ->andWhere('s.type = :type')->setParameter(':type', 'training')
            ->andWhere('rp.region = :region')->setParameter(':region', $user->getRegion())
            ->getQuery()->getArrayResult();
        foreach ($services_orig as $service) {
            $services[$service[0]['id']] = array_merge($service[0], array('price' => $service['price']));
        }

        $sum = 0;
        foreach ($services as $service) {
            $sum += $service['price'];
        }

        return $sum;
    }
}
