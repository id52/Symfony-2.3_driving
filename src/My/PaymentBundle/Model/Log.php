<?php

namespace My\PaymentBundle\Model;

/**
 * Log
 */
abstract class Log
{
    /**
     * @var integer
     */
    protected $id;

    /**
     \* @var string
     */
    protected $s_type;

    /**
     * @var string
     */
    protected $s_id;

    /**
     * @var integer
     */
    protected $sum;

    /**
     * @var boolean
     */
    protected $paid;

    /**
     * @var string
     */
    protected $comment;

    /**
     * @var \DateTime
     */
    protected $created_at;

    /**
     * @var \DateTime
     */
    protected $updated_at;

    /**
     \* @var string
     */
    protected $display;

    /**
     * @var \DateTime
     */
    protected $transferred_at;

    /**
     * @var \My\AppBundle\Entity\OweStage
     */
    protected $owe_stage;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $revert_logs;

    /**
     * @var \My\AppBundle\Entity\User
     */
    protected $user;

    /**
     * @var \My\AppBundle\Entity\User
     */
    protected $admin;

    /**
     * @var \My\AppBundle\Entity\PromoKey
     */
    protected $promoKey;

    /**
     * @var \My\AppBundle\Entity\DrivingPackage
     */
    protected $package;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->revert_logs = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set s_type
     *
     \* @param string $sType
     * @return Log
     */
    public function setSType($sType)
    {
        $this->s_type = $sType;

        return $this;
    }

    /**
     * Get s_type
     *
     \* @return string 
     */
    public function getSType()
    {
        return $this->s_type;
    }

    /**
     * Set s_id
     *
     * @param string $sId
     * @return Log
     */
    public function setSId($sId)
    {
        $this->s_id = $sId;

        return $this;
    }

    /**
     * Get s_id
     *
     * @return string 
     */
    public function getSId()
    {
        return $this->s_id;
    }

    /**
     * Set sum
     *
     * @param integer $sum
     * @return Log
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
     * @return Log
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
     * Set comment
     *
     * @param string $comment
     * @return Log
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return Log
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
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     * @return Log
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated_at
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set display
     *
     \* @param string $display
     * @return Log
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get display
     *
     \* @return string 
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set transferred_at
     *
     * @param \DateTime $transferredAt
     * @return Log
     */
    public function setTransferredAt($transferredAt)
    {
        $this->transferred_at = $transferredAt;

        return $this;
    }

    /**
     * Get transferred_at
     *
     * @return \DateTime 
     */
    public function getTransferredAt()
    {
        return $this->transferred_at;
    }

    /**
     * Set owe_stage
     *
     * @param \My\AppBundle\Entity\OweStage $oweStage
     * @return Log
     */
    public function setOweStage(\My\AppBundle\Entity\OweStage $oweStage = null)
    {
        $this->owe_stage = $oweStage;

        return $this;
    }

    /**
     * Get owe_stage
     *
     * @return \My\AppBundle\Entity\OweStage 
     */
    public function getOweStage()
    {
        return $this->owe_stage;
    }

    /**
     * Add revert_logs
     *
     * @param \My\PaymentBundle\Entity\RevertLog $revertLogs
     * @return Log
     */
    public function addRevertLog(\My\PaymentBundle\Entity\RevertLog $revertLogs)
    {
        $this->revert_logs[] = $revertLogs;

        return $this;
    }

    /**
     * Remove revert_logs
     *
     * @param \My\PaymentBundle\Entity\RevertLog $revertLogs
     */
    public function removeRevertLog(\My\PaymentBundle\Entity\RevertLog $revertLogs)
    {
        $this->revert_logs->removeElement($revertLogs);
    }

    /**
     * Get revert_logs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRevertLogs()
    {
        return $this->revert_logs;
    }

    /**
     * Set user
     *
     * @param \My\AppBundle\Entity\User $user
     * @return Log
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
     * Set admin
     *
     * @param \My\AppBundle\Entity\User $admin
     * @return Log
     */
    public function setAdmin(\My\AppBundle\Entity\User $admin = null)
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * Get admin
     *
     * @return \My\AppBundle\Entity\User 
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * Set promoKey
     *
     * @param \My\AppBundle\Entity\PromoKey $promoKey
     * @return Log
     */
    public function setPromoKey(\My\AppBundle\Entity\PromoKey $promoKey = null)
    {
        $this->promoKey = $promoKey;

        return $this;
    }

    /**
     * Get promoKey
     *
     * @return \My\AppBundle\Entity\PromoKey 
     */
    public function getPromoKey()
    {
        return $this->promoKey;
    }

    /**
     * Set package
     *
     * @param \My\AppBundle\Entity\DrivingPackage $package
     * @return Log
     */
    public function setPackage(\My\AppBundle\Entity\DrivingPackage $package = null)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get package
     *
     * @return \My\AppBundle\Entity\DrivingPackage 
     */
    public function getPackage()
    {
        return $this->package;
    }
}
