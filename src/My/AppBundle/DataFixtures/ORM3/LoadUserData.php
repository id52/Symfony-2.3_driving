<?php

namespace My\AppBundle\DataFixtures\ORM3;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use My\AppBundle\Entity\User;

class LoadUserData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $s = 5000;
        for ($i = $s; $i < $s + 100; $i++) {
            $name = sprintf('test_user_%04d', $i);
            $user = new User();
            $user->setLastName($name);
            $user->setFirstName('');
            $user->setPatronymic('');
            $user->setEmail($name.'@example.com');
            $user->setPlainPassword($name);
            $user->setEnabled(true);
            $user->setPaidNotifiedAt(new \DateTime());
            $user->setPayment1Paid(new \DateTime());
            $user->setPayment2Paid(new \DateTime());
            $manager->persist($user);
            $manager->flush();
            $manager->clear('AppBundle:User');
            echo $name.PHP_EOL;
        }
    }

    public function getOrder()
    {
        return 1;
    }
}
