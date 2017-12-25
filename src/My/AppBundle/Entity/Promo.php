<?php

namespace My\AppBundle\Entity;

use My\AppBundle\Model\Promo as PromoModel;

class Promo extends PromoModel
{
    protected $maxUsers = 0;
    protected $autoCreate = false;

    public function getActive()
    {
        $active = parent::getActive();
        if ($this->getUsedTo() < new \DateTime || $this->getUsedFrom() > new \DateTime) {
            $active = false;
        }

        return $active;
    }
}
