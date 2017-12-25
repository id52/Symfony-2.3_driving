<?php

namespace My\AppBundle\Model;

/**
 * ClassService
 */
abstract class ClassService
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
    protected $position;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $conditions;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->conditions = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return ClassService
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
     * Set position
     *
     * @param integer $position
     * @return ClassService
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
     * Add conditions
     *
     * @param \My\AppBundle\Entity\DrivingConditions $conditions
     * @return ClassService
     */
    public function addCondition(\My\AppBundle\Entity\DrivingConditions $conditions)
    {
        $this->conditions[] = $conditions;

        return $this;
    }

    /**
     * Remove conditions
     *
     * @param \My\AppBundle\Entity\DrivingConditions $conditions
     */
    public function removeCondition(\My\AppBundle\Entity\DrivingConditions $conditions)
    {
        $this->conditions->removeElement($conditions);
    }

    /**
     * Get conditions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getConditions()
    {
        return $this->conditions;
    }
}
