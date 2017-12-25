<?php

namespace My\AppBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class FlashBlockController extends AbstractEntityController
{
    protected $perms = array('ROLE_MOD_CONTENT');
    protected $tmplList = 'FlashBlock/list.html.twig';

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

    protected function checkPermissions()
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_CONTENT')) {
            throw $this->createNotFoundException();
        }
    }
}
