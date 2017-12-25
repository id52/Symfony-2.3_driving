<?php

namespace My\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use My\AppBundle\Entity\Theme;
use My\AppBundle\Entity\TrainingVersion;

class QuestionRepository extends EntityRepository
{
    public function getQuestionsIdsArray(Theme $theme, TrainingVersion $version, $without_pdd = false)
    {
        $qb = $this->createQueryBuilder('q')
            ->select('q.id')
            ->andWhere('q.theme = :theme')->setParameter('theme', $theme)
            ->leftJoin('q.versions', 'v')
            ->andWhere('v = :version')->setParameter('version', $version)
        ;
        if ($without_pdd) {
            $qb->andWhere('q.is_pdd = :is_pdd')->setParameter('is_pdd', false);
        }
        $questions = $qb->getQuery()->getArrayResult();
        array_walk($questions, function (&$item) {
            $item = $item['id'];
        });
        return $questions;
    }
}
