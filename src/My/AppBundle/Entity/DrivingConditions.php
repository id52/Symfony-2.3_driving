<?php

namespace My\AppBundle\Entity;

use My\AppBundle\Model\DrivingConditions as DrivingConditionsModel;

class DrivingConditions extends DrivingConditionsModel
{
    public function __toString()
    {
        return $this->name;
    }
    public function getCountPackages()
    {
        return count($this->packages);
    }

    public function getCountNotSoldPackages()
    {
        $packages = $this->packages;
        $notSold = array();
        foreach ($packages as $package) {
            /** @var $package \My\AppBundle\Entity\DrivingPackage  */
            if ($package->getSaleAt() == null) {
                $notSold[] = $package;
            }
        }

        return count($notSold);
    }
}
