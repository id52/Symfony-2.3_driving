<?php

namespace My\AppBundle\Model;

/**
 * TriedEnters
 */
abstract class TriedEnters
{
    /**
     * @var \DateTime
     */
    protected $created;

    /**
     * @var \My\AppBundle\Entity\User
     */
    protected $user;

    /**
     * @var \My\AppBundle\Entity\PromoKey
     */
    protected $promo_key;


    /**
     * Set created
     *
     * @param \DateTime $created
     * @return TriedEnters
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
     * Set user
     *
     * @param \My\AppBundle\Entity\User $user
     * @return TriedEnters
     */
    public function setUser(\My\AppBundle\Entity\User $user)
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
     * Set promo_key
     *
     * @param \My\AppBundle\Entity\PromoKey $promoKey
     * @return TriedEnters
     */
    public function setPromoKey(\My\AppBundle\Entity\PromoKey $promoKey)
    {
        $this->promo_key = $promoKey;

        return $this;
    }

    /**
     * Get promo_key
     *
     * @return \My\AppBundle\Entity\PromoKey 
     */
    public function getPromoKey()
    {
        return $this->promo_key;
    }
}
