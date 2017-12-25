<?php

namespace My\AppBundle\Model;

/**
 * Promo
 */
abstract class Promo
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
     * @var \DateTime
     */
    protected $created;

    /**
     * @var \DateTime
     */
    protected $used_from;

    /**
     * @var \DateTime
     */
    protected $used_to;

    /**
     * @var boolean
     */
    protected $active;

    /**
     \* @var string
     */
    protected $restricted;

    /**
     * @var integer
     */
    protected $maxUsers;

    /**
     * @var boolean
     */
    protected $autoCreate;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $keys;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->keys = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Promo
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
     * Set created
     *
     * @param \DateTime $created
     * @return Promo
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set used_from
     *
     * @param \DateTime $usedFrom
     * @return Promo
     */
    public function setUsedFrom($usedFrom)
    {
        $this->used_from = $usedFrom;

        return $this;
    }

    /**
     * Get used_from
     *
     * @return \DateTime 
     */
    public function getUsedFrom()
    {
        return $this->used_from;
    }

    /**
     * Set used_to
     *
     * @param \DateTime $usedTo
     * @return Promo
     */
    public function setUsedTo($usedTo)
    {
        $this->used_to = $usedTo;

        return $this;
    }

    /**
     * Get used_to
     *
     * @return \DateTime 
     */
    public function getUsedTo()
    {
        return $this->used_to;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return Promo
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
     * Set restricted
     *
     \* @param string $restricted
     * @return Promo
     */
    public function setRestricted($restricted)
    {
        $this->restricted = $restricted;

        return $this;
    }

    /**
     * Get restricted
     *
     \* @return string 
     */
    public function getRestricted()
    {
        return $this->restricted;
    }

    /**
     * Set maxUsers
     *
     * @param integer $maxUsers
     * @return Promo
     */
    public function setMaxUsers($maxUsers)
    {
        $this->maxUsers = $maxUsers;

        return $this;
    }

    /**
     * Get maxUsers
     *
     * @return integer 
     */
    public function getMaxUsers()
    {
        return $this->maxUsers;
    }

    /**
     * Set autoCreate
     *
     * @param boolean $autoCreate
     * @return Promo
     */
    public function setAutoCreate($autoCreate)
    {
        $this->autoCreate = $autoCreate;

        return $this;
    }

    /**
     * Get autoCreate
     *
     * @return boolean 
     */
    public function getAutoCreate()
    {
        return $this->autoCreate;
    }

    /**
     * Add keys
     *
     * @param \My\AppBundle\Model\PromoKey $keys
     * @return Promo
     */
    public function addKey(\My\AppBundle\Model\PromoKey $keys)
    {
        $this->keys[] = $keys;

        return $this;
    }

    /**
     * Remove keys
     *
     * @param \My\AppBundle\Model\PromoKey $keys
     */
    public function removeKey(\My\AppBundle\Model\PromoKey $keys)
    {
        $this->keys->removeElement($keys);
    }

    /**
     * Get keys
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getKeys()
    {
        return $this->keys;
    }
}
