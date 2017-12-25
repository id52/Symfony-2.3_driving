<?php

namespace My\AppBundle\Controller\Admin;

use My\AppBundle\Entity\ServicePrice;
use Symfony\Component\HttpFoundation\Request;

class ServiceController extends AbstractEntityController
{
    protected $listFields = array('name');
    protected $tmplItem = 'Service/item.html.twig';
    protected $tmplList = 'Service/list.html.twig';

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
        /** @var $entity \My\AppBundle\Entity\Service */

        $regions = $this->em->getRepository('AppBundle:Region')->createQueryBuilder('r')
            ->orderBy('r.name')->getQuery()->getResult();

        $prices = array();
        $prices_comb = array();
        $prices_expr = array();
        $prices_active = array();
        $regions_prices = $entity->getRegionsPrices();
        foreach ($regions_prices as $price) { /** @var $price \My\AppBundle\Entity\ServicePrice */
            $rid = $price->getRegion()->getId();
            $prices[$rid] = $price->getPrice();
            $prices_comb[$rid] = $price->getPriceComb();
            $prices_expr[$rid] = $price->getPriceExpr();
            $prices_active[$rid] = (bool)$price->getActive();
        }

        $form = $this->createForm(new $this->formClassName(), $entity);
        $form->handleRequest($request);
        if ($request->isMethod('post')) {
            $prices = (array)$request->get('prices', []) + $prices;
            $prices_comb = (array)$request->get('prices_comb', []) + $prices_comb;
            $prices_expr = (array)$request->get('prices_expr', []) + $prices_expr;
            $prices_active = (array)$request->get('prices_active', []);

            if ($form->isValid()) {
                $this->em->persist($entity);
                $this->em->flush();

                $this->em->getRepository('AppBundle:ServicePrice')->createQueryBuilder('sp')
                    ->delete()
                    ->where('sp.service = :service')->setParameter(':service', $entity)
                    ->getQuery()->execute();
                foreach ($regions as $region) { /** @var $region \My\AppBundle\Entity\Region */
                    $rid = $region->getId();
                    $is_training = $entity->getType() == 'training';
                    $price = new ServicePrice();
                    $price->setPrice(isset($prices[$rid]) ? $prices[$rid] : 0);
                    $price->setPriceComb(isset($prices_comb[$rid]) && $is_training ? $prices_comb[$rid] : 0);
                    $price->setPriceExpr(isset($prices_expr[$rid]) && $is_training ? $prices_expr[$rid] : 0);
                    $price->setActive($is_training ? true : isset($prices_active[$rid]));
                    $price->setRegion($region);
                    $price->setService($entity);
                    $this->em->persist($price);

                    /** FIX STRANGE BUG */
                    $region->addServicesPrice($price);
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
            'regions'       => $regions,
            'prices'        => $prices,
            'prices_comb'   => $prices_comb,
            'prices_expr'   => $prices_expr,
            'prices_active' => $prices_active,
        ));
    }
}
