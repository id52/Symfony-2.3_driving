<?php

namespace My\AppBundle\Util;

use KreaLab\CommonBundle\Entity\LegalEntity;
use KreaLab\CommonBundle\Entity\ReferenceType;

class Time
{
    /**
     * @param $object     \DateInterval
     * @return int
     */
    public static function getAllSeconds($object = null)
    {
        if ($object && $object instanceof \DateInterval) {
            $d = $object->days;
            $h = $object->h;
            $i = $object->i;
            $s = $object->s;

            $allSeconds = $s + $i * 60 + $h * 3600 + $d * 86400;

            return $allSeconds;
        }

        return 0;
    }
}
