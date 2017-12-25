<?php

namespace My\PaymentBundle\Doctrine\DBAL\Types;

class EnumDisplayType extends EnumType
{
    protected $name = 'enumdisplay';
    protected $values = array('main', 'comb', 'expr');
}
