<?php

namespace My\PaymentBundle\Entity;

use My\PaymentBundle\Model\Log as LogModel;

class Log extends LogModel
{
    public $categories = [];
    public $services = [];
    public $moderate_name = '';

    protected $paid = false;
    protected $in_transfer_paradox = false;
}
