<?php

namespace My\AppBundle\Model;

/**
 * Region
 */
abstract class Region
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var integer
     */
    protected $discount_1_amount;

    /**
     * @var \DateTime
     */
    protected $discount_1_date_from;

    /**
     * @var \DateTime
     */
    protected $discount_1_date_to;

    /**
     * @var integer
     */
    protected $discount_1_timer_period;

    /**
     * @var integer
     */
    protected $discount_2_first_amount;

    /**
     * @var integer
     */
    protected $discount_2_first_days;

    /**
     * @var integer
     */
    protected $discount_2_second_amount;

    /**
     * @var integer
     */
    protected $discount_2_second_days;

    /**
     * @var integer
     */
    protected $discount_2_between_period_days;

    /**
     * @var boolean
     */
    protected $filial_not_existing;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $services_prices;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $categories_prices;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $users;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $places;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $managers;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->services_prices = new \Doctrine\Common\Collections\ArrayCollection();
        $this->categories_prices = new \Doctrine\Common\Collections\ArrayCollection();
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->places = new \Doctrine\Common\Collections\ArrayCollection();
        $this->managers = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set name
     *
     * @param string $name
     * @return Region
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
     * Set discount_1_amount
     *
     * @param integer $discount1Amount
     * @return Region
     */
    public function setDiscount1Amount($discount1Amount)
    {
        $this->discount_1_amount = $discount1Amount;

        return $this;
    }

    /**
     * Get discount_1_amount
     *
     * @return integer 
     */
    public function getDiscount1Amount()
    {
        return $this->discount_1_amount;
    }

    /**
     * Set discount_1_date_from
     *
     * @param \DateTime $discount1DateFrom
     * @return Region
     */
    public function setDiscount1DateFrom($discount1DateFrom)
    {
        $this->discount_1_date_from = $discount1DateFrom;

        return $this;
    }

    /**
     * Get discount_1_date_from
     *
     * @return \DateTime 
     */
    public function getDiscount1DateFrom()
    {
        return $this->discount_1_date_from;
    }

    /**
     * Set discount_1_date_to
     *
     * @param \DateTime $discount1DateTo
     * @return Region
     */
    public function setDiscount1DateTo($discount1DateTo)
    {
        $this->discount_1_date_to = $discount1DateTo;

        return $this;
    }

    /**
     * Get discount_1_date_to
     *
     * @return \DateTime 
     */
    public function getDiscount1DateTo()
    {
        return $this->discount_1_date_to;
    }

    /**
     * Set discount_1_timer_period
     *
     * @param integer $discount1TimerPeriod
     * @return Region
     */
    public function setDiscount1TimerPeriod($discount1TimerPeriod)
    {
        $this->discount_1_timer_period = $discount1TimerPeriod;

        return $this;
    }

    /**
     * Get discount_1_timer_period
     *
     * @return integer 
     */
    public function getDiscount1TimerPeriod()
    {
        return $this->discount_1_timer_period;
    }

    /**
     * Set discount_2_first_amount
     *
     * @param integer $discount2FirstAmount
     * @return Region
     */
    public function setDiscount2FirstAmount($discount2FirstAmount)
    {
        $this->discount_2_first_amount = $discount2FirstAmount;

        return $this;
    }

    /**
     * Get discount_2_first_amount
     *
     * @return integer 
     */
    public function getDiscount2FirstAmount()
    {
        return $this->discount_2_first_amount;
    }

    /**
     * Set discount_2_first_days
     *
     * @param integer $discount2FirstDays
     * @return Region
     */
    public function setDiscount2FirstDays($discount2FirstDays)
    {
        $this->discount_2_first_days = $discount2FirstDays;

        return $this;
    }

    /**
     * Get discount_2_first_days
     *
     * @return integer 
     */
    public function getDiscount2FirstDays()
    {
        return $this->discount_2_first_days;
    }

    /**
     * Set discount_2_second_amount
     *
     * @param integer $discount2SecondAmount
     * @return Region
     */
    public function setDiscount2SecondAmount($discount2SecondAmount)
    {
        $this->discount_2_second_amount = $discount2SecondAmount;

        return $this;
    }

    /**
     * Get discount_2_second_amount
     *
     * @return integer 
     */
    public function getDiscount2SecondAmount()
    {
        return $this->discount_2_second_amount;
    }

    /**
     * Set discount_2_second_days
     *
     * @param integer $discount2SecondDays
     * @return Region
     */
    public function setDiscount2SecondDays($discount2SecondDays)
    {
        $this->discount_2_second_days = $discount2SecondDays;

        return $this;
    }

    /**
     * Get discount_2_second_days
     *
     * @return integer 
     */
    public function getDiscount2SecondDays()
    {
        return $this->discount_2_second_days;
    }

    /**
     * Set discount_2_between_period_days
     *
     * @param integer $discount2BetweenPeriodDays
     * @return Region
     */
    public function setDiscount2BetweenPeriodDays($discount2BetweenPeriodDays)
    {
        $this->discount_2_between_period_days = $discount2BetweenPeriodDays;

        return $this;
    }

    /**
     * Get discount_2_between_period_days
     *
     * @return integer 
     */
    public function getDiscount2BetweenPeriodDays()
    {
        return $this->discount_2_between_period_days;
    }

    /**
     * Set filial_not_existing
     *
     * @param boolean $filialNotExisting
     * @return Region
     */
    public function setFilialNotExisting($filialNotExisting)
    {
        $this->filial_not_existing = $filialNotExisting;

        return $this;
    }

    /**
     * Get filial_not_existing
     *
     * @return boolean 
     */
    public function getFilialNotExisting()
    {
        return $this->filial_not_existing;
    }

    /**
     * Add services_prices
     *
     * @param \My\AppBundle\Entity\ServicePrice $servicesPrices
     * @return Region
     */
    public function addServicesPrice(\My\AppBundle\Entity\ServicePrice $servicesPrices)
    {
        $this->services_prices[] = $servicesPrices;

        return $this;
    }

    /**
     * Remove services_prices
     *
     * @param \My\AppBundle\Entity\ServicePrice $servicesPrices
     */
    public function removeServicesPrice(\My\AppBundle\Entity\ServicePrice $servicesPrices)
    {
        $this->services_prices->removeElement($servicesPrices);
    }

    /**
     * Get services_prices
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServicesPrices()
    {
        return $this->services_prices;
    }

    /**
     * Add categories_prices
     *
     * @param \My\AppBundle\Entity\CategoryPrice $categoriesPrices
     * @return Region
     */
    public function addCategoriesPrice(\My\AppBundle\Entity\CategoryPrice $categoriesPrices)
    {
        $this->categories_prices[] = $categoriesPrices;

        return $this;
    }

    /**
     * Remove categories_prices
     *
     * @param \My\AppBundle\Entity\CategoryPrice $categoriesPrices
     */
    public function removeCategoriesPrice(\My\AppBundle\Entity\CategoryPrice $categoriesPrices)
    {
        $this->categories_prices->removeElement($categoriesPrices);
    }

    /**
     * Get categories_prices
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCategoriesPrices()
    {
        return $this->categories_prices;
    }

    /**
     * Add users
     *
     * @param \My\AppBundle\Entity\User $users
     * @return Region
     */
    public function addUser(\My\AppBundle\Entity\User $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users
     *
     * @param \My\AppBundle\Entity\User $users
     */
    public function removeUser(\My\AppBundle\Entity\User $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Add places
     *
     * @param \My\AppBundle\Model\RegionPlace $places
     * @return Region
     */
    public function addPlace(\My\AppBundle\Model\RegionPlace $places)
    {
        $this->places[] = $places;

        return $this;
    }

    /**
     * Remove places
     *
     * @param \My\AppBundle\Model\RegionPlace $places
     */
    public function removePlace(\My\AppBundle\Model\RegionPlace $places)
    {
        $this->places->removeElement($places);
    }

    /**
     * Get places
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPlaces()
    {
        return $this->places;
    }

    /**
     * Add managers
     *
     * @param \My\AppBundle\Entity\User $managers
     * @return Region
     */
    public function addManager(\My\AppBundle\Entity\User $managers)
    {
        $this->managers[] = $managers;

        return $this;
    }

    /**
     * Remove managers
     *
     * @param \My\AppBundle\Entity\User $managers
     */
    public function removeManager(\My\AppBundle\Entity\User $managers)
    {
        $this->managers->removeElement($managers);
    }

    /**
     * Get managers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getManagers()
    {
        return $this->managers;
    }
}
