<?php

namespace My\AppBundle\Controller\Admin;

use My\AppBundle\Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractEntityController extends Controller
{
    /** @var $em \Doctrine\ORM\EntityManager */
    public $em;
    /** @var $user \My\AppBundle\Entity\User */
    public $user;

    public $settings = array();
    public $settingsNotifies = array();

    /** @var $repo \Doctrine\ORM\EntityRepository */
    protected $repo;

    protected $entityClassName;
    protected $entityName;
    protected $entityNameS;
    protected $formClassName;
    protected $listFields = array('name');
    protected $routerItemAdd;
    protected $routerItemDelete;
    protected $routerItemEdit;
    protected $routerList;
    protected $routerRoot;
    protected $orderBy = array('id' => 'ASC');
    protected $tmplItem = '_item.html.twig';
    protected $tmplList = '_list.html.twig';

    public function init()
    {
        $this->checkPermissions();

        $this->entityName = $this->entityName ?: preg_replace('#^.*\\\(.*)Controller$#', '$1', get_called_class());
        $this->entityName = str_replace(' ', '', ucwords(str_replace('_', ' ', $this->entityName)));
        $this->repo = $this->em->getRepository('AppBundle:'.$this->entityName);

        $this->entityClassName = $this->entityClassName ?: 'My\AppBundle\Entity\\'.$this->entityName;
        $this->formClassName = $this->formClassName ?: 'My\AppBundle\Form\Type\\'.$this->entityName.'FormType';
        if (!$this->entityNameS) {
            $this->entityNameS = strtolower(trim(preg_replace('#[A-Z]+#', '_$0', $this->entityName), '_'));
        }

        $this->routerRoot = $this->routerRoot ?: 'admin_'.$this->entityNameS;
        $this->routerList = $this->routerList ?: $this->routerRoot.'s';
        $this->routerItemAdd = $this->routerItemAdd ?: $this->routerRoot.'_add';
        $this->routerItemEdit = $this->routerItemEdit ?: $this->routerRoot.'_edit';
        $this->routerItemDelete = $this->routerItemDelete ?: $this->routerRoot.'_delete';

        $twig = $this->container->get('twig');
        $twig->addGlobal('entity_name_s', $this->entityNameS);
        $twig->addGlobal('router_root', $this->routerRoot);
        $twig->addGlobal('router_list', $this->routerList);
        $twig->addGlobal('router_item_add', $this->routerItemAdd);
        $twig->addGlobal('router_item_edit', $this->routerItemEdit);
        $twig->addGlobal('router_item_delete', $this->routerItemDelete);
    }

    public function listAction()
    {
        $qb = $this->repo->createQueryBuilder('e');
        foreach ($this->orderBy as $field => $order) {
            $qb->addOrderBy('e.'.$field, $order);
        }

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($this->getRequest()->get('page'));

        return $this->render('AppBundle:Admin:'.$this->tmplList, array(
            'pagerfanta'  => $pagerfanta,
            'list_fields' => $this->listFields,
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

        $form = $this->createForm(new $this->formClassName(), $entity);

        $form->handleRequest($request);
        if ($form->isValid()) {
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

    public function deleteAction($id)
    {
        $entity = $this->repo->find($id);
        if (!$entity) {
            throw $this->createNotFoundException($this->entityName.' for id "'.$id.'" not found.');
        }

        $this->em->remove($entity);
        $this->em->flush();

        $this->get('session')->getFlashBag()->add('success', 'success_deleted');
        return $this->redirect($this->generateUrl($this->routerList));
    }

    protected function checkPermissions()
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }
    }
}
