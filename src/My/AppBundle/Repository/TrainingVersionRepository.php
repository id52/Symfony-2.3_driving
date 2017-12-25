<?php

namespace My\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use My\AppBundle\Entity\User;

class TrainingVersionRepository extends EntityRepository
{
    public function getVersionByUser(User $user)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.category = :category')->setParameter(':category', $user->getCategory())
            ->andWhere('v.start_date <= :start_date')
            ->setParameter(':start_date', date_format($user->getCreatedAt(), 'Y-m-d'))
            ->addOrderBy('v.start_date', 'DESC')
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }
}
