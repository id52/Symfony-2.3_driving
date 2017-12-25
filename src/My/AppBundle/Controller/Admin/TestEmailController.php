<?php

namespace My\AppBundle\Controller\Admin;

class TestEmailController extends AbstractEntityController
{
    protected $listFields = array('email');

    protected function checkPermissions()
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_MAILING')) {
            throw $this->createNotFoundException();
        }
    }
}
