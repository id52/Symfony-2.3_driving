<?php

namespace My\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Promo
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class Promo extends EntityRepository
{
    public function getBuilderForTriedRezervActiv()
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.keys', 'pk')
            ->addSelect('pk, COUNT(DISTINCT pk.id) as keys_count')
            ->leftJoin('pk.tried_enters', 'te')
            ->addSelect('COUNT(DISTINCT te.user) as tried_enter_count')
            ->leftJoin('pk.logs', 'lt', 'WITH', 'lt.paid = :ltp')->setParameter('ltp', true)
            ->addSelect('COUNT(DISTINCT lt.user) as paid_users_count')
            ->leftJoin('pk.logs', 'lf', 'WITH', 'lf.paid = :lfp')->setParameter('lfp', false)
            ->addSelect('COUNT(DISTINCT lf.user) as no_paid_users_count')
            ->groupBy('p.id');

        return $qb;
    }

    public function isRemovable($id)
    {
        $qb = $this->getBuilderForTriedRezervActiv();
        $qb->andWhere('p.id = :id')->setParameter('id', $id);
        $result = $qb->getQuery()->execute();

        foreach ($result as $value) {
            if ($value['tried_enter_count'] != 0 || $value['paid_users_count'] != 0 ||
                $value['no_paid_users_count'] != 0) {
                return false;
            }
        }
        return true;
    }
}
