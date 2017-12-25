<?php

namespace My\AppBundle\Model;

/**
 * UserStat
 */
abstract class UserStat
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $reg_by;

    /**
     * @var string
     */
    protected $reg_type;

    /**
     * @var string
     */
    protected $pay_1_type;

    /**
     * @var string
     */
    protected $discount_1_type;

    /**
     * @var string
     */
    protected $pay_2_type;

    /**
     * @var string
     */
    protected $discount_2_type;

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
     * Set reg_by
     *
     * @param string $regBy
     * @return UserStat
     */
    public function setRegBy($regBy)
    {
        $this->reg_by = $regBy;

        return $this;
    }

    /**
     * Get reg_by
     *
     * @return string 
     */
    public function getRegBy()
    {
        return $this->reg_by;
    }

    /**
     * Set reg_type
     *
     * @param string $regType
     * @return UserStat
     */
    public function setRegType($regType)
    {
        $this->reg_type = $regType;

        return $this;
    }

    /**
     * Get reg_type
     *
     * @return string 
     */
    public function getRegType()
    {
        return $this->reg_type;
    }

    /**
     * Set pay_1_type
     *
     * @param string $pay1Type
     * @return UserStat
     */
    public function setPay1Type($pay1Type)
    {
        $this->pay_1_type = $pay1Type;

        return $this;
    }

    /**
     * Get pay_1_type
     *
     * @return string 
     */
    public function getPay1Type()
    {
        return $this->pay_1_type;
    }

    /**
     * Set discount_1_type
     *
     * @param string $discount1Type
     * @return UserStat
     */
    public function setDiscount1Type($discount1Type)
    {
        $this->discount_1_type = $discount1Type;

        return $this;
    }

    /**
     * Get discount_1_type
     *
     * @return string 
     */
    public function getDiscount1Type()
    {
        return $this->discount_1_type;
    }

    /**
     * Set pay_2_type
     *
     * @param string $pay2Type
     * @return UserStat
     */
    public function setPay2Type($pay2Type)
    {
        $this->pay_2_type = $pay2Type;

        return $this;
    }

    /**
     * Get pay_2_type
     *
     * @return string 
     */
    public function getPay2Type()
    {
        return $this->pay_2_type;
    }

    /**
     * Set discount_2_type
     *
     * @param string $discount2Type
     * @return UserStat
     */
    public function setDiscount2Type($discount2Type)
    {
        $this->discount_2_type = $discount2Type;

        return $this;
    }

    /**
     * Get discount_2_type
     *
     * @return string 
     */
    public function getDiscount2Type()
    {
        return $this->discount_2_type;
    }

    /**
     * Set user
     *
     * @param \My\AppBundle\Entity\User $user
     * @return UserStat
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
