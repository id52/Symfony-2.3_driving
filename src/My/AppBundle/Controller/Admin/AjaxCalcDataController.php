<?php

namespace My\AppBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxCalcDataController extends Controller
{
    public function getDrivingPlacesAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $em = $this->get('doctrine.orm.entity_manager');
        /** @var  $user  \My\AppBundle\Entity\User */
        $user = $this->getUser();
        $region = $user->getRegion();
        if (!$region) {
            return new JsonResponse(['success' => false]);
        }

        $categoryId = $request->get('catId');
        $with_at = ($request->get('with_at') == 'none') ? 'none' : ($request->get('with_at') == 'true' ? true : false);

        $checkConditionsWithAt = $this->getDrvConditions(true, $categoryId);
        $checkConditionsWithNoAt = $this->getDrvConditions(false, $categoryId);

        if ($checkConditionsWithAt && $checkConditionsWithNoAt) {
            $typeWithAt = 0;
        } elseif ($checkConditionsWithAt && !$checkConditionsWithNoAt) {
            $typeWithAt = 1;
        } elseif (!$checkConditionsWithAt && $checkConditionsWithNoAt) {
            $typeWithAt = 2;
        } else {
            return new JsonResponse(['success' => false]);
        }

        if ($with_at && $with_at !== 'none') {
            $drConditions = $checkConditionsWithAt;
        } elseif (!$with_at) {
            $drConditions = $checkConditionsWithNoAt;
        } else {
            $drConditions = array_merge($checkConditionsWithAt, $checkConditionsWithNoAt);
        }
        $drConditionsIds = array();
        foreach ($drConditions as $condition) {
            /** @var $condition \My\AppBundle\Entity\DrivingConditions */
            $drConditionsIds[] = $condition->getId();
        }

        $places = $em->getRepository('AppBundle:RegionPlace')->createQueryBuilder('pl')
            ->andWhere('pl.region = :region')->setParameter('region', $region)
            ->leftJoin('pl.categories', 'cat')
            ->andWhere('cat.id = :category')->setParameter('category', $categoryId)
            ->leftJoin('pl.place_prices', 'pr')
            ->andWhere('pr.active = :active')->setParameter('active', true)
            ->leftJoin('pr.condition', 'dr_c')
            ->andWhere('dr_c.id IN (:ids)')->setParameter('ids', $drConditionsIds)
            ->getQuery()->execute();

        $placesData = array();
        foreach ($places as $place) {
            /** @var $place \My\AppBundle\Entity\RegionPlace */
            $plrices = $place->getPlacePrices();
            foreach ($plrices as $price) {
                /** @var  $price \My\AppBundle\Entity\RegionPlacePrice */
                $drCondition = $price->getCondition();
                $packages = $em->getRepository('AppBundle:DrivingPackage')
                    ->getNotSaleAndNotRezervPackages($drCondition);

                if ($packages) {
                    $placesData[$place->getId()] = $place->getName();
                }
            }
        }

