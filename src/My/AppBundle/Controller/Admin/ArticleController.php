<?php

namespace My\AppBundle\Controller\Admin;

class ArticleController extends AbstractEntityController
{
    protected $orderByField = array('position' => 'ASC');
    protected $tmplItem = 'Article/item.html.twig';
    protected $tmplList = 'Article/list.html.twig';

    public function upAction($id)
    {
        $entity = $this->repo->find($id);
        if (!$entity) {
            throw $this->createNotFoundException($this->entityName.' for id "'.$id.'" not found.');
        }

        $entity->setPosition($entity->getPosition() - 1);

        $this->em->persist($entity);
        $this->em->flush();

        return $this->redirect($this->generateUrl($this->routerList));
    }

    public function downAction($id)
    {
        $entity = $this->repo->find($id);
        if (!$entity) {
            throw $this->createNotFoundException($this->entityName.' for id "'.$id.'" not found.');
        }

        $entity->setPosition($entity->getPosition() + 1);

        $this->em->persist($entity);
        $this->em->flush();

        return $this->redirect($this->generateUrl($this->routerList));
    }

    protected function checkPermissions()
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_CONTENT')) {
            throw $this->createNotFoundException();
        }
    }
}
