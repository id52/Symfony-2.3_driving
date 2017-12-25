<?php

namespace My\AppBundle\Controller\Admin;

use My\AppBundle\Entity\DrivingPackage;
use My\AppBundle\Entity\DrivingTicket;
use My\AppBundle\Entity\RegionPlacePrice;
use My\AppBundle\Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DrivingConditionsController extends AbstractEntityController
{
    protected $routerList = 'admin_driving_conditions';
    protected $listFields = array(
        'condCode',
        'name',
        'withAt',
        'isPrimary',
        'countPackages',
        'countnotsoldPackages',
    );
    protected $tmplItem = 'DrivingConditions\item.html.twig';
    protected $tmplList = 'DrivingConditions\list.html.twig';
    protected $orderBy = array('position' => 'ASC');

    public function listAction()
    {
        $qb = $this->em->getRepository('AppBundle:DrivingConditions')->createQueryBuilder('dc')
            ->leftJoin('dc.region_prices', 'rp')
            ->andWhere('rp.condition = dc.id')
            ->andWhere('rp.with_at = dc.with_at')
            ->addSelect('rp.price AS price');

        foreach ($this->orderBy as $field => $order) {
            $qb->addOrderBy('dc.'.$field, $order);
        }

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($this->getRequest()->get('page'));

        return $this->render('AppBundle:Admin:'.$this->tmplList, array(
            'pagerfanta'  => $pagerfanta,
            'condition_list_fields' => $this->listFields,
        ));
    }

    public function itemAction(Request $request)
    {
        $id = null;
        $startPrices = array();

        /** @var  $entity \My\AppBundle\Entity\DrivingConditions */
        if ($id = $request->get('id')) {
            $entity = $this->repo->find($id);
            if (!$entity) {
                throw $this->createNotFoundException($this->entityName.' for id "'.$id.'" not found.');
            }

            $placePrices = $this->em->getRepository('AppBundle:RegionPlacePrice')->createQueryBuilder('p')
                ->andWhere('p.condition = :condition')->setParameter('condition', $entity)
                ->leftJoin('p.category', 'pc')->addSelect('pc')
                ->leftJoin('p.place', 'pp')->addSelect('pp')
                ->getQuery()->execute();

            foreach ($placePrices as $price) {
                /** @var  $price \My\AppBundle\Entity\RegionPlacePrice */
                $place = $price->getPlace();
                $category = $price->getCategory();
                $startPrices['price'] = $price->getPrice();
                $startPrices[$place->getId()][$category->getId()]['active'] = $price->getActive();
            }
        } else {
            $entity = new $this->entityClassName();
        }

        $existPackages = count($entity->getPackages()) > 0;

        $regions = $this->em->getRepository('AppBundle:Region')->createQueryBuilder('r')
            ->leftJoin('r.places', 'rp')->addSelect('rp')
            ->leftJoin('rp.categories', 'rp_c')->addSelect('rp_c')
            ->getQuery()->execute();

        $form = $this->createForm(new $this->formClassName(), $entity, array(
            'translation_domain' => $this->entityNameS,
        ));
        $form->handleRequest($request);
        if ($form->isValid()) {
            $this->em->persist($entity);
            $this->em->flush();

            $with_at = $form->get('with_at')->getData();

            $priceNum = $request->get('condition_price');
            $pricesActive = $request->get('prices_active');

            $this->em->getRepository('AppBundle:RegionPlacePrice')->createQueryBuilder('p')
                ->delete()
                ->andWhere('p.condition =:condition')->setParameter('condition', $entity)
                ->getQuery()->execute();

            foreach ($regions as $region) {
                /** @var  $region \My\AppBundle\Entity\Region */
                $places = $region->getPlaces();
                foreach ($places as $place) {
                    /** @var $place \My\AppBundle\Entity\RegionPlace */
                    $categories = $place->getCategories();
                    foreach ($categories as $category) {
                        /** @var $category \My\AppBundle\Entity\Category */
                        $placePrice = new RegionPlacePrice();
                        $placePrice->setCondition($entity);
                        $placePrice->setWithAt($with_at);
                        $placePrice->setPlace($place);
                        $placePrice->setCategory($category);
                        if ($priceNum != null  && !$existPackages) {
                            $placePrice->setPrice($priceNum);
                        } else {
                            $price = isset($startPrices['price'])
                                ? $startPrices['price'] : null;
                            $placePrice->setPrice(is_null($price) ? 0 : $price);
                        }
                        $placePrice
                            ->setActive(isset($pricesActive[$place->getId()][$category->getId()]) ? true : false);

                        $this->em->persist($placePrice);
                    }
                }
            }

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
            'form'           => $form->createView(),
            'entity'         => $entity,
            'regions'        => $regions,
            'start_prices'   => $startPrices,
            'exist_packages' => $existPackages,
        ));
    }

    public function deleteAction($id)
    {
        $entity = $this->repo->find($id);
        if (!$entity) {
            throw $this->createNotFoundException($this->entityName.' for id "'.$id.'" not found.');
        }
        /** @var $entity \My\AppBundle\Entity\DrivingConditions */
        if (count($entity->getPackages())) {
            $this->get('session')->getFlashBag()->add('error', 'errors.driving_condition_cant_delete');
        } else {
            $this->em->remove($entity);
            $this->em->flush();
            $this->get('session')->getFlashBag()->add('success', 'success_deleted');
        }

        $this->em->remove($entity);
        $this->em->flush();

        $this->get('session')->getFlashBag()->add('success', 'success_deleted');
        return $this->redirect($this->generateUrl($this->routerList));
    }

    public function addPackageAction(Request $request, $id)
    {
        $condition = $this->em->getRepository('AppBundle:DrivingConditions')->createQueryBuilder('dc')
            ->andWhere('dc.active = :active')->setParameter('active', true)
            ->andWhere('dc.id = :id')->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if (!$condition) {
            throw new NotFoundHttpException('Not find driving condition for Id '.$id);
        }

        $form_factory = $this->container->get('form.factory');
        $form = $form_factory->createNamedBuilder('package')
            ->add('start_number', 'integer', array(
                'attr'   => array('class' => 'span2'),
                'mapped' => false,
                'label'  => 'Начальный номер',
            ))
            ->add('total_number', 'integer', array(
                'attr'   => array('class' => 'span2'),
                'mapped' => false,
                'label'  => 'Кол-во пакетов талонов',
            ))
            ->getForm();

        $form->handleRequest($request);

        $startNumber = $form->get('start_number')->getData();
        $totalNumber = $form->get('total_number')->getData();
        $existingPackages = $this->em->getRepository('AppBundle:DrivingPackage')->createQueryBuilder('p')
            ->andWhere('p.number >= :start')->setParameter('start', $startNumber)
            ->andWhere('p.number <= :end')->setParameter('end', $startNumber + $totalNumber)
            ->getQuery()->execute();

        if ($existingPackages) {
            $form->get('start_number')
                ->addError(new FormError('Присутствуют пакеты в заданном диапозоне, измените значения.'));
        }

        if ($form->isValid()) {
            for ($i = $startNumber; $i < $startNumber + $totalNumber; $i++) {
                $numberTickets = $condition->getNumberTickets();

                $package = new DrivingPackage();
                $package->setCondition($condition);
                $package->setNumber($i);

                $tickets = array();
                for ($j = 0; $j < $numberTickets; $j++) {
                    $ticket = new DrivingTicket();
                    $this->em->persist($ticket);

                    $tickets[] = $ticket;
                }
                $this->em->flush();

                foreach ($tickets as $ticket) {
                    /** @var $ticket \My\AppBundle\Entity\DrivingTicket */
                    $package->addTicket($ticket);
                    $ticket->setPackage($package);

                    $this->em->persist($ticket);
                }
                $this->em->persist($package);
            }

            $this->em->flush();

            $this->get('session')->getFlashBag()->add('success', 'success_added');
            return $this->redirect($this->generateUrl($this->routerList));
        }

        return $this->render('AppBundle:Admin:DrivingConditions/add_package.html.twig', array(
            'form'   => $form->createView(),
        ));
    }

    public function packagesListAction($id)
    {
        $package = $this->em->getRepository('AppBundle:DrivingConditions')->createQueryBuilder('dc')
            ->andWhere('dc.id = :id')->setParameter('id', $id)
            ->leftJoin('dc.region_prices', 'rp')
            ->andWhere('rp.condition = dc.id')
            ->andWhere('rp.with_at = dc.with_at')
            ->addSelect('rp.price AS price')
            ->getQuery()->getOneOrNullResult();

        if (!$package) {
            throw $this->createNotFoundException('Driving Conditions for id "'.$id.'" not found.');
        }

        $packageListFields = array('number', 'rezervAt', 'saleAt', 'userParadoxId', 'fullNameUser', 'status');

        $qb = $this->em->getRepository('AppBundle:DrivingPackage')->createQueryBuilder('p')
            ->andWhere('p.condition = :condition')->setParameter('condition', $id)
            ->leftJoin('p.user', 'u')->addSelect('u');

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($this->getRequest()->get('page'));

        return $this->render('AppBundle:Admin:DrivingConditions/packages_list.html.twig', array(
            'pagerfanta'  => $pagerfanta,
            'package_list_fields' => $packageListFields,
            'condition_list_fields' => $this->listFields,
            'package' => $package,
        ));
    }
}
