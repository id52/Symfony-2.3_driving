<?php

namespace My\AppBundle\Controller\Admin;

use My\AppBundle\Doctrine\ORM\Tools\Summator;
use My\PaymentBundle\Entity\Log;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CheckPaymentOneTwoController extends Controller
{
    /** @var $em \Doctrine\ORM\EntityManager */
    public $em;

    public function init()
    {
        if ($this->get('security.context')->isGranted('ROLE_MOD_ACCOUNTANT') === false) {
            throw $this->createNotFoundException();
        }
    }

    public function listAction(Request $request)
    {
        $formFactory = $this->container->get('form.factory');

        /** @var $fb \Symfony\Component\Form\FormBuilder */
        $fb = $formFactory->createNamedBuilder('transfer', 'form', null, [
            'csrf_protection'    => false,
            'translation_domain' => 'transfer_payment',
        ])
            ->add('transferred_from', 'date', [
                'label'       => 'Период оплаты начало',
                'years'       => range(2010, date('Y') + 1),
                'required'    => false,
                'empty_value' => '--',
            ])
            ->add('transferred_to', 'date', [
                'label'       => 'Период оплаты конец',
                'years'       => range(2010, date('Y') + 1),
                'required'    => false,
                'empty_value' => '--',
            ])
            ->add('user_last_name', 'text', [
                'label'    => 'Фамилия',
                'required' => false,
            ])
            ->add('user_first_name', 'text', [
                'label'    => 'Имя',
                'required' => false,
            ])
            ->add('user_patronymic', 'text', [
                'label'    => 'Отчество',
                'required' => false,
            ])
            ->add('code_listener', 'text', [
                'label'    => 'Код слушателя',
                'required' => false,
            ])
            ->add('region', 'entity', [
                'label'       => 'Регион',
                'class'       => 'AppBundle:Region',
                'required'    => false,
                'empty_value' => ' - Выберите - ',
            ])
            ->add('condition', 'choice', [
                'label'       => 'Тип услуги',
                'required'    => false,
                'empty_value' => ' - Выберите - ',
                'choices'     => [
                    't1'      => 'Т1 - Оплата 1',
                    't2'      => 'Т2 - Оплата 2',
                    't12'     => 'Т1,2 - Оплата 1,2',
                ],
            ])
            ->add('s_id', 'text', [
                'label'    => 'Код транзакции',
                'required' => false,
            ])
            ->add('s_type', 'choice', [
                'label'       => 'Касса',
                'required'    => false,
                'empty_value' => ' - Выберите - ',
                'choices'     => [
                    'psb'       => 'ДО ПСБ',
                    'robokassa' => 'ДО Робокасса',
                    'api'       => 'АО ПСБ',
                ],
            ])
        ;
        $fb->setMethod('get');
        $filterForm = $fb->getForm();

        $filterForm->handleRequest($request);

        $all_services_true = [];
        $services = $this->em->getRepository('AppBundle:Service')->createQueryBuilder('s')
            ->getQuery()->getArrayResult();
        foreach ($services as $service) {
            if ($service['type']) {
                $all_services_true[] = $service['id'];
            }
        }

        $qb = $this->em->getRepository('PaymentBundle:Log')->createQueryBuilder('pl')
            ->andWhere('pl.paid = 1')
            ->andWhere('pl.s_id IS NOT NULL')
            ->andWhere('pl.s_type IS NOT NULL')
            ->andWhere('pl.transferred_at IS NOT NULL')
            ->leftJoin('pl.user', 'u')->addSelect('u')
            ->orderBy('pl.transferred_at', 'DESC')
            ->addGroupBy('pl.id')
            ->addSelect('pl.sum AS summator')
        ;

        $data               = $filterForm->get('transferred_from')->getData();
        $total['date_from'] = null;
        if ($data) {
            $qb->andWhere('pl.transferred_at >= :transferred_from')->setParameter('transferred_from', $data);
            $total['date_from'] = $data;
        }

        $data             = $filterForm->get('transferred_to')->getData();
        $total['date_to'] = null;
        if ($data) {
            $qb->andWhere('pl.transferred_at <= :transferred_to')->setParameter('transferred_to', $data);
            $total['date_to'] = $data;
        }

        $data = $filterForm->get('user_first_name')->getData();
        if ($data) {
            $qb->andWhere('u.first_name = :first_name')->setParameter('first_name', $data);
        }

        $data = $filterForm->get('user_last_name')->getData();
        if ($data) {
            $qb->andWhere('u.last_name = :last_name')->setParameter('last_name', $data);
        }

        $data = $filterForm->get('user_patronymic')->getData();
        if ($data) {
            $qb->andWhere('u.patronymic = :patronymic')->setParameter('patronymic', $data);
        }

        $data = $filterForm->get('code_listener')->getData();
        if ($data) {
            $qb->andWhere('u.paradox_id = :paradox_id')->setParameter('paradox_id', $data);
        }

        $data = $filterForm->get('region')->getData();
        if ($data) {
            $qb->leftJoin('u.region', 'r')
                ->andWhere('r.id = :region')->setParameter('region', $data);
        }

        $data = $filterForm->get('condition')->getData();

        $pay1 = 'pl.comment LIKE :categories1
                AND pl.comment NOT LIKE :not_paid1
                AND pl.comment NOT LIKE :not_services1';

        $pay2 = 'pl.comment LIKE :services2
                AND pl.comment NOT LIKE :categories2';

        $pay12 = 'pl.comment LIKE :categories12 
                AND pl.comment NOT LIKE :not_paid12
                AND pl.comment LIKE :services12';

        if ($data == 't1') {
            $qb->andWhere('('.$pay1.')')
                ->setParameter(':categories1', '%"categories"%')
                ->setParameter(':not_paid1', '%"paid"%')
                ->setParameter(':not_services1', '%"services"%')
            ;
        } elseif ($data == 't2') {
            $qb->andWhere('('.$pay2.')')
                ->setParameter(':services2', '%"services":"'.implode(',', $all_services_true).'"%')
                ->setParameter(':categories2', '%"categories"%')
            ;
        } elseif ($data == 't12') {
            $qb->andWhere('('.$pay12.')')
                ->setParameter(':categories12', '%"categories"%')
                ->setParameter(':not_paid12', '%"paid"%')
                ->setParameter(':services12', '%"services":"'.implode(',', $all_services_true).'"%')
            ;
        } else {
            $qb->andWhere('(('.$pay1.') OR ('.$pay2.') OR ('.$pay12.'))')
                ->setParameter(':categories1', '%"categories"%')
                ->setParameter(':not_paid1', '%"paid"%')
                ->setParameter(':not_services1', '%"services"%')
                ->setParameter(':services2', '%"services":"'.implode(',', $all_services_true).'"%')
                ->setParameter(':categories2', '%"categories"%')
                ->setParameter(':categories12', '%"categories"%')
                ->setParameter(':not_paid12', '%"paid"%')
                ->setParameter(':services12', '%"services":"'.implode(',', $all_services_true).'"%')
            ;
        }

        $data = $filterForm->get('s_id')->getData();
        if ($data) {
            $qb->andWhere('pl.s_id = :s_id')->setParameter('s_id', $data);
        }

        $data = $filterForm->get('s_type')->getData();
        if ($data) {
            $qb->andWhere('pl.s_type = :type')->setParameter('type', $data);
        }

        $isFilterFormErrors = false;
        foreach ($filterForm as $child) {
            $isFilterFormErrors = $isFilterFormErrors || (bool)$child->getErrors();
        }

        $summator         = new Summator($qb);
        $total['all_sum'] = $summator->getSum();

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setMaxPerPage(20);
        $pagerfanta->setCurrentPage($request->get('page', 1));

        $total['count'] = $pagerfanta->count();

        $results    = $pagerfanta->getCurrentPageResults();
        $paysByDate = [];
        foreach ($results as $result) {
            /** @var $log Log */
            $log             = $result[0];
            $transferredDate = $log->getTransferredAt()->format('Y-m-d');

            if (!isset($paysByDate[$transferredDate])) {
                $qbc  = clone $qb;
                $info = $qbc->select('COUNT(pl.id) as subcount')
                    ->addSelect('SUM(pl.sum) AS subsum')
                    ->addSelect('DATE(pl.transferred_at) AS transferred_date')
                    ->groupBy('transferred_date')
                    ->andWhere('DATE(pl.transferred_at) = :transferred_date')
                    ->setParameter('transferred_date', $transferredDate)
                    ->getQuery()->getResult();

                $paysByDate[$transferredDate] = $info[0];
            }

            $log_checked = array(
                'id_transaction' => $log->getId(),
                'updated_at'     => $log->getUpdatedAt(),
                's_id'           => $log->getSId(),
                's_type'         => $log->getSType(),
                'last_name'      => $log->getUser()->getLastName(),
                'first_name'     => $log->getUser()->getFirstName(),
                'patronymic'     => $log->getUser()->getPatronymic(),
                'paradox_id'     => $log->getUser()->getParadoxId(),
                'sum'            => $log->getSum(),
                'user_id'        => $log->getUser()->getId(),
                'user_full_name' => $log->getUser()->getFullName(),
                'transferred_at' => $log->getTransferredAt(),
                'region'         => $log->getUser()->getRegion(),
                'service_type'    => '',
            );

            $comment = json_decode($log->getComment(), true);
            $services_first_two = false;

            if (isset($comment['services'])) {
                $ids = explode(',', $comment['services']);
                if ($ids == $all_services_true) {
                    $log_checked['service_type'] = 'Т2';
                    $services_first_two = true;
                }
            }

            if (isset($comment['categories'])) {
                if (!isset($comment['services'])) {
                    if (!isset($comment['paid'])) {
                        $log_checked['service_type'] = 'Т1';
                    }
                } elseif ($services_first_two) {
                    $log_checked['service_type'] = 'Т1,2';
                }
            }

            $paysByDate[$transferredDate]['logs'][] = $log_checked;

        }

        return $this->render('AppBundle:Admin/CheckMoney:list_one_two.html.twig', [
            'pagerfanta'            => $pagerfanta,
            'filter_form'           => $filterForm->createView(),
            'is_filter_form_errors' => $isFilterFormErrors,
            'total'                 => $total,
            'pays_by_date'          => $paysByDate,
        ]);
    }

    public function itemAction(Request $request)
    {
        $logId = $request->get('log_id');
        if ($logId) {
            $log = $this->em->find('PaymentBundle:Log', $logId);
            if (!$log) {
                throw $this->createNotFoundException('Not log for "'.$logId.' log_id.');
            }
        } else {
            throw $this->createNotFoundException('Not log_id.');
        }

        $legalReferer = $this->getRequest()->headers->get('referer');
        $route   = $this->generateUrl('admin_list_check_payment_one_two_in_paradox', [], true);

        $url = $route == substr($legalReferer, 0, strlen($route)) ? $legalReferer : $route;

        $all_services_true = [];
        $comment = json_decode($log->getComment(), true);
        $service_type = '';
        $services_first_two = false;

        $services = $this->em->getRepository('AppBundle:Service')->createQueryBuilder('s')
            ->getQuery()->getArrayResult();
        foreach ($services as $service) {
            if ($service['type']) {
                $all_services_true[] = $service['id'];
            }
        }

        if (isset($comment['services'])) {
            $ids = explode(',', $comment['services']);
            if ($ids == $all_services_true) {
                $service_type = 'Т2';
                $services_first_two = true;
            }
        }

        if (isset($comment['categories'])) {
            if (!isset($comment['services'])) {
                if (!isset($comment['paid'])) {
                    $service_type = 'Т1';
                }
            } elseif ($services_first_two) {
                $service_type = 'Т1,2';
            }
        }

        return $this->render('AppBundle:Admin/CheckMoney:check_item_one_two.html.twig', [
            'log'           => $log,
            'list_route'    => $url,
            'service_type'  => $service_type,
        ]);
    }
}
