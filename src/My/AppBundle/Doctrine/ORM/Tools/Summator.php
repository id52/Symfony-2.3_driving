<?php

namespace My\AppBundle\Doctrine\ORM\Tools;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;

class Summator
{
    /** @var Query */
    protected $query;

    protected $sum;

    /**
     * @param $query Query|QueryBuilder
     */
    public function __construct($query)
    {
        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
        }

        $this->query = $query;
    }

    public function getSum()
    {
        if ($this->sum === null) {
            $query = clone $this->query;
            $query->setParameters(clone $this->query->getParameters());

            $platform = $query->getEntityManager()->getConnection()->getDatabasePlatform();

            $rsm = new ResultSetMapping();
            $rsm->addScalarResult($platform->getSQLResultCasing('summator'), 'sum');
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'My\AppBundle\Doctrine\ORM\Tools\SumOutputWalker');
            $query->setResultSetMapping($rsm);

            $this->sum = $query->getSingleScalarResult();
        }

        return $this->sum;
    }
}
