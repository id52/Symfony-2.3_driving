<?php

namespace My\AppBundle\Controller\Admin;

use My\AppBundle\Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;

class TrainingVersionController extends AbstractEntityController
{
    protected $tmplList = 'TrainingVersion/list.html.twig';

    public function listAction()
    {
        $qb = $this->repo->createQueryBuilder('e');
        $qb->leftJoin('e.category', 'c');
        $qb->addOrderBy('c.name');
        $qb->addOrderBy('e.start_date');

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($this->getRequest()->get('page'));

        return $this->render('AppBundle:Admin:'.$this->tmplList, array(
            'pagerfanta'  => $pagerfanta,
            'list_fields' => $this->listFields,
        ));
    }

    protected function checkPermissions()
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_CONTENT')) {
            throw $this->createNotFoundException();
        }
    }
}
