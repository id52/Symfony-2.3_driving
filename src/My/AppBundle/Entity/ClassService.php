<?php

namespace My\AppBundle\Entity;

use My\AppBundle\Model\ClassService as ClassServiceModel;

class ClassService extends ClassServiceModel
{
    public function __toString()
    {
        return $this->name;
    }
}
