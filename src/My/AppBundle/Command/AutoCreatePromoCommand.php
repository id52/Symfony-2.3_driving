<?php

namespace My\AppBundle\Command;

use My\AppBundle\Entity\SettingAutoCreatePromo;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use My\AppBundle\Entity\Promo;
use My\AppBundle\Entity\PromoKey;

class AutoCreatePromoCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:auto-create-promo')
            ->setDescription('Автоматическое создание промокомпании 1 ключ на 1000 человек, время действия - 2 недели')
            ->addOption('cron', 'c', InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $c = $input->getOption('cron') ? date('Y-m-d H:i:s').' | ' : '';

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $promoService = $this->getContainer()->get('app.promo');

        if (date('j') == 1 || date('j') == 16) {
            $year = date('Y');
            $month = date('n');
            $day = date('j');
            if ($day == 1) {
                $day = 16;
            } else {
                $month += 1;
                $day = 1;
            }
            $now = new \DateTime();
            $endDate = new \DateTime(sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $day));

            $promo = new Promo();
            $promo->setName('Автоматическая промокампания от '.$now->format('Y-m-d'));
            $promo->setUsedFrom($now);
            $promo->setUsedTo($endDate);
            $promo->setRestricted('users');
            $promo->setMaxUsers(1000);
            $promo->setActive(true);
            $promo->setAutoCreate(true);
            $em->persist($promo);
            $em->flush();

            $hash = $promoService->generatePromoKeyHashes(1);
            $promoKey = new PromoKey();
            $promoKey->setActive(true);
            $promoKey->setDiscount(100000);
            $promoKey->setHash($hash['h0']);
            $promoKey->setPromo($promo);
            $promoKey->setType('site_access');
            $em->persist($promoKey);
            $em->flush();

            $setingsDate = $em->getRepository('AppBundle:SettingAutoCreatePromo')->findOneBy(array('_key'=>'promoId'));
            if (!$setingsDate) {
                $setingsDate = new SettingAutoCreatePromo();
                $setingsDate->setKey('promoId');
                $setingsDate->setType('string');
                $setingsDate->setValue($promo->getId());
            } else {
                $setingsDate->setValue($promo->getId());
            }
            $em->persist($setingsDate);
            $em->flush();

            $output->writeln($c.'Создана промокомпания для вывода промоключа в лэндинге - "'
                .'Автоматическая промокомпания от '.$now->format('Y-m-d').'"');
        } else {
            $output->writeln($c.'Не удалось создать промокомпанию'
                .', создание возможно только 1 и 16 числа каждого месяца');
        }
    }
}
