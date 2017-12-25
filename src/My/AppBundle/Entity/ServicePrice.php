<?php

namespace My\AppBundle\Entity;

use My\AppBundle\Model\ServicePrice as ServicePriceModel;

class ServicePrice extends ServicePriceModel
{
    protected $price = 0;
    protected $price_comb = 0;
    protected $price_expr = 0;

    public function setPrice($price)
    {
        $this->price = max(intval($price), 0);
    }

    public function setPriceComb($price)
    {
        $this->price_comb = max(intval($price), 0);
    }

    public function setPriceExpr($price)
    {
        $this->price_expr = max(intval($price), 0);
    }

    public function getPriceForApi($comb = false, $expr = false)
    {
        $price = $this->price;
        $price += $comb ? $this->price_comb : 0;
        $price += $expr ? $this->price_expr : 0;
        return $price;
    }
}
