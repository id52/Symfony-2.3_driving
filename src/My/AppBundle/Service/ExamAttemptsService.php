<?php

namespace My\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use My\AppBundle\Entity\User;

class ExamAttemptsService
{
    protected $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getErrorsCountByUser(User $user)
    {
        $examLog = $this->em->getRepository('AppBundle:ExamLog')->createQueryBuilder('el')
            ->select('count(el.id)')
            ->andWhere('el.user = :user')->setParameter('user', $user)
            ->andWhere('el.passed = :passed')->setParameter('passed', false)
            ->getQuery()->getSingleScalarResult();

        $finalExamLog = $this->em->getRepository('AppBundle:FinalExamLog')->createQueryBuilder('fel')
            ->select('count(fel.id)')
            ->andWhere('fel.user = :user')->setParameter('user', $user)
            ->andWhere('fel.passed = :passed')->setParameter('passed', false)
            ->getQuery()->getSingleScalarResult();

        $errorsCount = $examLog + $finalExamLog;

        return $errorsCount;
    }
}
