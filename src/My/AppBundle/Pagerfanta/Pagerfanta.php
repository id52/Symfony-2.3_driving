<?php

namespace My\AppBundle\Pagerfanta;

use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Pagerfanta as Base;

class Pagerfanta extends Base
{
    public function __construct(AdapterInterface $adapter)
    {
        parent::__construct($adapter);
        $this->setMaxPerPage(20);
        $this->setNormalizeOutOfRangePages(true);
    }

    public function setCurrentPage($currentPage)
    {
        parent::setCurrentPage(max(intval($currentPage), 1));
        return $this;
    }
}