<?php

namespace My\AppBundle\Model;

/**
 * SecondPaymentPost
 */
abstract class SecondPaymentPost
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $user_id;

    /**
     * @var integer
     */
    protected $log_id;

    /**
     * @var \DateTime
     */
    protected $created_at;

    /**
     * @var \DateTime
     */
    protected $sended_at;

    /**
     * @var \DateTime
     */
    protected $arrived_at;


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
     * Set user_id
     *
     * @param integer $userId
     * @return SecondPaymentPost
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get user_id
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set log_id
     *
     * @param integer $logId
     * @return SecondPaymentPost
     */
    public function setLogId($logId)
    {
        $this->log_id = $logId;

        return $this;
    }

    /**
     * Get log_id
     *
     * @return integer 
     */
    public function getLogId()
    {
        return $this->log_id;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return SecondPaymentPost
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
     * Set sended_at
     *
     * @param \DateTime $sendedAt
     * @return SecondPaymentPost
     */
    public function setSendedAt($sendedAt)
    {
        $this->sended_at = $sendedAt;

        return $this;
    }

    /**
     * Get sended_at
     *
     * @return \DateTime 
     */
    public function getSendedAt()
    {
        return $this->sended_at;
    }

    /**
     * Set arrived_at
     *
     * @param \DateTime $arrivedAt
     * @return SecondPaymentPost
     */
    public function setArrivedAt($arrivedAt)
    {
        $this->arrived_at = $arrivedAt;

        return $this;
    }

    /**
     * Get arrived_at
     *
     * @return \DateTime 
     */
    public function getArrivedAt()
    {
        return $this->arrived_at;
    }
}
