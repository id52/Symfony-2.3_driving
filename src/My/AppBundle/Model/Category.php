<?php

namespace My\AppBundle\Model;

/**
 * Category
 */
abstract class Category
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
     * @var \My\AppBundle\Entity\Image
     */
    protected $image;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $place_prices;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $regions_prices;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $users;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $training_versions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $region_places;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->place_prices = new \Doctrine\Common\Collections\ArrayCollection();
        $this->regions_prices = new \Doctrine\Common\Collections\ArrayCollection();
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->training_versions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->region_places = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Category
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
     * Set image
     *
     * @param \My\AppBundle\Entity\Image $image
     * @return Category
     */
    public function setImage(\My\AppBundle\Entity\Image $image = null)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return \My\AppBundle\Entity\Image 
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Add place_prices
     *
     * @param \My\AppBundle\Entity\RegionPlacePrice $placePrices
     * @return Category
     */
    public function addPlacePrice(\My\AppBundle\Entity\RegionPlacePrice $placePrices)
    {
        $this->place_prices[] = $placePrices;

        return $this;
    }

    /**
     * Remove place_prices
     *
     * @param \My\AppBundle\Entity\RegionPlacePrice $placePrices
     */
    public function removePlacePrice(\My\AppBundle\Entity\RegionPlacePrice $placePrices)
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
     * Add regions_prices
     *
     * @param \My\AppBundle\Model\CategoryPrice $regionsPrices
     * @return Category
     */
    public function addRegionsPrice(\My\AppBundle\Model\CategoryPrice $regionsPrices)
    {
        $this->regions_prices[] = $regionsPrices;

        return $this;
    }

    /**
     * Remove regions_prices
     *
     * @param \My\AppBundle\Model\CategoryPrice $regionsPrices
     */
    public function removeRegionsPrice(\My\AppBundle\Model\CategoryPrice $regionsPrices)
    {
        $this->regions_prices->removeElement($regionsPrices);
    }

    /**
     * Get regions_prices
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRegionsPrices()
    {
        return $this->regions_prices;
    }

    /**
     * Add users
     *
     * @param \My\AppBundle\Entity\User $users
     * @return Category
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
     * Add training_versions
     *
     * @param \My\AppBundle\Entity\TrainingVersion $trainingVersions
     * @return Category
     */
    public function addTrainingVersion(\My\AppBundle\Entity\TrainingVersion $trainingVersions)
    {
        $this->training_versions[] = $trainingVersions;

        return $this;
    }

    /**
     * Remove training_versions
     *
     * @param \My\AppBundle\Entity\TrainingVersion $trainingVersions
     */
    public function removeTrainingVersion(\My\AppBundle\Entity\TrainingVersion $trainingVersions)
    {
        $this->training_versions->removeElement($trainingVersions);
    }

    /**
     * Get training_versions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTrainingVersions()
    {
        return $this->training_versions;
    }

    /**
     * Add region_places
     *
     * @param \My\AppBundle\Entity\RegionPlace $regionPlaces
     * @return Category
     */
    public function addRegionPlace(\My\AppBundle\Entity\RegionPlace $regionPlaces)
    {
        $this->region_places[] = $regionPlaces;

        return $this;
    }

    /**
     * Remove region_places
     *
     * @param \My\AppBundle\Entity\RegionPlace $regionPlaces
     */
    public function removeRegionPlace(\My\AppBundle\Entity\RegionPlace $regionPlaces)
    {
        $this->region_places->removeElement($regionPlaces);
    }

    /**
     * Get region_places
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRegionPlaces()
    {
        return $this->region_places;
    }
}
