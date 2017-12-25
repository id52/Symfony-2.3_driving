<?php

namespace My\AppBundle\Entity;

use My\AppBundle\Model\SupportCategory as SupportCategoryModel;

class SupportCategory extends SupportCategoryModel
{
    public function __toString()
    {
        $parentName = $this->getParent()->getName();
        if (empty($parentName)) {
            return $this->getName();
        } else {
            return $parentName.': '.$this->getName();
        }
    }
}
