<?php

namespace My\AppBundle\Model;

/**
 * CategoryPrice
 */
abstract class CategoryPrice
{
    /**
     * @var integer
     */
    protected $price;

    /**
     * @var boolean
     */
    protected $active;

    /**
     * @var \My\AppBundle\Entity\Category
     */
    protected $category;

    /**
     * @var \My\AppBundle\Entity\Region
     */
    protected $region;


    /**
     * Set price
     *
     * @param integer $price
     * @return CategoryPrice
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
     * Set active
     *
     * @param boolean $active
     * @return CategoryPrice
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
     * Set category
     *
     * @param \My\AppBundle\Entity\Category $category
     * @return CategoryPrice
     */
    public function setCategory(\My\AppBundle\Entity\Category $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \My\AppBundle\Entity\Category 
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set region
     *
     * @param \My\AppBundle\Entity\Region $region
     * @return CategoryPrice
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
