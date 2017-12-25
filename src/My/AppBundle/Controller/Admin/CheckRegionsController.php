<?php

namespace My\AppBundle\Controller\Admin;

use My\AppBundle\Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;

class CheckRegionsController extends Controller
{
    protected $routerList = 'admin_check_regions';
    protected $listFields = array('email', 'fullName');

    public function listAction(Request $request, $type = null)
    {
        if (($this->get('security.context')->isGranted('ROLE_MOD_MANAGER')) == false) {
            throw $this->createNotFoundException();
        }

        /** @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $qb = $em->getRepository('AppBundle:User')->createQueryBuilder('u')
            ->leftJoin('u.region', 'r')
            ->andWhere('r.filial_not_existing = :exist')->setParameter('exist', true)
            ->leftJoin('u.api_question_log', 'aql')->addSelect('aql')
            ->leftJoin('u.user_stat', 'us')->addSelect('us')
        ;

        $filterFields = array(
            'reg'                     => 'reg',
            'first_paid'              => 'first_paid',
            'second_paid'             => 'second_paid',
            'driving_paid'            => 'driving_paid',
            'load_document'           => 'load_document',
            'driving_additional_paid' => 'driving_additional_paid',
            'driving'                 => 'driving',
            'driving_additional'      => 'driving_additional',
            'execute_documents'       => 'execute_documents',
            'get_documents'           => 'get_documents',
            'graduated'               => 'graduated',
            'all'                     => 'all',
        );

        $form_factory = $this->container->get('form.factory');
        /** @var $fb \Symfony\Component\Form\FormBuilder */
        $fb = $form_factory->createNamedBuilder('', 'form', array(), array(
                'csrf_protection' => false,
            ))
            ->add('filter_condition', 'choice', array(
                'required'    => false,
                'empty_value' => 'choose_option',
                'choices' => $filterFields,
            ))
        ;
        $fb->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $event->stopPropagation();
        }, 900);
        $fb->setMethod('get');
        $filter = $fb->getForm();

        $filter->handleRequest($request);
        $data = $filter->get('filter_condition')->getData();
        if ($type!= null && $data == null) {
            $data = $type;
        }

        switch ($data) {
            case 'reg':
                $qb->andWhere('u.phone_mobile_status = :status')->setParameter('status', 'confirmed')
                    ->andWhere('u.enabled = :enabled')->setParameter('enabled', true)
                    ->andWhere('u.payment_1_paid IS NULL')
                    ->andWhere('u.payment_2_paid IS NULL');
                break;
            case 'first_paid':
                $qb->andWhere('u.payment_1_paid IS NOT NULL')
                    ->andWhere('u.payment_2_paid IS NULL');
                break;
            case 'second_paid':
                $qb->andWhere('u.payment_1_paid IS NOT NULL')
                    ->andWhere('u.payment_2_paid IS NOT NULL')
                    ->leftJoin('u.packages', 'p')
                    ->andWhere('p.sale_at IS NOT NULL')
                    ->andHaving('COUNT(p.number) = 0')
                    ->groupBy('u.id');
                break;
            case 'driving_paid':
                $qb->leftJoin('u.packages', 'p')
                    ->andWhere('p.sale_at IS NOT NULL')
                    ->andHaving('COUNT(p.number) = 1')
                    ->groupBy('u.id');
                break;
            case 'load_document':
                $qb ->leftJoin('u.documents', 'doc', 'WITH', 'doc.status IS NULL')
                    ->andHaving('COUNT(doc.id) > 0')
                    ->groupBy('u.id');
                break;
            case 'driving_additional_paid':
                $qb->leftJoin('u.packages', 'p')
                    ->andWhere('p.sale_at IS NOT NULL')
                    ->andHaving('COUNT(p.number) > 1')
                    ->groupBy('u.id');
                break;
            case 'driving':
                $qb->leftJoin('u.packages', 'p')
                    ->andWhere('p.sale_at IS NOT NULL')
                    ->andWhere('p.status = :status')->setParameter('status', 'received')
                    ->leftJoin('p.condition', 'c')
                    ->andWhere('c.is_primary = :primary')->setParameter('primary', true)
                    ->leftJoin('p.tickets', 't')
                    ->andWhere('t.rating IS NULL');
                break;
            case 'driving_additional':
                $qb->leftJoin('u.packages', 'p')
                    ->andWhere('p.sale_at IS NOT NULL')
                    ->andWhere('p.status = :status')->setParameter('status', 'received')
                    ->leftJoin('p.condition', 'c')
                    ->andWhere('c.is_primary = :primary')->setParameter('primary', false)
                    ->leftJoin('p.tickets', 't')
                    ->andWhere('t.rating IS NULL');
                break;
            case 'execute_documents':
                $qb->leftJoin('u.final_exams_logs', 'fel')
                    ->andWhere('fel.passed = :passed')->setParameter('passed', true)
                    ->leftJoin('u.packages', 'p')
                    ->andWhere('p.sale_at IS NOT NULL')
                    ->leftJoin('p.tickets', 't', 'WITH', 't.rating IS NULL')
                    ->andHaving('COUNT(t.id)=0');
                break;
            case 'get_documents':
                $qb->andWhere('u.final_doc_status = :f_status')->setParameter('f_status', 'doc_is_done');
                break;
            case 'graduated':
                $qb->andWhere('u.final_doc_status = :f_status')->setParameter('f_status', 'doc_is_picked');
                break;
            case 'all':
                $qb->addOrderBy('u.created_at', 'DESC');
                break;
            default:
                $qb->addOrderBy('u.created_at', 'ASC');
        }

        $categories = $em->getRepository('AppBundle:Category')->findAll();
        $services = $em->getRepository('AppBundle:Service')->findAll();

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setMaxPerPage(50);
        $pagerfanta->setCurrentPage($this->getRequest()->get('page'));

        // добавил проверку на count и null, так как COUNT в запросе "execute_documents" дает массив со значением null
        // а в других запросах массив пустой
        if (count($pagerfanta->getIterator()) > 0 && $pagerfanta->getIterator()[0] != null) {
            $existUser = true;
            foreach ($pagerfanta->getIterator() as $user) {
                /** @var $user \My\AppBundle\Entity\User */
                $user->forcePromoInfo($categories, $services);
            }
        } else {
            $existUser = false;
        }

        return $this->render('AppBundle:Admin:CheckRegions\list.html.twig', array(
            'pagerfanta'  => $pagerfanta,
            'list_fields' => $this->listFields,
            'filter_form' => $filter->createView(),
            'exist_user'  => $existUser,
        ));
    }

    public function userCardViewAction(Request $request, $id)
    {
        if (($this->get('security.context')->isGranted('ROLE_MOD_MANAGER')) == false) {
            throw $this->createNotFoundException();
        }

        /** @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $userRepo = $em->getRepository('AppBundle:User');
        $user = $userRepo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User for id "'.$id.'" not found.');
        }

        $failDocs = $em->getRepository('AppBundle:Document')->createQueryBuilder('d')
            ->andWhere('d.status = :status')->setParameter('status', 'fail')
            ->addSelect('MAX(d.updated_at) AS lastDate')
            ->getQuery()->execute();

        $adminDeleteDocs = false;
        if ($failDocs[0][0]) {
            $date = new \DateTime($failDocs[0]['lastDate']);
            $confirmedDocs = $em->getRepository('AppBundle:Document')->createQueryBuilder('d')
                ->andWhere('d.status = :status')->setParameter('status', 'confirm')
                ->andWhere('d.updated_at > :date')->setParameter('date', $date)
                ->getQuery()->execute();
            if ($confirmedDocs) {
                $adminDeleteDocs = true;
            }
        }

        $userDocs = $em->getRepository('AppBundle:Document')->createQueryBuilder('d')
            ->andWhere('d.user = :user')->setParameter('user', $user)
            ->addOrderBy('d.type')
            ->getQuery()->execute();

        $userMedDocs = $em->getRepository('AppBundle:Document')->findBy(array(
            'user' => $user,
            'type' => 'medical_certificate',
        ));
        $userContractDocs = $em->getRepository('AppBundle:Document')->findBy(array(
            'user' => $user,
            'type' => 'contract',
        ));
        $userPassportDocs = $em->getRepository('AppBundle:Document')->findBy(array(
            'user' => $user,
            'type' => 'passport',
        ));

        $confirmedMedDocs = false;
        $confirmedContractDocs = false;
        $confirmedPassportDocs = false;

        if (count($userMedDocs) > 0) {
            foreach ($userMedDocs as $userDoc) {
                /** @var  $userDoc \My\AppBundle\Entity\Document */
                if ($userDoc->getStatus() == 'confirm') {
                    $confirmedMedDocs = true;
                }
            }
        }

        if (count($userContractDocs) > 0) {
            foreach ($userContractDocs as $userDoc) {
                /** @var  $userDoc \My\AppBundle\Entity\Document */
                if ($userDoc->getStatus() == 'confirm') {
                    $confirmedContractDocs = true;
                }
            }
        }

        if (count($userPassportDocs) > 0) {
            foreach ($userPassportDocs as $userDoc) {
                /** @var  $userDoc \My\AppBundle\Entity\Document */
                if ($userDoc->getStatus() == 'confirm') {
                    $confirmedPassportDocs = true;
                }
            }
        }

        $confirmedDocs = false;
        $confirmDocsExist = false;
        if ($confirmedMedDocs && $confirmedContractDocs && $confirmedPassportDocs) {
            $confirmedDocs = true;
            $confirmDocsExist = true;
        } elseif ($confirmedMedDocs || $confirmedContractDocs || $confirmedPassportDocs) {
            $confirmDocsExist = true;
        }

        if ($request->isMethod('post')) {
            $notify = $this->get('app.notify');

            $sendConfirmDocsMail = $request->get('confirm_docs_mail');
            $finalDocumentsStatus = $request->get('final_document_status');
            $documentsStatus = $request->get('document_status');
            $documentComment  = $request->get('document_comment');
            $ticketsStatus = $request->get('ticket_status');

            if ($sendConfirmDocsMail && $confirmedMedDocs && $confirmedContractDocs && $confirmedPassportDocs) {
                $notify->sendDocsIsConfirm($user);
                $user->setConfirmDocsIsSend(true);
            }

            if (!is_null($finalDocumentsStatus)) {
                $status = $finalDocumentsStatus[0];
                if (($status == 'doc_is_done' || $status == 'doc_is_picked') && $confirmedDocs) {
                    $user->setFinalDocStatus($status);

                    if ($status == 'doc_is_picked') {
                        $user->setFinalDocGetAt(new \DateTime());
                        $moderators = $user->getFinalDocModerator();
                        foreach ($moderators as $moderator) {
                            $user->removeFinalDocModerator($moderator);
                        }
                        $user->addFinalDocModerator($this->getUser());
                    }
                    $em->persist($user);

                    if ($status == 'doc_is_done') {
                        $notify->sendDocIsDone($user);
                    }
                }
            }

            $ids = array();
            if (!is_null($documentsStatus)) {
                foreach ($documentsStatus as $id => $docStatus) {
                    $ids[] = $id;
                }
            }

            $sendFailDocsMail = false;
            if (count($ids) > 0) {
                $documents = $em->getRepository('AppBundle:Document')->createQueryBuilder('d')
                    ->andWhere('d.id IN (:ids)')->setParameter('ids', $ids)
                    ->getQuery()->execute();

                foreach ($documents as $document) {
                    /** @var $document \My\AppBundle\Entity\Document */
                    $document->setReSent(false);

                    if ((isset($documentComment[$document->getId()]) && !$document->getStatus())
                        || (isset($documentComment[$document->getId()]) && $document->getReSent())
                    ) {
                        $document->setComment($documentComment[$document->getId()]);
                    }
                    if (isset($documentsStatus[$document->getId()])) {
                        $type = key($documentsStatus[$document->getId()]);
                        $document->setStatus($documentsStatus[$document->getId()][$type]);
                        $confirmDocsExist = true;

                        if ($documentsStatus[$document->getId()][$type] == 'fail') {
                            $sendFailDocsMail = true;
                        }
                    }

                    $em->persist($document);
                }
            }

            if ($sendFailDocsMail) {
                $notify->sendDocsIsFail($user);
            }

            if (!is_null($ticketsStatus) && $confirmedDocs) {
                $numbers = array();
                if (!is_null($ticketsStatus)) {
                    foreach ($ticketsStatus as $number => $status) {
                        $numbers[] = $number;
                    }
                }
                $userPackages = $em->getRepository('AppBundle:DrivingPackage')->createQueryBuilder('p')
                    ->andWhere('p.number IN (:numbers)')->setParameter('numbers', $numbers)
                    ->getQuery()->execute();

                foreach ($userPackages as $userPackage) {
                    /** @var $userPackage \My\AppBundle\Entity\DrivingPackage */
                    if (isset($ticketsStatus[$userPackage->getNumber()])) {
                        if ($ticketsStatus[$userPackage->getNumber()] == 'sended') {
                            $notify->sendTicketsIsSended($user);
                        }

                        $userPackage->setStatus($ticketsStatus[$userPackage->getNumber()]);
                        $userPackage->setModerator($this->getUser());

                        if ($ticketsStatus[$userPackage->getNumber()] == 'given_into_hands') {
                            $userPackage->setReceivedAt(new \DateTime());
                        }
                    }
                    $em->persist($userPackage);
                }
            }

            $em->flush();
        }

        $logs = $em->getRepository('PaymentBundle:Log')->createQueryBuilder('l')
            ->andWhere('l.user = :user')->setParameter(':user', $user)
            ->andWhere('l.paid = :paid')->setParameter(':paid', true)
            ->leftJoin('l.package', 'p')
            ->andWhere('p.user = :user')->setParameter('user', $user)
            ->addSelect('p')
            ->leftJoin('p.tickets', 't')
            ->addSelect('t')
            ->addOrderBy('l.updated_at', 'ASC')
            ->getQuery()->getResult();

        $packagesData = array();
        foreach ($logs as $log) {
            /** @var  $log \My\PaymentBundle\Entity\Log */
            $package = $log->getPackage();
            $completed = true;
            $tickets = $package->getTickets();

            foreach ($tickets as $ticket) {
                /** @var $ticket \My\AppBundle\Entity\DrivingTicket */
                if (is_null($ticket->getRating())) {
                    $completed = false;
                    break;
                }
            }
            $packagesData[$package->getNumber()]['complited'] = $completed;
            $packagesData[$package->getNumber()]['tickets'] = $tickets;
        }

        $version = $em->getRepository('AppBundle:TrainingVersion')->createQueryBuilder('v')
            ->andWhere('v.category = :category')->setParameter(':category', $user->getCategory())
            ->andWhere('v.start_date <= :start_date')
            ->setParameter(':start_date', date_format($user->getCreatedAt(), 'Y-m-d'))
            ->addOrderBy('v.start_date', 'DESC')
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();

        $subjects_repository = $em->getRepository('AppBundle:Subject');
        $subjects = $subjects_repository->findAllAsArray($user, $version);

        $final_exams_logs_repository = $em->getRepository('AppBundle:FinalExamLog');
        $passed_date = $final_exams_logs_repository->getPassedDate($user);
        $is_passed = (bool)$passed_date;

        $finalDriving = false;
        if ($is_passed) {
            $emptyTickets = $em->getRepository('AppBundle:DrivingTicket')->createQueryBuilder('t')
                ->andWhere('t.rating IS NULL')
                ->leftJoin('t.package', 'p')
                ->andWhere('p.sale_at IS NOT NULL')
                ->andWhere('p.user = :user')->setParameter('user', $user)
                ->getQuery()->execute();

            if (count($emptyTickets) == 0) {
                $finalDriving = true;
            }
        }

        $exp_limit = null;
        if ($user->getPayment2Paid()) {
            /** @var  $limitDate \My\AppBundle\Entity\Setting */
            $limitDate = $em->getRepository('AppBundle:Setting')->createQueryBuilder('s')
                ->andWhere('s._key = :key')->setParameter('key', 'access_time_after_2_payment')
                ->getQuery()->getOneOrNullResult();
            if ($limitDate) {
                $exp_limit = clone $user->getPayment2Paid();
                $exp_limit->add(new \DateInterval('P' . $limitDate->getValue() . 'D'));
            }
        }

        $isDocsForCheckExist = false;
        foreach ($userDocs as $userDoc) {
            /** @var  $userDoc \My\AppBundle\Entity\Document */
            if ($userDoc->getStatus() == null || $userDoc->getReSent()) {
                $isDocsForCheckExist = true;
                break;
            }
        }

        return $this->render('AppBundle:Admin/CheckRegions:user_card_view.html.twig', array(
            'user'                    => $user,
            'logs'                    => $logs,
            'subjects'                => $subjects,
            'passed_date'             => $passed_date,
            'is_passed'               => $is_passed,
            'is_expired'              => $exp_limit && $exp_limit < new \DateTime(),
            'user_docs'               => $userDocs,
            'confirmed_med_docs'      => $confirmedMedDocs,
            'confirmed_contract_docs' => $confirmedContractDocs,
            'confirmed_passport_docs' => $confirmedPassportDocs,
            'final_driving'           => $finalDriving,
            'packages'                => $packagesData,
            'admin_delete_docs'       => $adminDeleteDocs,
            'confirm_docs_exist'      => $confirmDocsExist,
            'docs_for_check'          => $isDocsForCheckExist,
        ));
    }
}
