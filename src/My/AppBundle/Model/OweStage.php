<?php

namespace My\AppBundle\Model;

/**
 * OweStage
 */
abstract class OweStage
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $number_stage;

    /**
     * @var integer
     */
    protected $sum;

    /**
     * @var boolean
     */
    protected $paid;

    /**
     * @var \DateTime
     */
    protected $start;

    /**
     * @var \DateTime
     */
    protected $end;

    /**
     * @var \My\PaymentBundle\Entity\Log
     */
    protected $log;

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
     * Set number_stage
     *
     * @param integer $numberStage
     * @return OweStage
     */
    public function setNumberStage($numberStage)
    {
        $this->number_stage = $numberStage;

        return $this;
    }

    /**
     * Get number_stage
     *
     * @return integer 
     */
    public function getNumberStage()
    {
        return $this->number_stage;
    }

    /**
     * Set sum
     *
     * @param integer $sum
     * @return OweStage
     */
    public function setSum($sum)
    {
        $this->sum = $sum;

        return $this;
    }

    /**
     * Get sum
     *
     * @return integer 
     */
    public function getSum()
    {
        return $this->sum;
    }

    /**
     * Set paid
     *
     * @param boolean $paid
     * @return OweStage
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;

        return $this;
    }

    /**
     * Get paid
     *
     * @return boolean 
     */
    public function getPaid()
    {
        return $this->paid;
    }

    /**
     * Set start
     *
     * @param \DateTime $start
     * @return OweStage
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get start
     *
     * @return \DateTime 
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set end
     *
     * @param \DateTime $end
     * @return OweStage
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Get end
     *
     * @return \DateTime 
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set log
     *
     * @param \My\PaymentBundle\Entity\Log $log
     * @return OweStage
     */
    public function setLog(\My\PaymentBundle\Entity\Log $log = null)
    {
        $this->log = $log;

        return $this;
    }

    /**
     * Get log
     *
     * @return \My\PaymentBundle\Entity\Log 
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Set user
     *
     * @param \My\AppBundle\Entity\User $user
     * @return OweStage
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
