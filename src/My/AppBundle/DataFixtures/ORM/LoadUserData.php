<?php

namespace My\AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use My\AppBundle\Entity\User;

class LoadUserData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var $region \My\AppBundle\Entity\Region; */
        $region = $manager->getRepository('AppBundle:Region')->findOneBy([]);
        /** @var $category \My\AppBundle\Entity\Category; */
        $category = $manager->getRepository('AppBundle:Category')->findOneBy([]);

        $user = new User();
        $user->setLastName('');
        $user->setFirstName('Admin');
        $user->setPatronymic('');
        $user->setEmail('admin@example.com');
        $user->setPlainPassword('admin');
        $user->addRole('ROLE_ADMIN');
        $user->setEnabled(true);
        $user->setPaidNotifiedAt(new \DateTime());
        $user->setPayment1Paid(new \DateTime());
        $user->setPayment2Paid(new \DateTime());
        $user->setRegion($region);
        $user->setCategory($category);
        $manager->persist($user);

        $user = new User();
        $user->setLastName('');
        $user->setFirstName('User00');
        $user->setPatronymic('');
        $user->setEmail('user00@user00.ru');
        $user->setPlainPassword('user00');
        $user->addRole('ROLE_USER');
        $user->setEnabled(true);
        $user->setPaidNotifiedAt(new \DateTime());
        $user->setPayment1Paid(new \DateTime());
        $user->setPayment2Paid(new \DateTime());
        $user->setRegion($region);
        $user->setCategory($category);
        $manager->persist($user);

        $manager->flush();
    }



    public function getOrder()
    {
        return 2;
    }
}
