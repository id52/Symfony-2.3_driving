<?php

namespace My\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class RegionRepository extends EntityRepository
{
    public function getRegionCategories()
    {
        $region_categories = array();
        $region_categories_source = $this->createQueryBuilder('r')
            ->leftJoin('r.categories_prices', 'cp')
            ->leftJoin('cp.category', 'c')
            ->getQuery()->getResult();
        foreach ($region_categories_source as $region) {
            /** @var $region \My\AppBundle\Entity\Region */

            if (!isset($region_categories[$region->getId()])) {
                $region_categories[$region->getId()] = array();
            }

            foreach ($region->getCategoriesPrices() as $cp) {
                /** @var $cp \My\AppBundle\Entity\CategoryPrice */

                if ($cp->getActive()) {
                    $category = $cp->getCategory();
                    $region_categories[$region->getId()][$category->getId()] = $category->getName();
                }
            }
        }

        return $region_categories;
    }
}
