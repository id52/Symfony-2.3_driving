<?php

namespace My\AppBundle\Model;

/**
 * RegionPlace
 */
abstract class RegionPlace
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
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $users;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $place_prices;

    /**
     * @var \My\AppBundle\Entity\Region
     */
    protected $region;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $categories;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->place_prices = new \Doctrine\Common\Collections\ArrayCollection();
        $this->categories = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return RegionPlace
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
     * Add users
     *
     * @param \My\AppBundle\Entity\User $users
     * @return RegionPlace
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
     * Add place_prices
     *
     * @param \My\AppBundle\Model\RegionPlacePrice $placePrices
     * @return RegionPlace
     */
    public function addPlacePrice(\My\AppBundle\Model\RegionPlacePrice $placePrices)
    {
        $this->place_prices[] = $placePrices;

        return $this;
    }

    /**
     * Remove place_prices
     *
     * @param \My\AppBundle\Model\RegionPlacePrice $placePrices
     */
    public function removePlacePrice(\My\AppBundle\Model\RegionPlacePrice $placePrices)
    {
        $this->place_prices->removeElement($placePrices);
    }

    /**
     * Get place_prices
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPlacePrices()
    {
        return $this->place_prices;
    }

    /**
     * Set region
     *
     * @param \My\AppBundle\Entity\Region $region
     * @return RegionPlace
     */
    public function setRegion(\My\AppBundle\Entity\Region $region = null)
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

    /**
     * Add categories
     *
     * @param \My\AppBundle\Entity\Category $categories
     * @return RegionPlace
     */
    public function addCategory(\My\AppBundle\Entity\Category $categories)
    {
        $this->categories[] = $categories;

        return $this;
    }

    /**
     * Remove categories
     *
     * @param \My\AppBundle\Entity\Category $categories
     */
    public function removeCategory(\My\AppBundle\Entity\Category $categories)
    {
        $this->categories->removeElement($categories);
    }

    /**
     * Get categories
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCategories()
    {
        return $this->categories;
    }
}
