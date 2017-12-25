<?php

namespace My\AppBundle\Twig\Extension;

class CommonExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('phone_format', array($this, 'phoneFormatFilter')),
        ];
    }

    public static function phoneFormatFilter($phone)
    {
        if (preg_match('#^(\d{3})(\d{3})(\d{2})(\d{2})$#', $phone, $matches)) {
            return '+7 ('.$matches[1].') '.$matches[2].'-'.$matches[3].'-'.$matches[4];
        } else {
            return $phone;
        }
    }

    public function getName()
    {
        return 'common';
    }
}
