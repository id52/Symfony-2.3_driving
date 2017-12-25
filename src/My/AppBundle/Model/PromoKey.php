<?php

namespace My\AppBundle\Model;

/**
 * PromoKey
 */
abstract class PromoKey
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var boolean
     */
    protected $active;

    /**
     * @var \DateTime
     */
    protected $created;

    /**
     * @var \DateTime
     */
    protected $activated;

    /**
     * @var integer
     */
    protected $discount;

    /**
     * @var string
     */
    protected $hash;

    /**
     \* @var string
     */
    protected $source;

    /**
     \* @var string
     */
    protected $type;

    /**
     * @var integer
     */
    protected $overdue_letter_num;

    /**
     * @var \DateTime
     */
    protected $valid_to;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $logs;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $tried_enters;

    /**
     * @var \My\AppBundle\Entity\Promo
     */
    protected $promo;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logs = new \Doctrine\Common\Collections\ArrayCollection();
        $this->tried_enters = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set active
     *
     * @param boolean $active
     * @return PromoKey
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
     * Set created
     *
     * @param \DateTime $created
     * @return PromoKey
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
     * Set activated
     *
     * @param \DateTime $activated
     * @return PromoKey
     */
    public function setActivated($activated)
    {
        $this->activated = $activated;

        return $this;
    }

    /**
     * Get activated
     *
     * @return \DateTime 
     */
    public function getActivated()
    {
        return $this->activated;
    }

    /**
     * Set discount
     *
     * @param integer $discount
     * @return PromoKey
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount
     *
     * @return integer 
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set hash
     *
     * @param string $hash
     * @return PromoKey
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     *
     * @return string 
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set source
     *
     \* @param string $source
     * @return PromoKey
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     \* @return string 
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set type
     *
     \* @param string $type
     * @return PromoKey
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     \* @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set overdue_letter_num
     *
     * @param integer $overdueLetterNum
     * @return PromoKey
     */
    public function setOverdueLetterNum($overdueLetterNum)
    {
        $this->overdue_letter_num = $overdueLetterNum;

        return $this;
    }

    /**
     * Get overdue_letter_num
     *
     * @return integer 
     */
    public function getOverdueLetterNum()
    {
        return $this->overdue_letter_num;
    }

    /**
     * Set valid_to
     *
     * @param \DateTime $validTo
     * @return PromoKey
     */
    public function setValidTo($validTo)
    {
        $this->valid_to = $validTo;

        return $this;
    }

    /**
     * Get valid_to
     *
     * @return \DateTime 
     */
    public function getValidTo()
    {
        return $this->valid_to;
    }

    /**
     * Add logs
     *
     * @param \My\PaymentBundle\Entity\Log $logs
     * @return PromoKey
     */
    public function addLog(\My\PaymentBundle\Entity\Log $logs)
    {
        $this->logs[] = $logs;

        return $this;
    }

    /**
     * Remove logs
     *
     * @param \My\PaymentBundle\Entity\Log $logs
     */
    public function removeLog(\My\PaymentBundle\Entity\Log $logs)
    {
        $this->logs->removeElement($logs);
    }

    /**
     * Get logs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * Add tried_enters
     *
     * @param \My\AppBundle\Entity\TriedEnters $triedEnters
     * @return PromoKey
     */
    public function addTriedEnter(\My\AppBundle\Entity\TriedEnters $triedEnters)
    {
        $this->tried_enters[] = $triedEnters;

        return $this;
    }

    /**
     * Remove tried_enters
     *
     * @param \My\AppBundle\Entity\TriedEnters $triedEnters
     */
    public function removeTriedEnter(\My\AppBundle\Entity\TriedEnters $triedEnters)
    {
        $this->tried_enters->removeElement($triedEnters);
    }

    /**
     * Get tried_enters
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTriedEnters()
    {
        return $this->tried_enters;
    }

    /**
     * Set promo
     *
     * @param \My\AppBundle\Entity\Promo $promo
     * @return PromoKey
     */
    public function setPromo(\My\AppBundle\Entity\Promo $promo = null)
    {
        $this->promo = $promo;

        return $this;
    }

    /**
     * Get promo
     *
     * @return \My\AppBundle\Entity\Promo 
     */
    public function getPromo()
    {
        return $this->promo;
    }
}