        return new JsonResponse([
            $placesData,
            'success'    => true,
            'count'      => count($placesData),
            'type_at'    => $typeWithAt,
        ]);
    }

    public function getDrivingClassServicesAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $em = $this->get('doctrine.orm.entity_manager');
        $categorId = $request->get('catId');
        $with_at = $request->get('with_at') == 'true' ? true : false;
        $placeId = $request->get('place');

        $drConditions = $this->getDrvConditions($with_at, $categorId, $placeId);
        $drConditionsIds = array();
        foreach ($drConditions as $condition) {
            /** @var $condition \My\AppBundle\Entity\DrivingConditions */
            $drConditionsIds[] = $condition->getId();
        }

        $services = $em->getRepository('AppBundle:ClassService')->createQueryBuilder('cs')
            ->leftJoin('cs.conditions', 'c')
            ->andWhere('c.id IN (:ids)')->setParameter('ids', $drConditions)
            ->getQuery()->execute();

        $serviceData = array();
        foreach ($services as $service) {
            /** @var $service \My\AppBundle\Entity\ClassService */
            $serviceData[$service->getId()] = $service->getName();
        }

        return new JsonResponse([$serviceData, 'success' => true, 'count' => count($serviceData)]);
    }

    public function getDrivingConditionsAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $categorId = $request->get('catId');
        $with_at = $request->get('with_at') == 'true' ? true : false;
        $placeId = $request->get('place');
        $serviceId = $request->get('service');

        $drConditions = $this->getDrvConditions($with_at, $categorId, $placeId, $serviceId);

        $drConditionsData = array();
        foreach ($drConditions as $condition) {
            /** @var $condition \My\AppBundle\Entity\DrivingConditions */
            $drConditionsData[$condition->getId()] = $condition->getName();
        }

        return new JsonResponse([$drConditionsData, 'success' => true, 'count' => count($drConditionsData)]);
    }

    public function getDrivingConditionPriceAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }
        $category = null;
        $with_at = null;
        $em = $this->get('doctrine.orm.entity_manager');
        $cntxt = $this->get('security.context');
        if ($cntxt->isGranted('ROLE_USER')) {
            /** @var  $user \My\AppBundle\Entity\User */
            $user = $this->getUser();
            $userCategory = $user->getCategory();

            $driveInfo = $user->getDriveInfo();

            $category = isset($userCategory) ? $userCategory : $request->get('catId');
        }

        $category = isset($category) ? $category : $request->get('catId');
        $with_at = isset($driveInfo ['with_at']) ? $driveInfo ['with_at']
            : $request->get('with_at') == 'true' ? true : false;
        $placeId = $request->get('place');
        $drConditionId = $request->get('condition');

        $drCondition = $em->find('AppBundle:DrivingConditions', $drConditionId);

        $prices = $em->getRepository('AppBundle:RegionPlacePrice')->createQueryBuilder('p')
            ->andWhere('p.with_at = :with_at')->setParameter('with_at', $with_at)
            ->andWhere('p.category = :category')->setParameter('category', $category)
            ->andWhere('p.place = :place')->setParameter('place', $placeId)
            ->andWhere('p.condition =:condition')->setParameter('condition', $drConditionId)
            ->getQuery()->execute();

        if ($prices) {
            $price = $prices[0];
        } else {
            $price = false;
        }

        /** @var $price \My\AppBundle\Entity\RegionPlacePrice */
        return new JsonResponse([
            $price             ? $price->getPrice() : 0,
            'success'         => $price ? true : false,
            'discription'     => $drCondition ? $drCondition->getDescription() : ''
        ]);
    }

    protected function getDrvConditions($with_at = null, $category = null, $placeId = null, $servId = null)
    {
        $cntxt = $this->get('security.context');
        $em = $this->get('doctrine.orm.entity_manager');
        if ($cntxt->isGranted('ROLE_USER')) {
            /** @var  $user \My\AppBundle\Entity\User */
            $user = $this->getUser();
            $userCategory = $user->getCategory();

            $driveInfo = $user->getDriveInfo();
            $with_at = isset($driveInfo ['with_at']) ? $driveInfo ['with_at'] : $with_at;
            $category = isset($userCategory) ? $userCategory : $category;

            $packages = $em->getRepository('AppBundle:DrivingPackage')->createQueryBuilder('p')
                ->andWhere('p.user = :user')->setParameter('user', $user)
                ->andWhere('p.sale_at IS NOT NULL')
                ->getQuery()->execute();

            if (count($packages)) {
                $primary = false;
            } else {
                $primary = true;
            }
        } else {
            $primary = true;
        }
        $currentTime = new \DateTime();
        $findDateTime = $currentTime->sub(new \DateInterval('PT2H'));

        $qb = $em->getRepository('AppBundle:DrivingConditions')->createQueryBuilder('dc')
            ->andWhere('dc.active = :active')->setParameter('active', true)
            ->leftJoin(
                'dc.packages',
                'cp',
                'WITH',
                '(cp.rezerv_at IS NULL OR cp.rezerv_at <= :time OR (cp.rezerv_at > :time AND cp.user = :user)) 
                AND cp.sale_at IS NULL'
            )
            ->andHaving('COUNT(cp.condition) > 0')
            ->setParameter('time', $findDateTime)
            ->setParameter('user', $this->getUser())
            ->leftJoin('dc.region_prices', 'rp')
            ->andWhere('rp.active = :act')->setParameter('act', true)
            ->groupBy('dc');

        if ($primary !== null) {
            $qb->andWhere('dc.is_primary = :primary')->setParameter('primary', $primary);
        }
        if ($with_at !== null && $with_at !== 'none') {
            $qb->andWhere('dc.with_at = :with_at')->setParameter('with_at', $with_at);
        }
        if ($category !== null) {
            $qb->andWhere('rp.category = :cat_id')->setParameter('cat_id', $category);
        }
        if ($placeId !== null) {
            $qb->andWhere('rp.place = :placeId')->setParameter('placeId', $placeId);
        }
        if ($servId !== null) {
            $qb->andWhere('dc.class_service =:service')->setParameter('service', $servId);
        }

        return $qb->getQuery()->execute();
    }
}
