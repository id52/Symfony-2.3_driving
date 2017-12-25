<?php

namespace My\AppBundle\Controller\Admin;

use My\AppBundle\Entity\CategoryPrice;
use My\AppBundle\Entity\ServicePrice;
use Symfony\Component\HttpFoundation\Request;

class RegionController extends AbstractEntityController
{
    protected $orderBy = array('name' => 'ASC');
    protected $tmplItem = 'Region/item.html.twig';

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
        /** @var $entity \My\AppBundle\Entity\Region */

        $categories = $this->em->getRepository('AppBundle:Category')->findAll();
        $services = $this->em->getRepository('AppBundle:Service')->findAll();

        $category_prices = array();
        $category_prices_active = array();
        $prices = $entity->getCategoriesPrices();
        foreach ($prices as $price) { /** @var $price \My\AppBundle\Entity\CategoryPrice */
            $category_prices[$price->getCategory()->getId()] = $price->getPrice();
            $category_prices_active[$price->getCategory()->getId()] = $price->getActive();
        }

        $service_prices = array();
        $service_prices_comb = array();
        $service_prices_expr = array();
        $service_prices_active = array();
        $prices = $entity->getServicesPrices();
        foreach ($prices as $price) { /** @var $price \My\AppBundle\Entity\ServicePrice */
            $service_prices[$price->getService()->getId()] = $price->getPrice();
            $service_prices_comb[$price->getService()->getId()] = $price->getPriceComb();
            $service_prices_expr[$price->getService()->getId()] = $price->getPriceExpr();
            $service_prices_active[$price->getService()->getId()] = $price->getActive();
        }

        $form = $this->createForm(new $this->formClassName(), $entity);
        $form->handleRequest($request);
        if ($request->isMethod('post')) {
            $category_prices = (array)$request->get('category_prices', []) + $category_prices;
            $category_prices_active = (array)$request->get('category_prices_active', []);

            $service_prices = (array)$request->get('service_prices', []) + $service_prices;
            $service_prices_comb = (array)$request->get('service_prices_comb', []) + $service_prices_comb;
            $service_prices_expr = (array)$request->get('service_prices_expr', []) + $service_prices_expr;
            $service_prices_active = (array)$request->get('service_prices_active', []);

            if ($form->isValid()) {
                $this->em->persist($entity);
                $this->em->flush();

                $this->em->getRepository('AppBundle:CategoryPrice')->createQueryBuilder('cp')
                    ->delete()
                    ->where('cp.region = :region')->setParameter(':region', $entity)
                    ->getQuery()->execute();
                foreach ($categories as $category) { /** @var $category \My\AppBundle\Entity\Category */
                    $c_id = $category->getId();
                    $price = new CategoryPrice();
                    $price->setPrice(isset($category_prices[$c_id]) ? $category_prices[$c_id] : 0);
                    $price->setActive(isset($category_prices_active[$c_id]));
                    $price->setRegion($entity);
                    $price->setCategory($category);
                    $this->em->persist($price);

                    /** FIX STRANGE BUG */
                    $entity->addCategoriesPrice($price);
                    $this->em->persist($entity);
                    $category->addRegionsPrice($price);
                    $this->em->persist($category);

                    $this->em->flush();
                }

                $this->em->getRepository('AppBundle:ServicePrice')->createQueryBuilder('sp')
                    ->delete()
                    ->where('sp.region = :region')->setParameter(':region', $entity)
                    ->getQuery()->execute();
                foreach ($services as $service) { /** @var $service \My\AppBundle\Entity\Service */
                    $s_id = $service->getId();
                    $is_training = $service->getType() == 'training';
                    $price = new ServicePrice();
                    $price->setPrice(isset($service_prices[$s_id]) ? $service_prices[$s_id] : 0);
                    $price->setPriceComb(isset($service_prices_comb[$s_id]) && $is_training
                        ? $service_prices_comb[$s_id] : 0);
                    $price->setPriceExpr(isset($service_prices_expr[$s_id]) && $is_training
                        ? $service_prices_expr[$s_id] : 0);
                    $price->setActive($is_training ? true : isset($service_prices_active[$s_id]));
                    $price->setRegion($entity);
                    $price->setService($service);
                    $this->em->persist($price);

                    /** FIX STRANGE BUG */
                    $entity->addServicesPrice($price);
                    $this->em->persist($entity);
                    $service->addRegionsPrice($price);
                    $this->em->persist($service);

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
            'form'                   => $form->createView(),
            'entity'                 => $entity,
            'categories'             => $categories,
            'services'               => $services,
            'category_prices'        => $category_prices,
            'category_prices_active' => $category_prices_active,
            'service_prices'         => $service_prices,
            'service_prices_comb'    => $service_prices_comb,
            'service_prices_expr'    => $service_prices_expr,
            'service_prices_active'  => $service_prices_active,
        ));
    }
}
