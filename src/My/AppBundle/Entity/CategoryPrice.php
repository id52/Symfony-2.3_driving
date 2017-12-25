<?php

namespace My\AppBundle\Entity;

use My\AppBundle\Model\CategoryPrice as CategoryPriceModel;

class CategoryPrice extends CategoryPriceModel
{
    protected $price = 0;

    public function setPrice($price)
    {
        $this->price = max(intval($price), 0);
    }
}
