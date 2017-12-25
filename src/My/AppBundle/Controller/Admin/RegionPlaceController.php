<?php

namespace My\AppBundle\Controller\Admin;

use My\AppBundle\Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Symfony\Component\HttpFoundation\Request;

class RegionPlaceController extends AbstractEntityController
{
    protected $listFields = array('name', 'region');
    protected $orderBy = array('name' => 'ASC');
    protected $tmplItem = 'RegionPlace/item.html.twig';
    protected $tmplList = 'RegionPlace/list.html.twig';

    public function listAction($type = null)
    {
        $qb = $this->repo->createQueryBuilder('e');
        foreach ($this->orderBy as $field => $order) {
            $qb->addOrderBy('e.'.$field, $order);
        }

        $type = trim($type);
        $region = $this->em->getRepository('AppBundle:Region')->findOneBy(array());
        if ($type == 'moskva') {
            $qb->andWhere('e.region = :region')->setParameter('region', $region);
        } elseif ($type == 'oblast') {
            $qb->andWhere('e.region != :region')->setParameter('region', $region);
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
        /** @var  $entity \My\AppBundle\Entity\RegionPlace */
        $id = null;
        $selectedCategories = array();
        if ($id = $request->get('id')) {
            $entity = $this->repo->find($id);
            if (!$entity) {
                throw $this->createNotFoundException($this->entityName.' for id "'.$id.'" not found.');
            }
            $categories = $entity->getCategories();
            foreach ($categories as $category) {
                /** @var $category \My\AppBundle\Entity\Category */
                $selectedCategories[$category->getId()] = $category;
            }
        } else {
            $entity = new $this->entityClassName();
        }

        $activeCategories = $this->em->getRepository('AppBundle:Category')->createQueryBuilder('c')
            ->getQuery()->execute();
        $choices = array();
        foreach ($activeCategories as $category) {
            /** @var $category \My\AppBundle\Entity\Category */
            $choices[$category->getId()] = $category;
        }

        $form = $this->createForm(new $this->formClassName(), $entity, array(
            'translation_domain' => $this->entityNameS,
        ));

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->em->persist($entity);
            $this->em->flush();

            // Добавляем в новый массив ключи - которые являються Id категорий
            $selected = $request->get('categories');
            $selectedId = array();
            if ($selected) {
                foreach ($selected as $categoryId => $value) {
                    $selectedId[] = $categoryId;
                }
            }

            // Получаем массив всех категорий с ключами - Id категории
            $allCategories = $this->em->getRepository('AppBundle:Category')->findAll();
            $allCategoriesId = array();
            foreach ($allCategories as $cat) {
                $allCategoriesId[$cat->getId()] = $cat;
            }

            // Удаляем все категории
            foreach ($allCategoriesId as $notSelected) { /** @var $notSelected \My\AppBundle\Entity\Category */
                $notSelected->removeRegionPlace($entity);
                $this->em->persist($notSelected);
            }

            // Назначаем выбранные категории
            $categories = $this->em->getRepository('AppBundle:Category')->createQueryBuilder('c')
                ->andWhere('c.id IN (:categories)')->setParameter('categories', $selectedId)
//                ->andWhere('c.active = :active')->setParameter('active', true)
                ->getQuery()->execute();
            foreach ($categories as $category) {
                /** @var $category \My\AppBundle\Entity\Category */
                $category->addRegionPlace($entity);
                $this->em->persist($category);
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
            'form'              => $form->createView(),
            'categories'        => $choices,
            'select_categories' => $selectedCategories,
            'entity'            => $entity,
        ));
    }
}
