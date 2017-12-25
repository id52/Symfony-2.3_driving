<?php

namespace My\AppBundle\Entity;

use My\AppBundle\Model\DrivingPackage as DrivingPackageModel;

class DrivingPackage extends DrivingPackageModel
{
    protected $user = null;
    protected $is_sold = false;

    public function getFullNameUser()
    {
        $user = $this->getUser();
        if (!$user) {
            return null;
        }

        return $this->getUser()->getFullName();
    }

    public function getUserParadoxId()
    {
        $user = $this->getUser();
        if (!$user) {
            return null;
        }

        return $this->getUser()->getParadoxId();
    }
}
