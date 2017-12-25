<?php

namespace My\AppBundle\Controller\Admin;

use Doctrine\ORM\EntityRepository;
use My\AppBundle\Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Symfony\Component\HttpFoundation\Request;

class SliceController extends AbstractEntityController
{
    protected $tmplList = 'Slice/list.html.twig';

    public function listAction()
    {
        $qb = $this->repo->createQueryBuilder('e');
        $qb->leftJoin('e.after_theme', 't');
        $qb->addOrderBy('t.subject');
        $qb->addOrderBy('t.position');

        $form_factory = $this->container->get('form.factory');
        /** @var $fb \Symfony\Component\Form\FormBuilder */
        $fb = $form_factory->createNamedBuilder('slice', 'form', array(), array('csrf_protection' => false))
            ->add('version', 'entity', array(
                'class'         => 'AppBundle:TrainingVersion',
                'required'      => false,
                'empty_value'   => 'choose_option',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('v')
                        ->leftJoin('v.category', 'c')
                        ->addOrderBy('c.name')
                        ->addOrderBy('v.start_date')
                    ;
                },
            ))
        ;

        $fb->setMethod('get');
        $filter_form = $fb->getForm();
        $filter_form->handleRequest($this->getRequest());

        $data = null;
        if ($data = $filter_form->get('version')->getData()) {
            $qb
                ->leftJoin('e.versions', 'v')
                ->andWhere('v.id = :version')->setParameter(':version', $data)
            ;
        }

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($this->getRequest()->get('page'));

        return $this->render('AppBundle:Admin:'.$this->tmplList, array(
            'pagerfanta'  => $pagerfanta,
            'list_fields' => $this->listFields,
            'filter_form' => $filter_form->createView(),
        ));
    }

    public function itemAction(Request $request)
    {
        $id = null;
        if ($id = $request->get('id')) {
            $entity = $this->repo->find($id);
            if (!$entity) {
                throw $this->createNotFoundException($this->entityName.' for id "'.$id.'" not found.');
            }
        } else {
            $entity = new $this->entityClassName();
        }
        /** @var $entity \My\AppBundle\Entity\Slice */

        $form = $this->createForm(new $this->formClassName(), $entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $allow_versions = array();
            $versions = $this->em->getRepository('AppBundle:TrainingVersion')->createQueryBuilder('v')
                ->leftJoin('v.themes', 't')
                ->andWhere('t.id = :theme')->setParameter(':theme', $entity->getAfterTheme())
                ->getQuery()->getArrayResult();
            foreach ($versions as $version) {
                $allow_versions[] = $version['id'];
            }

            $versions = $entity->getVersions();
            foreach ($versions as $version) {
                if (!in_array($version->getId(), $allow_versions)) {
                    $entity->removeVersion($version);
                }
            }

            $this->em->persist($entity);
            $this->em->flush();

            if ($id) {
                $this->get('session')->getFlashBag()->add('success', 'success_edited');
                return $this->redirect($this->generateUrl($this->routerList));
            } else {
                $this->get('session')->getFlashBag()->add('success', 'success_added');
                return $this->redirect($this->generateUrl($this->routerItemAdd));
            }
        }

        return $this->render('AppBundle:Admin:'.$this->tmplItem, array(
            'form'   => $form->createView(),
            'entity' => $entity,
        ));
    }

    protected function checkPermissions()
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_CONTENT')) {
            throw $this->createNotFoundException();
        }
    }
}
