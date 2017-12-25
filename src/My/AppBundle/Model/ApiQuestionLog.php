<?php

namespace My\AppBundle\Model;

/**
 * ApiQuestionLog
 */
abstract class ApiQuestionLog
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $radio;

    /**
     * @var integer
     */
    protected $months;

    /**
     * @var \DateTime
     */
    protected $created_at;

    /**
     * @var \My\AppBundle\Entity\User
     */
    protected $user;


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
     * Set radio
     *
     * @param string $radio
     * @return ApiQuestionLog
     */
    public function setRadio($radio)
    {
        $this->radio = $radio;

        return $this;
    }

    /**
     * Get radio
     *
     * @return string 
     */
    public function getRadio()
    {
        return $this->radio;
    }

    /**
     * Set months
     *
     * @param integer $months
     * @return ApiQuestionLog
     */
    public function setMonths($months)
    {
        $this->months = $months;

        return $this;
    }

    /**
     * Get months
     *
     * @return integer 
     */
    public function getMonths()
    {
        return $this->months;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return ApiQuestionLog
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set user
     *
     * @param \My\AppBundle\Entity\User $user
     * @return ApiQuestionLog
     */
    public function setUser(\My\AppBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \My\AppBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }
}
