<?php

namespace My\AppBundle\Controller\Admin;

use My\AppBundle\Doctrine\ORM\Tools\Summator;
use My\AppBundle\Entity\User;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class TransferPaymentController extends Controller
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
                ],
            ])
            ->add('condition', 'choice', [
                'label'       => 'Тип пакета',
                'required'    => false,
                'empty_value' => ' - Выберите - ',
                'choices'     => [
                    'main'       => 'Основной',
                    'additional' => 'Дополнительный',
                ],
            ])
        ;

        $fb->setMethod('get');
        $filter_form = $fb->getForm();
        $filter_form->handleRequest($request);

        $qb = $this->em->getRepository('PaymentBundle:Log')->createQueryBuilder('pl')
            ->andWhere('pl.paid = 1')
            ->andWhere('pl.s_type IS NOT NULL')
            ->andWhere('pl.package IS NOT NULL')
            ->andWhere('pl.transferred_at IS NULL')
            ->leftJoin('pl.user', 'u')->addSelect('u')
            ->leftJoin('pl.package', 'dp')->addSelect('dp')
            ->leftJoin('dp.condition', 'c')->addSelect('c')
            ->leftJoin('dp.tickets', 't')
            ->addSelect('COUNT(t) AS tickets')
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

        $data = $filter_form->get('code_driving_package')->getData();
        if ($data) {
            $qb->andWhere('dp = :number')->setParameter('number', $data);
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
        if ($data) {
            $qb->andWhere('u.moderated = :moderated')->setParameter('moderated', true);
        }

        $data = $filter_form->get('paradox')->getData();
        if ($data) {
            $qb->andWhere('u.paradox_id IS NOT NULL');
        }

        $data = $filter_form->get('s_type')->getData();
        if ($data) {
            $qb->andWhere('pl.s_type = :type')->setParameter('type', $data);
        }

        $data = $filter_form->get('condition')->getData();
        if ($data == 'main') {
            $qb->andWhere('c.is_primary = 1');
        } elseif ($data == 'additional') {
            $qb->andWhere('c.is_primary = 0');
        }

        $summator = new Summator($qb);
        $allSum = $summator->getSum();

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($request->get('page', 1));

        return $this->render('AppBundle:Admin/TransferMoney:list.html.twig', array(
            'pagerfanta'  => $pagerfanta,
            'filter_form' => $filter_form->createView(),
            'all_sum'     => $allSum,
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
        $route = $this->generateUrl('admin_list_transfer_payment_in_paradox', [], true);
        if ($route == substr($referer, 0, strlen($route))) {
            $session->set('list_referer', $referer);
        }

        $form_factory = $this->container->get('form.factory');
        $fb = $form_factory->createNamedBuilder('transfer');
        $form = $fb->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var  $user User */
            $user = $log->getUser();
            if ($user->getParadoxId()) {
                $log->setTransferredAt(new \DateTime());
                $log->setAdmin($this->getUser());

                $this->em->persist($log);
                $this->em->flush();

                $session->getFlashBag()->add('success', 'success_transferred');

                if ($session->has('list_referer')) {
                    $url = $session->get('list_referer');
                    $session->remove('list_referer');
                } else {
                    $url = $this->generateUrl('admin_list_transfer_payment_in_paradox');
                }

                return $this->redirect($url);
            }
        }

        if ($session->has('list_referer')) {
            $url = $session->get('list_referer');
        } else {
            $url = $this->generateUrl('admin_list_transfer_payment_in_paradox');
        }

        return $this->render('AppBundle:Admin/TransferMoney:transfers.html.twig', [
            'log'        => $log,
            'form'       => $form->createView(),
            'list_route' => $url,
        ]);
    }
}
