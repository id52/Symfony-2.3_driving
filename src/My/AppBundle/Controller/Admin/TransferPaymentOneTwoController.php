<?php

namespace My\AppBundle\Controller\Admin;

use My\AppBundle\Doctrine\ORM\Tools\Summator;
use My\AppBundle\Entity\User;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;

class TransferPaymentOneTwoController extends Controller
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
        $form_factory = $this->container->get('form.factory');

        /** @var $fb \Symfony\Component\Form\FormBuilder */
        $fb = $form_factory->createNamedBuilder('transfer', 'form', null, array(
            'csrf_protection'    => false,
            'translation_domain' => 'transfer_payment',
        ))
            ->add('payment_period_from', 'date', [
                'label'       => 'Период оплаты начало',
                'years'       => range(2010, date('Y') + 1),
                'required'    => false,
                'empty_value' => '--',
            ])
            ->add('payment_period_to', 'date', [
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
            ->add('is_confirmed', 'choice', [
                'label'       => 'Подтвержден',
                'required'    => false,
                'empty_value' => ' - Выберите - ',
                'choices'     => [
                    'confirmed'     => 'да',
                    'not_confirmed' => 'нет',

                ],
            ])
            ->add('paradox', 'choice', [
                'label'       => 'Paradox',
                'required'    => false,
                'empty_value' => ' - Выберите - ',
                'choices'     => [
                    'yes' => 'да',
                    'no'  => 'нет',
                ],
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
            ->add('condition', 'choice', [
                'label'       => 'Тип услуги',
                'required'    => false,
                'empty_value' => ' - Выберите - ',
                'choices'     => [
                    't1'    => 'Т1 - Оплата 1',
                    't2'    => 'Т2 - Оплата 2',
                    't12'   => 'Т1,2 - Оплата 1,2',
                ],
            ])
        ;
        $fb->setMethod('get');
        $filter_form = $fb->getForm();

        $filter_form->handleRequest($request);

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
            ->andWhere('pl.transferred_at IS NULL')
            ->leftJoin('pl.user', 'u')->addSelect('u')
            ->orderBy('pl.created_at', 'DESC')
            ->addGroupBy('pl.id')
            ->addSelect('pl.sum AS summator')
        ;

        $data = $filter_form->get('payment_period_from')->getData();
        if ($data) {
            $qb->andWhere('pl.created_at >= :create_from')->setParameter('create_from', $data);
        }

        $data = $filter_form->get('payment_period_to')->getData();
        if ($data) {
            $qb->andWhere('pl.created_at <= :create_to')->setParameter('create_to', $data);
        }

        $data = $filter_form->get('user_first_name')->getData();
        if ($data) {
            $qb->andWhere('u.first_name = :first_name')->setParameter('first_name', $data);
        }

        $data = $filter_form->get('user_last_name')->getData();
        if ($data) {
            $qb->andWhere('u.last_name = :last_name')->setParameter('last_name', $data);
        }

        $data = $filter_form->get('user_patronymic')->getData();
        if ($data) {
            $qb->andWhere('u.patronymic = :patronymic')->setParameter('patronymic', $data);
        }

        $data = $filter_form->get('code_listener')->getData();
        if ($data) {
            $qb->andWhere('u.paradox_id = :paradox_id')->setParameter('paradox_id', $data);
        }

        $data = $filter_form->get('region')->getData();
        if ($data) {
            $qb->leftJoin('u.region', 'r')
                ->andWhere('r.id = :region')->setParameter('region', $data);
        }

        $data = $filter_form->get('is_confirmed')->getData();
        if ($data == 'confirmed') {
            $qb->andWhere('u.moderated = :moderated')->setParameter(':moderated', true);
        } elseif ($data == 'not_confirmed') {
            $qb->andWhere('u.moderated = :not_moderated')->setParameter(':not_moderated', false);
        }

        $data = $filter_form->get('paradox')->getData();
        if ($data == 'yes') {
            $qb->andWhere('u.paradox_id IS NOT NULL');
        } elseif ($data == 'no') {
            $qb->andWhere('u.paradox_id IS NULL');
        }

        $data = $filter_form->get('s_type')->getData();
        if ($data) {
            $qb->andWhere('pl.s_type = :type')->setParameter('type', $data);
        }

        $data = $filter_form->get('condition')->getData();

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

        $summator = new Summator($qb);
        $allSum = $summator->getSum();

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setMaxPerPage(20);
        $pagerfanta->setCurrentPage($request->get('page', 1));

        $transactions = $pagerfanta->getCurrentPageResults();
        $transactions_transfer = [];

        foreach ($transactions as $transaction) { /** @var $transaction \My\PaymentBundle\Entity\Log */
            $transaction = $transaction[0];

            $log_transactions = [
                'created_at'     => $transaction->getCreatedAt(),
                's_type'         => $transaction->getSType(),
                's_id'           => $transaction->getSId(),
                'service_type'   => '',
                'sum'            => $transaction->getSum(),
                'user_id'        => $transaction->getUser()->getId(),
                'user_full_name' => $transaction->getUser()->getFullName(),
                'paradox_id'     => $transaction->getUser()->getParadoxId(),
                'region'         => $transaction->getUser()->getRegion(),
                'moderated'      => $transaction->getUser()->getModerated(),
                'log_id'         => $transaction->getId(),
            ];

            $comment = json_decode($transaction->getComment(), true);
            $services_first_two = false;

            if (isset($comment['services'])) {
                $ids = explode(',', $comment['services']);
                if ($ids == $all_services_true) {
                    $log_transactions['service_type'] = 'Т2';
                    $services_first_two = true;
                }
            }

            if (isset($comment['categories'])) {
                if (!isset($comment['services'])) {
                    if (!isset($comment['paid'])) {
                        $log_transactions['service_type'] = 'Т1';
                    }
                } elseif ($services_first_two) {
                    $log_transactions['service_type'] = 'Т1,2';
                }
            }

            $transactions_transfer [] = $log_transactions;
        }

        return $this->render('AppBundle:Admin/TransferMoney:list_one_two.html.twig', array(
            'pagerfanta'            => $pagerfanta,
            'filter_form'           => $filter_form->createView(),
            'all_sum'               => $allSum,
            'transactions_transfer' => $transactions_transfer,
        ));
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

        $session = $this->get('session');

        $referer = $this->getRequest()->headers->get('referer');
        $route = $this->generateUrl('admin_list_transfer_payment_one_two_in_paradox', [], true);
        if ($route == substr($referer, 0, strlen($route))) {
            $session->set('list_referer', $referer);
        }

        $form_factory = $this->container->get('form.factory');
        $fb = $form_factory->createNamedBuilder('transfer');
        $fb->add('date', 'datetime', [
            'years'       => range(date('Y') - 2, date('Y') + 2),
            'data'        => new \DateTime(),
            'constraints' => new NotBlank(),
        ]);
        $form = $fb->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var  $user User */
            $user = $log->getUser();
            if ($user->getParadoxId()) {
                $date = $form->get('date')->getData();
                $log->setTransferredAt($date);
                $log->setAdmin($this->getUser());

                $this->em->persist($log);
                $this->em->flush();

                $session->getFlashBag()->add('success', 'success_transferred');

                if ($session->has('list_referer')) {
                    $url = $session->get('list_referer');
                    $session->remove('list_referer');
                } else {
                    $url = $this->generateUrl('admin_list_transfer_payment_one_two_in_paradox');
                }

                return $this->redirect($url);
            }
        }

        if ($session->has('list_referer')) {
            $url = $session->get('list_referer');
        } else {
            $url = $this->generateUrl('admin_list_transfer_payment_one_two_in_paradox');
        }

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

        return $this->render('AppBundle:Admin/TransferMoney:transfers_one_two.html.twig', [
            'log'          => $log,
            'form'         => $form->createView(),
            'list_route'   => $url,
            'service_type' => $service_type,
        ]);
    }
}
