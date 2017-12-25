<?php

namespace My\AppBundle\Model;

/**
 * ServicePrice
 */
abstract class ServicePrice
{
    /**
     * @var integer
     */
    protected $price;

    /**
     * @var integer
     */
    protected $price_comb;

    /**
     * @var integer
     */
    protected $price_expr;

    /**
     * @var boolean
     */
    protected $active;

    /**
     * @var \My\AppBundle\Entity\Service
     */
    protected $service;

    /**
     * @var \My\AppBundle\Entity\Region
     */
    protected $region;


    /**
     * Set price
     *
     * @param integer $price
     * @return ServicePrice
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return integer 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set price_comb
     *
     * @param integer $priceComb
     * @return ServicePrice
     */
    public function setPriceComb($priceComb)
    {
        $this->price_comb = $priceComb;

        return $this;
    }

    /**
     * Get price_comb
     *
     * @return integer 
     */
    public function getPriceComb()
    {
        return $this->price_comb;
    }

    /**
     * Set price_expr
     *
     * @param integer $priceExpr
     * @return ServicePrice
     */
    public function setPriceExpr($priceExpr)
    {
        $this->price_expr = $priceExpr;

        return $this;
    }

    /**
     * Get price_expr
     *
     * @return integer 
     */
    public function getPriceExpr()
    {
        return $this->price_expr;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return ServicePrice
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set service
     *
     * @param \My\AppBundle\Entity\Service $service
     * @return ServicePrice
     */
    public function setService(\My\AppBundle\Entity\Service $service)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get service
     *
     * @return \My\AppBundle\Entity\Service 
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set region
     *
     * @param \My\AppBundle\Entity\Region $region
     * @return ServicePrice
     */
    public function setRegion(\My\AppBundle\Entity\Region $region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get region
     *
     * @return \My\AppBundle\Entity\Region 
     */
    public function getRegion()
    {
        return $this->region;
    }
}
