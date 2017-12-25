<?php

namespace My\AppBundle\Model;

/**
 * RegionPlacePrice
 */
abstract class RegionPlacePrice
{
    /**
     * @var boolean
     */
    protected $with_at;

    /**
     * @var boolean
     */
    protected $active;

    /**
     * @var integer
     */
    protected $price;

    /**
     * @var \My\AppBundle\Entity\Category
     */
    protected $category;

    /**
     * @var \My\AppBundle\Entity\RegionPlace
     */
    protected $place;

    /**
     * @var \My\AppBundle\Entity\DrivingConditions
     */
    protected $condition;


    /**
     * Set with_at
     *
     * @param boolean $withAt
     * @return RegionPlacePrice
     */
    public function setWithAt($withAt)
    {
        $this->with_at = $withAt;

        return $this;
    }

    /**
     * Get with_at
     *
     * @return boolean 
     */
    public function getWithAt()
    {
        return $this->with_at;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return RegionPlacePrice
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
     * Set price
     *
     * @param integer $price
     * @return RegionPlacePrice
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
     * Set category
     *
     * @param \My\AppBundle\Entity\Category $category
     * @return RegionPlacePrice
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
     * Set place
     *
     * @param \My\AppBundle\Entity\RegionPlace $place
     * @return RegionPlacePrice
     */
    public function setPlace(\My\AppBundle\Entity\RegionPlace $place)
    {
        $this->place = $place;

        return $this;
    }

    /**
     * Get place
     *
     * @return \My\AppBundle\Entity\RegionPlace 
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Set condition
     *
     * @param \My\AppBundle\Entity\DrivingConditions $condition
     * @return RegionPlacePrice
     */
    public function setCondition(\My\AppBundle\Entity\DrivingConditions $condition)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Get condition
     *
     * @return \My\AppBundle\Entity\DrivingConditions 
     */
    public function getCondition()
    {
        return $this->condition;
    }
}
