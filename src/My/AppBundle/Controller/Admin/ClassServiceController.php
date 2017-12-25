<?php

namespace My\AppBundle\Controller\Admin;

use Symfony\Component\Validator\Constraints as Assert;

class ClassServiceController extends AbstractEntityController
{
    protected $tmplList = 'ClassService/list.html.twig';
    protected $tmplItem = 'ClassService/item.html.twig';
}
