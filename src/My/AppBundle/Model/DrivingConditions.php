<?php

namespace My\AppBundle\Model;

/**
 * DrivingConditions
 */
abstract class DrivingConditions
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var boolean
     */
    protected $active;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $cond_code;

    /**
     * @var boolean
     */
    protected $with_at;

    /**
     * @var boolean
     */
    protected $is_primary;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var integer
     */
    protected $number_tickets;

    /**
     * @var integer
     */
    protected $position;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $region_prices;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $packages;

    /**
     * @var \My\AppBundle\Entity\ClassService
     */
    protected $class_service;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->region_prices = new \Doctrine\Common\Collections\ArrayCollection();
        $this->packages = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return DrivingConditions
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
     * Set name
     *
     * @param string $name
     * @return DrivingConditions
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set cond_code
     *
     * @param string $condCode
     * @return DrivingConditions
     */
    public function setCondCode($condCode)
    {
        $this->cond_code = $condCode;

        return $this;
    }

    /**
     * Get cond_code
     *
     * @return string 
     */
    public function getCondCode()
    {
        return $this->cond_code;
    }

    /**
     * Set with_at
     *
     * @param boolean $withAt
     * @return DrivingConditions
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
     * Set is_primary
     *
     * @param boolean $isPrimary
     * @return DrivingConditions
     */
    public function setIsPrimary($isPrimary)
    {
        $this->is_primary = $isPrimary;

        return $this;
    }

    /**
     * Get is_primary
     *
     * @return boolean 
     */
    public function getIsPrimary()
    {
        return $this->is_primary;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return DrivingConditions
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set number_tickets
     *
     * @param integer $numberTickets
     * @return DrivingConditions
     */
    public function setNumberTickets($numberTickets)
    {
        $this->number_tickets = $numberTickets;

        return $this;
    }

    /**
     * Get number_tickets
     *
     * @return integer 
     */
    public function getNumberTickets()
    {
        return $this->number_tickets;
    }

    /**
     * Set position
     *
     * @param integer $position
     * @return DrivingConditions
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer 
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Add region_prices
     *
     * @param \My\AppBundle\Entity\RegionPlacePrice $regionPrices
     * @return DrivingConditions
     */
    public function addRegionPrice(\My\AppBundle\Entity\RegionPlacePrice $regionPrices)
    {
        $this->region_prices[] = $regionPrices;

        return $this;
    }

    /**
     * Remove region_prices
     *
     * @param \My\AppBundle\Entity\RegionPlacePrice $regionPrices
     */
    public function removeRegionPrice(\My\AppBundle\Entity\RegionPlacePrice $regionPrices)
    {
        $this->region_prices->removeElement($regionPrices);
    }

    /**
     * Get region_prices
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRegionPrices()
    {
        return $this->region_prices;
    }

    /**
     * Add packages
     *
     * @param \My\AppBundle\Entity\DrivingPackage $packages
     * @return DrivingConditions
     */
    public function addPackage(\My\AppBundle\Entity\DrivingPackage $packages)
    {
        $this->packages[] = $packages;

        return $this;
    }

    /**
     * Remove packages
     *
     * @param \My\AppBundle\Entity\DrivingPackage $packages
     */
    public function removePackage(\My\AppBundle\Entity\DrivingPackage $packages)
    {
        $this->packages->removeElement($packages);
    }

    /**
     * Get packages
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * Set class_service
     *
     * @param \My\AppBundle\Entity\ClassService $classService
     * @return DrivingConditions
     */
    public function setClassService(\My\AppBundle\Entity\ClassService $classService = null)
    {
        $this->class_service = $classService;

        return $this;
    }

    /**
     * Get class_service
     *
     * @return \My\AppBundle\Entity\ClassService 
     */
    public function getClassService()
    {
        return $this->class_service;
    }
}
