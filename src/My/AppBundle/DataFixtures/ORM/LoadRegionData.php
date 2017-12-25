<?php

namespace My\AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use My\AppBundle\Entity\Category;
use My\AppBundle\Entity\CategoryPrice;
use My\AppBundle\Entity\Region;

class LoadRegionData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $region1 = new Region();
        $region1->setName('Москва');
        $region1->setFilialNotExisting(false);
        $manager->persist($region1);

        $region2 = new Region();
        $region2->setName('Питер');
        $region2->setFilialNotExisting(false);
        $manager->persist($region2);

        $category1 = new Category();
        $category1->setName('A');
        $manager->persist($category1);

        $category2 = new Category();
        $category2->setName('B');
        $manager->persist($category2);

        $manager->flush();

        $price = new CategoryPrice();
        $price->setActive(true);
        $price->setPrice(1000);
        $price->setRegion($region1);
        $price->setCategory($category1);
        $manager->persist($price);

        $price = new CategoryPrice();
        $price->setActive(true);
        $price->setPrice(2000);
        $price->setRegion($region1);
        $price->setCategory($category2);
        $manager->persist($price);

        $price = new CategoryPrice();
        $price->setActive(true);
        $price->setPrice(1100);
        $price->setRegion($region2);
        $price->setCategory($category1);
        $manager->persist($price);

        $price = new CategoryPrice();
        $price->setActive(false);
        $price->setPrice(2200);
        $price->setRegion($region2);
        $price->setCategory($category2);
        $manager->persist($price);

        $manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }
}
