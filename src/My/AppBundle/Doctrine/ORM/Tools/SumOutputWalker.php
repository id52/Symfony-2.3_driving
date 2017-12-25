<?php

namespace My\AppBundle\Doctrine\ORM\Tools;

use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\AST\SelectStatement;

class SumOutputWalker extends SqlWalker
{
    /** @var \Doctrine\DBAL\Platforms\AbstractPlatform */
    protected $platform;

    /** @var \Doctrine\ORM\Query\ResultSetMapping */
    protected $rsm;

    /** @var array */
    protected $queryComponents;

    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->platform = $query->getEntityManager()->getConnection()->getDatabasePlatform();
        $this->rsm = $parserResult->getResultSetMapping();
        $this->queryComponents = $queryComponents;

        parent::__construct($query, $parserResult, $queryComponents);
    }

    public function walkSelectStatement(SelectStatement $AST)
    {
        $sql = parent::walkSelectStatement($AST);

        $key = array_search('summator', $this->rsm->scalarMappings);
        if (!$key) {
            throw new \RuntimeException('Not found scalar key "summator"');
        }

        return 'SELECT SUM('.$key.') AS summator FROM ('.$sql.') summator_table';
    }
}
