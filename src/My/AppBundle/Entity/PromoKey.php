<?php

namespace My\AppBundle\Entity;

use My\AppBundle\Model\PromoKey as PromoKeyModel;

class PromoKey extends PromoKeyModel
{
    protected $source = 'campaign';

    public function setPromo(Promo $promo = null)
    {
        if ($promo) {
            if ($promo->getRestricted() == 'users' && count($promo->getKeys()) > 0) {
                throw new \Exception("Can't add more than 1 key to Promo of type 'Users'");
            }
        }
        parent::setPromo($promo);
    }

    public function isRemovable()
    {
        return count($this->getLogs()) == 0 && count($this->getTriedEnters()) == 0;
    }

    public function getPaidUser()
    {
        $logs = $this->getLogs();
        foreach ($logs as $log) { /** @var $log \My\PaymentBundle\Entity\Log */
            if ($log->getPaid()) {
                return $log->getUser();
            }
        }
        return false;
    }
}
