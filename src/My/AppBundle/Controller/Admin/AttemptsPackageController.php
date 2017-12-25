<?php

namespace My\AppBundle\Controller\Admin;

class AttemptsPackageController extends AbstractEntityController
{
    protected $listFields = array('name', 'numberOfAttempts', 'cost');
    protected $orderBy = array('number_of_attempts' => 'ASC');
}
