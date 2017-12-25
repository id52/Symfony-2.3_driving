<?php

namespace My\AppBundle\Entity;

use My\AppBundle\Model\Category as CategoryModel;

class Category extends CategoryModel
{
    public function __toString()
    {
        return $this->getName();
    }

    public function getImageId()
    {
        return $this->getImage() ? $this->getImage()->getId() : null;
    }

    public function setImageId($imageId)
    {
    }
}
