<?php

namespace My\AppBundle\Controller\Admin;

use My\AppBundle\Doctrine\ORM\Tools\Summator;
use My\PaymentBundle\Entity\Log;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CheckPaymentController extends Controller
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
            ->add('code_driving_package', 'text', [
                'label'    => 'Код пакета талонов',
                'required' => false,
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
                'label'       => 'Тип пакета',
                'required'    => false,
                'empty_value' => ' - Выберите - ',
                'choices'     => [
                    'main'       => 'ВОСН - Вождение, основной талон',
                    'additional' => 'ВДОП - Вождение, дополнительный талон',
                ],
            ])
            ->add('s_id', 'text', [
                'label'    => 'Код слушателя',
                'required' => false,
            ])
            ->add('s_type', 'choice', [
                'label'       => 'Касса',
                'required'    => false,
                'empty_value' => ' - Выберите - ',
                'choices'     => [
                    'psb'       => 'ДО ПСБ',
                    'robokassa' => 'ДО Робокасса',
                ],
            ])
            ;

        $fb->setMethod('get');
        $filterForm = $fb->getForm();
        $filterForm->handleRequest($request);

        $qb = $this->em->getRepository('PaymentBundle:Log')->createQueryBuilder('pl')
            ->andWhere('pl.paid = 1')
            ->andWhere('pl.s_type IS NOT NULL')
            ->andWhere('pl.package IS NOT NULL')
            ->andWhere('pl.transferred_at IS NOT NULL')
            ->leftJoin('pl.user', 'u')->addSelect('u')
            ->leftJoin('pl.package', 'dp')->addSelect('dp')
            ->leftJoin('dp.condition', 'c')->addSelect('c')
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

        $data = $filterForm->get('code_driving_package')->getData();
        if ($data) {
            $qb->andWhere('dp = :number')->setParameter('number', $data);
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
        if ($data == 'main') {
            $qb->andWhere('c.is_primary = 1');
        } elseif ($data == 'additional') {
            $qb->andWhere('c.is_primary = 0');
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
            $isFilterFormErrors = $isFilterFormErrors || (bool) $child->getErrors();
        }

        $summator         = new Summator($qb);
        $total['all_sum'] = $summator->getSum();

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
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

            $paysByDate[$transferredDate]['logs'][] = $log;
        }

        return $this->render('AppBundle:Admin/CheckMoney:list.html.twig', [
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
        $route   = $this->generateUrl('admin_list_check_payment_in_paradox', [], true);

        $url = $route == substr($legalReferer, 0, strlen($route)) ? $legalReferer : $route;

        return $this->render('AppBundle:Admin/CheckMoney:check_item.html.twig', [
            'log'        => $log,
            'list_route' => $url,
        ]);
    }
}
