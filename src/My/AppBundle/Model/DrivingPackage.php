<?php

namespace My\AppBundle\Model;

/**
 * DrivingPackage
 */
abstract class DrivingPackage
{
    /**
     * @var integer
     */
    protected $number;

    /**
     * @var \DateTime
     */
    protected $rezerv_at;

    /**
     * @var \DateTime
     */
    protected $sale_at;

    /**
     * @var \DateTime
     */
    protected $received_at;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $tickets;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $payment_logs;

    /**
     * @var \My\AppBundle\Entity\DrivingConditions
     */
    protected $condition;

    /**
     * @var \My\AppBundle\Entity\User
     */
    protected $user;

    /**
     * @var \My\AppBundle\Entity\User
     */
    protected $moderator;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tickets = new \Doctrine\Common\Collections\ArrayCollection();
        $this->payment_logs = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set number
     *
     * @param integer $number
     * @return DrivingPackage
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number
     *
     * @return integer 
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set rezerv_at
     *
     * @param \DateTime $rezervAt
     * @return DrivingPackage
     */
    public function setRezervAt($rezervAt)
    {
        $this->rezerv_at = $rezervAt;

        return $this;
    }

    /**
     * Get rezerv_at
     *
     * @return \DateTime 
     */
    public function getRezervAt()
    {
        return $this->rezerv_at;
    }

    /**
     * Set sale_at
     *
     * @param \DateTime $saleAt
     * @return DrivingPackage
     */
    public function setSaleAt($saleAt)
    {
        $this->sale_at = $saleAt;

        return $this;
    }

    /**
     * Get sale_at
     *
     * @return \DateTime 
     */
    public function getSaleAt()
    {
        return $this->sale_at;
    }

    /**
     * Set received_at
     *
     * @param \DateTime $receivedAt
     * @return DrivingPackage
     */
    public function setReceivedAt($receivedAt)
    {
        $this->received_at = $receivedAt;

        return $this;
    }

    /**
     * Get received_at
     *
     * @return \DateTime 
     */
    public function getReceivedAt()
    {
        return $this->received_at;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return DrivingPackage
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Add tickets
     *
     * @param \My\AppBundle\Entity\DrivingTicket $tickets
     * @return DrivingPackage
     */
    public function addTicket(\My\AppBundle\Entity\DrivingTicket $tickets)
    {
        $this->tickets[] = $tickets;

        return $this;
    }

    /**
     * Remove tickets
     *
     * @param \My\AppBundle\Entity\DrivingTicket $tickets
     */
    public function removeTicket(\My\AppBundle\Entity\DrivingTicket $tickets)
    {
        $this->tickets->removeElement($tickets);
    }

    /**
     * Get tickets
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTickets()
    {
        return $this->tickets;
    }

    /**
     * Add payment_logs
     *
     * @param \My\PaymentBundle\Entity\Log $paymentLogs
     * @return DrivingPackage
     */
    public function addPaymentLog(\My\PaymentBundle\Entity\Log $paymentLogs)
    {
        $this->payment_logs[] = $paymentLogs;

        return $this;
    }

    /**
     * Remove payment_logs
     *
     * @param \My\PaymentBundle\Entity\Log $paymentLogs
     */
    public function removePaymentLog(\My\PaymentBundle\Entity\Log $paymentLogs)
    {
        $this->payment_logs->removeElement($paymentLogs);
    }

    /**
     * Get payment_logs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPaymentLogs()
    {
        return $this->payment_logs;
    }

    /**
     * Set condition
     *
     * @param \My\AppBundle\Entity\DrivingConditions $condition
     * @return DrivingPackage
     */
    public function setCondition(\My\AppBundle\Entity\DrivingConditions $condition = null)
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

    /**
     * Set user
     *
     * @param \My\AppBundle\Entity\User $user
     * @return DrivingPackage
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

    /**
     * Set moderator
     *
     * @param \My\AppBundle\Entity\User $moderator
     * @return DrivingPackage
     */
    public function setModerator(\My\AppBundle\Entity\User $moderator = null)
    {
        $this->moderator = $moderator;

        return $this;
    }

    /**
     * Get moderator
     *
     * @return \My\AppBundle\Entity\User 
     */
    public function getModerator()
    {
        return $this->moderator;
    }
}
