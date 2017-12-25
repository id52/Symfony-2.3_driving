<?php

namespace My\AppBundle\Controller\Admin;

use My\AppBundle\Entity\CategoryPrice;
use My\AppBundle\Entity\Image;
use My\AppBundle\Form\Type\ImageFormType;
use Symfony\Component\HttpFoundation\Request;

class CategoryController extends AbstractEntityController
{
    protected $routerList = 'admin_categories';
    protected $tmplItem = 'Category/item.html.twig';

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
        /** @var $entity \My\AppBundle\Entity\Category */

        $regions = $this->em->getRepository('AppBundle:Region')->createQueryBuilder('r')
            ->orderBy('r.name')->getQuery()->getResult();

        $prices = array();
        $prices_active = array();
        $regions_prices = $entity->getRegionsPrices();
        foreach ($regions_prices as $price) { /** @var $price \My\AppBundle\Entity\CategoryPrice */
            $prices[$price->getRegion()->getId()] = $price->getPrice();
            $prices_active[$price->getRegion()->getId()] = $price->getActive();
        }

        $form = $this->createForm(new $this->formClassName(), $entity);
        $form->handleRequest($request);
        if ($request->isMethod('post')) {
            $prices = (array)$request->get('prices', []) + $prices;
            $prices_active = (array)$request->get('prices_active', []);

            if ($form->isValid()) {
                $this->em->persist($entity);

                if ($entity->getImage()) {
                    $entity->getImage()->setCategory(null);
                }

                $image_id = intval($form->get('image_id')->getData());
                $image = $this->em->getRepository('AppBundle:Image')->find($image_id);
                if ($image) {
                    $image->setCategory($entity);
                }

                $this->em->flush();

                $this->em->getRepository('AppBundle:CategoryPrice')->createQueryBuilder('cp')
                    ->delete()
                    ->where('cp.category = :category')->setParameter(':category', $entity)
                    ->getQuery()->execute();
                $prices = $request->get('prices');
                $prices_active = $request->get('prices_active');
                foreach ($regions as $region) { /** @var $region \My\AppBundle\Entity\Region */
                    $price = new CategoryPrice();
                    $price->setPrice(isset($prices[$region->getId()]) ? $prices[$region->getId()] : 0);
                    $price->setActive(isset($prices_active[$region->getId()]));
                    $price->setRegion($region);
                    $price->setCategory($entity);
                    $this->em->persist($price);

                    /** FIX STRANGE BUG */
                    $region->addCategoriesPrice($price);
                    $this->em->persist($region);
                    $entity->addRegionsPrice($price);
                    $this->em->persist($entity);

                    $this->em->flush();
                }

                if ($id) {
                    $this->get('session')->getFlashBag()->add('success', 'success_edited');
                    return $this->redirect($this->generateUrl($this->routerList));
                } else {
                    $this->get('session')->getFlashBag()->add('success', 'success_added');
                    return $this->redirect($this->generateUrl($this->routerItemAdd));
                }
            }
        }

        return $this->render('AppBundle:Admin:'.$this->tmplItem, array(
            'form'          => $form->createView(),
            'entity'        => $entity,
            'imageForm'     => $this->createForm(new ImageFormType(), new Image())->createView(),
            'regions'       => $regions,
            'prices'        => $prices,
            'prices_active' => $prices_active,
        ));
    }

    public function deleteAction($id)
    {
        $entity = $this->repo->find($id);
        if (!$entity) {
            throw $this->createNotFoundException($this->entityName.' for id "'.$id.'" not found.');
        }

        if (count($entity->getUsers())) {
            $this->get('session')->getFlashBag()->add('error', 'category_error_cant_delete_users');
        } else {
            $this->em->remove($entity);
            $this->em->flush();
            $this->get('session')->getFlashBag()->add('success', 'success_deleted');
        }

        return $this->redirect($this->generateUrl($this->routerList));
    }
}
