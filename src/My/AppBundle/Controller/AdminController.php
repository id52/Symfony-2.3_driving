<?php

namespace My\AppBundle\Controller;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use My\AppBundle\Entity\Image;
use My\AppBundle\Entity\Holiday;
use My\AppBundle\Entity\OweStage;
use My\AppBundle\Entity\PromoKey;
use My\AppBundle\Entity\ServicePrice;
use My\AppBundle\Entity\Setting;
use My\AppBundle\Entity\Promo;
use My\AppBundle\Entity\SupportCategory;
use My\AppBundle\Entity\SupportMessage;
use My\AppBundle\Entity\FeedbackEmail;
use My\AppBundle\Entity\FeedbackTeacherEmail;
use My\AppBundle\Entity\User;
use My\AppBundle\Entity\UserStat;
use My\AppBundle\Form\Type\SimpleProfileFormType;
use My\AppBundle\Repository\CategoryPriceRepository;
use My\AppBundle\Util\Time;
use My\PaymentBundle\Entity\Log as PaymentLog;
use My\AppBundle\Form\Type\ImageFormType;
use My\AppBundle\Form\Type\ProfileFormType;
use My\AppBundle\Form\Type\SupportCategoryFormType;
use My\AppBundle\Form\Type\SupportMessageFormType;
use My\AppBundle\Form\Type\PromoFormType;
use My\AppBundle\Form\Type\PromoKeyFormType;
use My\AppBundle\Form\Type\PromoKeyGenerateFormType;
use My\AppBundle\Form\Type\FeedbackEmailFormType;
use My\AppBundle\Form\Type\FeedbackTeacherEmailFormType;
use My\AppBundle\Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;

class AdminController extends Controller
{
    /** @var $em \Doctrine\ORM\EntityManager */
    public $em;
    /** @var $user \My\AppBundle\Entity\User */
    public $user;
    public $settings = array();
    public $settingsNotifies = array();

    public function init()
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD')) {
            throw $this->createNotFoundException();
        }
    }

    public function indexAction()
    {
        $route = null;

        /** @var $cntxt \Symfony\Component\Security\Core\SecurityContext */
        $cntxt = $this->get('security.context');
        if ($cntxt->isGranted('ROLE_MOD_ACCOUNTANT')) {
            $route = 'admin_revert_money_user_list';
        }
        if ($cntxt->isGranted('ROLE_MOD_PRECHECK_USERS')) {
            $route = 'admin_precheck_users';
        }
        if ($cntxt->isGranted('ROLE_MOD_PARADOX_USERS')) {
            $route = 'admin_paradox_users';
        }
        if ($cntxt->isGranted('ROLE_MOD_CONTENT')) {
            $route = 'admin_training_versions';
        }
        if ($cntxt->isGranted('ROLE_MOD_ADD_USER')
            || $cntxt->isGranted('ROLE_MOD_REPRESENTATIVE')
        ) {
            $route = 'admin_add_user';
        }
        if ($cntxt->isGranted('ROLE_MOD_SUPPORT')) {
            $route = 'admin_support_dialogs';
        }
        if ($cntxt->isGranted('ROLE_MOD_MAILING')) {
            $route = 'admin_mailing';
        }
        if ($cntxt->isGranted('ROLE_MOD_FINANCE')) {
            $route = 'admin_overdue_statistics';
        }
        if ($cntxt->isGranted('ROLE_MOD_REG_STAT')) {
            $route = 'admin_reg_stat';
        }
        if ($cntxt->isGranted('ROLE_MOD_API_STAT')) {
            $route = 'admin_api_stat';
        }
        if ($cntxt->isGranted('ROLE_MOD_MANAGER')) {
            $route = 'admin_check_regions';
        }
        if ($cntxt->isGranted('ROLE_ADMIN')) {
            $route = 'admin_settings';
        }

        if (!$route) {
            throw new AccessDeniedException();
        }

        return $this->redirect($this->generateUrl($route));
    }

    public function settingsAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $settings_repository = $this->em->getRepository('AppBundle:Setting');
        $settings = $settings_repository->getAllData();

        $form_factory = $this->get('form.factory');
        /** @var $fb \Symfony\Component\Form\FormBuilder */
        $fb = $form_factory->createNamedBuilder('settings');

        $fb->add('medical_certificate_is_not_issued_and_the_agreement_is_not_signed', 'checkbox', [
            'required' => false
        ]);

        $fb->add('theme_test_correct_answers', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('theme_test_correct_answers_in_row', 'checkbox', array('required' => false));
        $fb->add('theme_test_questions_method', 'choice', array(
            'expanded' => true,
            'choices'  => array(
                'same_order' => 'settings_theme_test_questions_method_same_order',
                'shuffle'    => 'settings_theme_test_questions_method_shuffle',
            ),
            'attr'     => array('class' => 'inline'),
        ));
        $fb->add('theme_test_time', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('theme_test_shuffle_answers', 'checkbox', array('required' => false));

        $fb->add('ticket_test_old_style', 'checkbox', array('required' => false));

        $fb->add('slice_tickets', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('slice_questions_in_ticket', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('slice_not_repeat_questions_in_tickets', 'checkbox', array('required' => false));
        $fb->add('slice_max_errors_in_ticket', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('slice_ticket_time', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('slice_shuffle_answers', 'checkbox', array('required' => false));

        $fb->add('exam_shuffle', 'checkbox', array('required' => false));
        $fb->add('exam_tickets', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('exam_questions_in_ticket', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('exam_not_repeat_questions_in_tickets', 'checkbox', array('required' => false));
        $fb->add('exam_max_errors_in_ticket', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('exam_ticket_time', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('exam_shuffle_answers', 'checkbox', array('required' => false));
        $fb->add('exam_retake_time', 'integer', array('attr' => array('class' => 'span1')));

        $fb->add('final_exam_1_shuffle', 'checkbox', array('required' => false));
        $fb->add('final_exam_1_tickets', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('final_exam_1_ticket_time', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('final_exam_1_shuffle_answers', 'checkbox', array('required' => false));
        $fb->add('final_exam_1_extra_time', 'integer', array('attr' => array('class' => 'span1')));

        $fb->add('final_exam_2_tickets', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('final_exam_2_questions_in_ticket', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('final_exam_2_not_repeat_questions_in_tickets', 'checkbox', array('required' => false));
        $fb->add('final_exam_2_max_errors_in_ticket', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('final_exam_2_ticket_time', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('final_exam_2_shuffle_answers', 'checkbox', array('required' => false));

        $fb->add('access_time_after_1_payment', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('access_time_after_2_payment', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('support_days_to_answer', 'integer', array('attr' => array('class' => 'span1')));

        for ($i = 1; $i <= 16; $i ++) {
            $fb->add('access_time_end_notify_after_1_payment_'.$i, 'integer', array(
                'attr' => array('class' => 'span1'),
            ));
            $fb->add('access_time_end_notify_after_2_payment_'.$i, 'integer', array(
                'attr' => array('class' => 'span1'),
            ));
        }

        $fb->add('notify_no_payments_1', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('notify_no_payments_2', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('notify_no_payments_3', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('notify_no_payments_4', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('notify_no_payments_5', 'integer', array('attr' => array('class' => 'span1')));

        $fb->add('notify_no_payments_promo_expiration_1', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('notify_no_payments_promo_expiration_2', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('notify_no_payments_promo_expiration_3', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('notify_no_payments_promo_expiration_4', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('notify_no_payments_promo_expiration_5', 'integer', array('attr' => array('class' => 'span1')));

        $fb->add('notify_no_payments_promo_discount_1', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('notify_no_payments_promo_discount_2', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('notify_no_payments_promo_discount_3', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('notify_no_payments_promo_discount_4', 'integer', array('attr' => array('class' => 'span1')));
        $fb->add('notify_no_payments_promo_discount_5', 'integer', array('attr' => array('class' => 'span1')));

        $fb->add('attempts_days_of_retake', 'integer', array(
            'attr'        => array('class' => 'span1'),
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\GreaterThanOrEqual(0),
            ),
        ));
        $fb->add('attempts_to_reset', 'integer', array(
            'attr'        => array('class' => 'span1'),
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\GreaterThan(0),
            ),
        ));

        $fb->add('cost_driving_payment_in_office', 'integer', array(
            'attr' => array('class' => 'span1'),
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\GreaterThan(0),
            ),
        ));

        $fb->setData(array_intersect_key($settings, $fb->all()));

        $form = $fb->getForm();
        $errorsOwe = [];
        if ($request->isMethod('post')) {
            $form->handleRequest($request);

            $data = $form->getData();
            if ($data['access_time_after_1_payment'] > 0
                && $data['access_time_after_2_payment'] > 0
                && $data['access_time_after_1_payment'] >= $data['access_time_after_2_payment']
            ) {
                $translator = $this->get('translator');
                $error = new FormError($translator->trans('settings_access_time_after_2_payment_less'));
                $form->get('access_time_after_2_payment')->addError($error);
            }

            $costs = $request->get('cost_online_driving_payment');
            $timers = $request->get('timer_online_driving_payment');
            $resultOnlinePayments = [];

            if ($costs && $timers) {
                $maxCount = max(count($costs), count($timers));

                for ($i = 1; $i <= $maxCount; $i++) {
                    $ind = ($i-1);
                    $cost = isset($costs['cost_'.$i]) ? intval($costs['cost_'.$i]) : 0;
                    if ($cost <= 0) {
                        $errorsOwe[$ind]['cost'] = 'Ценник должен быть больше чем 0';
                    }

                    $timer = isset($timers['timer_'.$i]) ? intval($timers['timer_'.$i]) : 0;
                    if ($timer <= 0) {
                        $errorsOwe[$ind]['timer'] = 'Срок должен быть больше чем 0';
                    }

                    $resultOnlinePayments[] = [
                        'cost'  => $cost,
                        'timer' => $timer,
                    ];
                }
            }

            if ($form->isValid() && !count($errorsOwe)) {
                $settings_repository->setData($form->getData());

                $onlineDrivingPayment = $this->em->find('AppBundle:Setting', 'online_driving_payment');

                if (!$onlineDrivingPayment) {
                    $onlineDrivingPayment = new Setting();
                    $onlineDrivingPayment->setKey('online_driving_payment');
                    $onlineDrivingPayment->setType('string');
                }

                $onlineDrivingPayment->setValue(serialize($resultOnlinePayments));

                $this->em->persist($onlineDrivingPayment);
                $this->em->flush();

                return $this->redirect($this->generateUrl('admin_settings'));
            }
        }

        $onlineDrivingPayment = $this->em->find('AppBundle:Setting', 'online_driving_payment');

        if ($onlineDrivingPayment) {
            $onlineDrivingPayment = unserialize($onlineDrivingPayment->getValue());
        }

        return $this->render('AppBundle:Admin:settings.html.twig', array(
            'form'                   => $form->createView(),
            'online_driving_payment' => $onlineDrivingPayment,
            'owe_errors'             => $errorsOwe,
        ));
    }

    public function settingsNotifiesRegistrationAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $settings_repository = $this->em->getRepository('AppBundle:Setting');
        $settings = $settings_repository->getAllData();

        $form_factory = $this->get('form.factory');
        $fb = $form_factory->createNamedBuilder('settings_notifies');

        $fb->add('confirmation_registration_enabled', 'checkbox', array('required' => false));
        $fb->add('confirmation_registration_title', 'text');
        $fb->add('confirmation_registration_text', 'textarea');

        $fb->add('confirmation_sms_info_text', 'textarea');

        $fb->add('confirmation_error_sms_info_text', 'textarea');

        $fb->add('confirmed_registration_title', 'text');
        $fb->add('confirmed_registration_text', 'textarea');

        $fb->add('confirmed_registration_again_title', 'text');
        $fb->add('confirmed_registration_again_text', 'textarea');

        $fb->add('api_add_user_email_title', 'text');
        $fb->add('api_add_user_email_text', 'textarea');

        $fb->add('after_changing_phone_number_text', 'textarea');

        $fb->add('activation_add_user_title');
        $fb->add('activation_add_user_text', 'textarea');

        $fb->add('need_fill_profile_title');
        $fb->add('need_fill_profile_text', 'textarea');
        $fb->add('need_fill_profile_button');

        $fb->add('confirmation_code_was_sent_again_text', 'textarea');

        $fb->add('confirmation_code_time_to_resend_text', 'textarea');

        $fb->setData(array_intersect_key($settings, $fb->all()));

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $settings_repository->setData($form->getData());

            return $this->redirect($this->generateUrl('admin_settings_notifies_registration'));
        }

        return $this->render('AppBundle:Admin/SettingsNotifies:settings_notifies_registration.html.twig', array(
            'form'     => $form->createView(),
        ));
    }


    public function settingsNotifiesValidityPeriodsAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $settings_repository = $this->em->getRepository('AppBundle:Setting');
        $settings = $settings_repository->getAllData();

        $form_factory = $this->get('form.factory');
        $fb = $form_factory->createNamedBuilder('settings_notifies');

        $fb->add('timelimit_after_1_payment_title', 'text');
        $fb->add('timelimit_after_1_payment_text', 'textarea');

        $fb->add('before_access_time_end_after_1_payment_enabled', 'checkbox', array('required' => false));
        $fb->add('before_access_time_end_after_1_payment_title', 'text');
        $fb->add('before_access_time_end_after_1_payment_text', 'textarea');

        $fb->add('before_access_time_end_after_1_payment_email_enabled', 'checkbox', array('required' => false));
        $fb->add('before_access_time_end_after_1_payment_email_title', 'text');
        $fb->add('before_access_time_end_after_1_payment_email_text', 'textarea');

        $fb->add('after_access_time_end_after_1_payment_enabled', 'checkbox', array('required' => false));
        $fb->add('after_access_time_end_after_1_payment_title', 'text');
        $fb->add('after_access_time_end_after_1_payment_text', 'textarea');

        $fb->add('before_access_time_end_after_2_payment_enabled', 'checkbox', array('required' => false));
        $fb->add('before_access_time_end_after_2_payment_title', 'text');
        $fb->add('before_access_time_end_after_2_payment_text', 'textarea');

        $fb->add('before_access_time_end_after_2_payment_email_enabled', 'checkbox', array('required' => false));
        $fb->add('before_access_time_end_after_2_payment_email_title', 'text');
        $fb->add('before_access_time_end_after_2_payment_email_text', 'textarea');

        $fb->add('after_access_time_end_after_2_payment_enabled', 'checkbox', array('required' => false));
        $fb->add('after_access_time_end_after_2_payment_title', 'text');
        $fb->add('after_access_time_end_after_2_payment_text', 'textarea');

        $fb->add('discount_2_notify_first_email_title', 'text');
        $fb->add('discount_2_notify_first_email_text', 'textarea');

        $fb->add('discount_2_warning_first_email_title', 'text');
        $fb->add('discount_2_warning_first_email_text', 'textarea');

        $fb->add('discount_2_missing_email_title', 'text');
        $fb->add('discount_2_missing_email_text', 'textarea');

        $fb->add('discount_2_warning_second_email_title', 'text');
        $fb->add('discount_2_warning_second_email_text', 'textarea');

        $fb->setData(array_intersect_key($settings, $fb->all()));

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $settings_repository->setData($form->getData());

            return $this->redirect($this->generateUrl('admin_settings_notifies_validity_periods'));
        }

        return $this->render('AppBundle:Admin/SettingsNotifies:settings_notifies_validity_periods.html.twig', array(
            'form'     => $form->createView(),
        ));

    }

    public function settingsNotifiesNotifiesExamsAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $settings_repository = $this->em->getRepository('AppBundle:Setting');
        $settings = $settings_repository->getAllData();

        $form_factory = $this->get('form.factory');
        $fb = $form_factory->createNamedBuilder('settings_notifies');

        $subjects = $this->em->getRepository('AppBundle:Subject')->findAll();
        foreach ($subjects as $subject) {
            /** @var $subject \My\AppBundle\Entity\Subject */

            $fb->add('after_all_slices_'.$subject->getId().'_enabled', 'checkbox', array('required' => false));
            $fb->add('after_all_slices_'.$subject->getId().'_title', 'text');
            $fb->add('after_all_slices_'.$subject->getId().'_text', 'textarea');

            $fb->add('after_all_slices_'.$subject->getId().'_email_enabled', 'checkbox', array('required' => false));
            $fb->add('after_all_slices_'.$subject->getId().'_email_title', 'text');
            $fb->add('after_all_slices_'.$subject->getId().'_email_text', 'textarea');

            $fb->add('after_fail_exam_'.$subject->getId().'_enabled', 'checkbox', array('required' => false));
            $fb->add('after_fail_exam_'.$subject->getId().'_title', 'text');
            $fb->add('after_fail_exam_'.$subject->getId().'_text', 'textarea');

            $fb->add('after_fail_exam_'.$subject->getId().'_email_enabled', 'checkbox', array('required' => false));
            $fb->add('after_fail_exam_'.$subject->getId().'_email_title', 'text');
            $fb->add('after_fail_exam_'.$subject->getId().'_email_text', 'textarea');

            $fb->add('after_exam_'.$subject->getId().'_enabled', 'checkbox', array('required' => false));
            $fb->add('after_exam_'.$subject->getId().'_title', 'text');
            $fb->add('after_exam_'.$subject->getId().'_text', 'textarea');

            $fb->add('after_exam_'.$subject->getId().'_email_enabled', 'checkbox', array('required' => false));
            $fb->add('after_exam_'.$subject->getId().'_email_title', 'text');
            $fb->add('after_exam_'.$subject->getId().'_email_text', 'textarea');
        }

        $fb->add('after_all_exams_enabled', 'checkbox', array('required' => false));
        $fb->add('after_all_exams_title', 'text');
        $fb->add('after_all_exams_text', 'textarea');

        $fb->add('after_all_exams_email_enabled', 'checkbox', array('required' => false));
        $fb->add('after_all_exams_email_title', 'text');
        $fb->add('after_all_exams_email_text', 'textarea');

        $fb->add('after_final_exam_enabled', 'checkbox', array('required' => false));
        $fb->add('after_final_exam_title', 'text');
        $fb->add('after_final_exam_text', 'textarea');

        $fb->add('after_final_exam_email_enabled', 'checkbox', array('required' => false));
        $fb->add('after_final_exam_email_title', 'text');
        $fb->add('after_final_exam_email_text', 'textarea');

        $fb->add('max_errors_questions_text', 'textarea', array('required' => false));

        $fb->add('max_errors_questions_block_text', 'textarea', array('required' => false));

        $fb->add('max_errors_additional_questions_text', 'textarea', array('required' => false));

        $fb->add('max_errors_ticket_text', 'textarea', array('required' => false));

        $fb->setData(array_intersect_key($settings, $fb->all()));

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $settings_repository->setData($form->getData());

            return $this->redirect($this->generateUrl('admin_settings_notifies_exams'));
        }

        return $this->render('AppBundle:Admin/SettingsNotifies:settings_notifies_exams.html.twig', array(
            'form'     => $form->createView(),
            'subjects' => $subjects,
        ));
    }

    public function settingsNotifiesPaymentsAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $settings_repository = $this->em->getRepository('AppBundle:Setting');
        $settings = $settings_repository->getAllData();

        $form_factory = $this->get('form.factory');
        $fb = $form_factory->createNamedBuilder('settings_notifies');

        $fb->add('hurry_email_title', 'text');
        $fb->add('hurry_email_text', 'textarea');

        $fb->add('no_payments_enabled', 'checkbox', array('required' => false));
        $fb->add('no_payments_title', 'text');
        $fb->add('no_payments_text', 'textarea');

        $fb->add('pay_now_title', 'text');
        $fb->add('pay_now_text', 'textarea');

        $fb->add('training_without_2_payment_title', 'text');
        $fb->add('training_without_2_payment_text', 'textarea');

        $fb->add('first_payment_text', 'textarea');
        $fb->add('first_payment_promo_discount_text', 'textarea');

        $fb->add('second_payment_title', 'text');
        $fb->add('second_payment_text', 'textarea');

        $fb->add('after_confirm_mobile_enabled', 'checkbox', array('required' => false));
        $fb->add('after_confirm_mobile_title', 'text');
        $fb->add('after_confirm_mobile_text', 'textarea');

        $fb->add('after_confirm_mobile_email_enabled', 'checkbox', array('required' => false));
        $fb->add('after_confirm_mobile_email_title', 'text');
        $fb->add('after_confirm_mobile_email_text', 'textarea');

        $fb->add('after_1_payment_enabled', 'checkbox', array('required' => false));
        $fb->add('after_1_payment_title', 'text');
        $fb->add('after_1_payment_text', 'textarea');

        $fb->add('after_1_promo_payment_enabled', 'checkbox', array('required' => false));
        $fb->add('after_1_promo_payment_title', 'text');
        $fb->add('after_1_promo_payment_text', 'textarea');

        $fb->add('after_1_payment_email_enabled', 'checkbox', array('required' => false));
        $fb->add('after_1_payment_email_title', 'text');
        $fb->add('after_1_payment_email_text', 'textarea');

        $fb->add('payments_mobile_not_confirmed_title', 'text');
        $fb->add('payments_mobile_not_confirmed_text', 'textarea');

        $fb->add('after_1_payment_email_enabled', 'checkbox', array('required' => false));
        $fb->add('after_1_payment_email_title', 'text');
        $fb->add('after_1_payment_email_text', 'textarea');

        $fb->add('after_1_promo_payment_email_enabled', 'checkbox', array('required' => false));
        $fb->add('after_1_promo_payment_email_title', 'text');
        $fb->add('after_1_promo_payment_email_text', 'textarea');

        $fb->add('discount_2_notify_first', 'textarea');
        $fb->add('discount_2_notify_second', 'textarea');
        $fb->add('discount_2_warning_sms_first', 'textarea');
        $fb->add('discount_2_missing_sms', 'textarea');
        $fb->add('discount_2_warning_sms_second', 'textarea');

        $fb->add('after_2_payment_enabled', 'checkbox', array('required' => false));
        $fb->add('after_2_payment_title', 'text');
        $fb->add('after_2_payment_text', 'textarea');

        $fb->add('after_2_promo_payment_enabled', 'checkbox', array('required' => false));
        $fb->add('after_2_promo_payment_title', 'text');
        $fb->add('after_2_promo_payment_text', 'textarea');

        $fb->add('after_2_payment_email_enabled', 'checkbox', array('required' => false));
        $fb->add('after_2_payment_email_title', 'text');
        $fb->add('after_2_payment_email_text', 'textarea');

        $fb->add('after_2_promo_payment_email_enabled', 'checkbox', array('required' => false));
        $fb->add('after_2_promo_payment_email_title', 'text');
        $fb->add('after_2_promo_payment_email_text', 'textarea');

        $fb->add('after_payment_enabled', 'checkbox', array('required' => false));
        $fb->add('after_payment_title', 'text');
        $fb->add('after_payment_text', 'textarea');

        $fb->add('after_promo_payment_enabled', 'checkbox', array('required' => false));
        $fb->add('after_promo_payment_title', 'text');
        $fb->add('after_promo_payment_text', 'textarea');

        $fb->add('after_payment_email_enabled', 'checkbox', array('required' => false));
        $fb->add('after_payment_email_title', 'text');
        $fb->add('after_payment_email_text', 'textarea');

        $fb->add('after_promo_payment_email_enabled', 'checkbox', array('required' => false));
        $fb->add('after_promo_payment_email_title', 'text');
        $fb->add('after_promo_payment_email_text', 'textarea');

        $fb->add('primary_boosting_not_paid_driving_popup_title', 'text');
        $fb->add('primary_boosting_not_paid_driving_popup_text', 'textarea');

        $fb->add('primary_boosting_not_paid_driving_email_enabled', 'checkbox', array('required' => false));
        $fb->add('primary_boosting_not_paid_driving_email_title', 'text');
        $fb->add('primary_boosting_not_paid_driving_email_text', 'textarea');

        $fb->add('user_not_paid_primary_boosting_not_paid_driving_popup_title', 'text');
        $fb->add('user_not_paid_primary_boosting_not_paid_driving_popup_text', 'textarea');

        $fb->add('user_not_paid_primary_boosting_not_paid_driving_email_enabled', 'checkbox', array(
            'required' => false,
        ));
        $fb->add('user_not_paid_primary_boosting_not_paid_driving_email_title', 'text');
        $fb->add('user_not_paid_primary_boosting_not_paid_driving_email_text', 'textarea');

        $fb->setData(array_intersect_key($settings, $fb->all()));

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $settings_repository->setData($form->getData());

            return $this->redirect($this->generateUrl('admin_settings_notifies_payments'));
        }

        return $this->render('AppBundle:Admin/SettingsNotifies:settings_notifies_payments.html.twig', array(
            'form'     => $form->createView(),
        ));
    }

    public function settingsNotifiesSystemAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $settings_repository = $this->em->getRepository('AppBundle:Setting');
        $settings = $settings_repository->getAllData();

        $form_factory = $this->get('form.factory');
        $fb = $form_factory->createNamedBuilder('settings_notifies');

        $fb->add('landing_title', 'text');
        $fb->add('landing_keywords', 'text');
        $fb->add('landing_description', 'text');

        $fb->add('contact_email', 'text');
        $fb->add('contact_phone', 'text');

        $fb->add('social_vk', 'text');
        $fb->add('social_facebook', 'text');
        $fb->add('social_twitter', 'text');

        $fb->add('profile_final_exam', 'textarea');

        $fb->add('training_theme_test_success', 'textarea');
        $fb->add('training_theme_test_error', 'textarea');
        $fb->add('training_theme_test_timeout', 'textarea');
        $fb->add('training_theme_test_complete_next', 'textarea');
        $fb->add('training_theme_test_complete_list', 'textarea');
        $fb->add('training_theme_test_long_time', 'textarea');

        $fb->add('training_slice_success', 'textarea');
        $fb->add('training_slice_error', 'textarea');
        $fb->add('training_slice_timeout', 'textarea');
        $fb->add('training_slice_complete', 'textarea');
        $fb->add('training_slice_long_time', 'textarea');
        $fb->add('training_slice_max_errors', 'textarea');

        $fb->add('training_exam_success', 'textarea');
        $fb->add('training_exam_error', 'textarea');
        $fb->add('training_exam_timeout', 'textarea');
        $fb->add('training_exam_complete', 'textarea');
        $fb->add('training_exam_long_time', 'textarea');
        $fb->add('training_exam_max_errors', 'textarea');
        $fb->add('training_exam_retake', 'textarea');

        $fb->add('training_final_exam_description', 'textarea');
        $fb->add('training_final_exam_success', 'textarea');
        $fb->add('training_final_exam_error', 'textarea');
        $fb->add('training_final_exam_timeout', 'textarea');
        $fb->add('training_final_exam_complete', 'textarea');
        $fb->add('training_final_exam_long_time', 'textarea');
        $fb->add('training_final_exam_max_errors', 'textarea');
        $fb->add('training_final_exam_retake', 'textarea');
        $fb->add('training_final_exam_denied', 'textarea');
        $fb->add('training_final_exam_passed', 'textarea');

        $fb->add('training_test_success', 'textarea');
        $fb->add('training_test_error', 'textarea');
        $fb->add('training_test_timeout', 'textarea');
        $fb->add('training_test_complete', 'textarea');
        $fb->add('training_test_long_time', 'textarea');
        $fb->add('training_test_max_errors', 'textarea');

        $fb->add('training_test_knowledge_success', 'textarea');
        $fb->add('training_test_knowledge_error', 'textarea');
        $fb->add('training_test_knowledge_timeout', 'textarea');
        $fb->add('training_test_knowledge_complete', 'textarea');
        $fb->add('training_test_knowledge_long_time', 'textarea');
        $fb->add('training_test_knowledge_max_errors', 'textarea');

        $fb->add('profile_final_exam', 'textarea');

        $fb->add('trainings_help_btn', 'textarea');
        $subjects = $this->em->getRepository('AppBundle:Subject')->findAll();
        foreach ($subjects as $subject) {
            /** @var $subject \My\AppBundle\Entity\Subject */

            $fb->add('training_'.$subject->getId().'_help_btn', 'textarea');
        }
        $fb->add('theme_help_btn', 'textarea');
        $fb->add('question_help_btn', 'textarea');
        $fb->add('promo_help_btn', 'textarea');

        $fb->setData(array_intersect_key($settings, $fb->all()));

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $settings_repository->setData($form->getData());

            return $this->redirect($this->generateUrl('admin_settings_notifies_system'));
        }

        return $this->render('AppBundle:Admin/SettingsNotifies:settings_notifies_system.html.twig', array(
            'form'     => $form->createView(),
            'subjects' => $subjects,
        ));
    }

    public function settingsNotifiesUserNotifiesAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $settings_repository = $this->em->getRepository('AppBundle:Setting');
        $settings = $settings_repository->getAllData();

        $form_factory = $this->get('form.factory');
        $fb = $form_factory->createNamedBuilder('settings_notifies');

        $fb->add('after_password_recovery_title', 'text');
        $fb->add('after_password_recovery_text', 'textarea');

        $fb->add('resetting_password_enabled', 'checkbox', array('required' => false));
        $fb->add('resetting_password_title', 'text');
        $fb->add('resetting_password_text', 'textarea');

        $fb->add('payments_mobile_not_confirmed_title', 'text');
        $fb->add('payments_mobile_not_confirmed_text', 'textarea');

        $fb->add('after_confirm_mobile_enabled', 'checkbox', array('required' => false));
        $fb->add('after_confirm_mobile_title', 'text');
        $fb->add('after_confirm_mobile_text', 'textarea');

        $fb->add('after_confirm_mobile_email_enabled', 'checkbox', array('required' => false));
        $fb->add('after_confirm_mobile_email_title', 'text');
        $fb->add('after_confirm_mobile_email_text', 'textarea');

        $fb->add('after_1_payment_enabled', 'checkbox', array('required' => false));
        $fb->add('after_1_payment_title', 'text');
        $fb->add('after_1_payment_text', 'textarea');

        $fb->add('after_1_promo_payment_enabled', 'checkbox', array('required' => false));
        $fb->add('after_1_promo_payment_title', 'text');
        $fb->add('after_1_promo_payment_text', 'textarea');

        $fb->add('birthday_greeting_title', 'text');
        $fb->add('birthday_greeting_text', 'textarea');

        $fb->add('lock_user_enabled', 'checkbox', array('required' => false));
        $fb->add('lock_user_title', 'text');
        $fb->add('lock_user_text', 'textarea');

        $fb->add('unlock_user_enabled', 'checkbox', array('required' => false));
        $fb->add('unlock_user_title', 'text');
        $fb->add('unlock_user_text', 'textarea');

        $fb->add('error_account_locked', 'text');

        for ($i = 1; $i <= 5; $i++) {
            $fb->add('sign_'.$i, 'text');
        }

        $fb->add('support_answered_email_enabled', 'checkbox', ['required' => false]);
        $fb->add('support_answered_email_title', 'text');
        $fb->add('support_answered_email_text', 'textarea');

        $fb->add('api_med_form', 'textarea');
        $fb->add('api_contract_sign', 'textarea');
        $fb->add('api_med_con', 'textarea');

        $fb->add('unpaid_driving_email_enabled', 'checkbox', ['required' => false]);
        $fb->add('unpaid_driving_email_title', 'text');
        $fb->add('unpaid_driving_email_text', 'textarea');

        $fb->add('unpaid_driving_sms_enabled', 'checkbox', ['required' => false]);
        $fb->add('unpaid_driving_sms_text', 'textarea');

        $fb->setData(array_intersect_key($settings, $fb->all()));

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $settings_repository->setData($form->getData());

            return $this->redirect($this->generateUrl('admin_settings_notifies_user_notifies'));
        }

        return $this->render('AppBundle:Admin/SettingsNotifies:settings_notifies_user_notifies.html.twig', array(
            'form'     => $form->createView(),
        ));
    }

    public function settingsNotifiesAdditionalAttemptsAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $settings_repository = $this->em->getRepository('AppBundle:Setting');
        $settings = $settings_repository->getAllData();

        $form_factory = $this->get('form.factory');
        $fb = $form_factory->createNamedBuilder('settings_notifies');

        $fb->add('info_attempts_title', 'text');
        $fb->add('info_attempts_text', 'textarea');

        $fb->add('attempts_buy_title', 'text');
        $fb->add('attempts_buy_text', 'textarea');

        $fb->add('attempts_reset_progress_title', 'textarea');
        $fb->add('attempts_reset_progress_text', 'textarea');

        $fb->add('second_attempt_title', 'text');
        $fb->add('second_attempt_text', 'textarea');

        $fb->setData(array_intersect_key($settings, $fb->all()));

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $settings_repository->setData($form->getData());

            return $this->redirect($this->generateUrl('admin_settings_notifies_additional_attempts'));
        }

        return $this->render('AppBundle:Admin/SettingsNotifies:settings_notifies_additional_attempts.html.twig', array(
            'form'     => $form->createView(),
        ));
    }

    public function settingsNotifiesMoscowFilialsAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $settings_repository = $this->em->getRepository('AppBundle:Setting');
        $settings = $settings_repository->getAllData();

        $form_factory = $this->get('form.factory');
        $fb = $form_factory->createNamedBuilder('settings_notifies');


        $fb->add('tickets_is_sended_title', 'text');
        $fb->add('tickets_is_sended_text', 'textarea');

        $fb->add('doc_is_done_title', 'text');
        $fb->add('doc_is_done_text', 'textarea');

        $fb->add('doc_is_confirm_title', 'text');
        $fb->add('doc_is_confirm_text', 'textarea');

        $fb->add('doc_is_fail_title', 'text');
        $fb->add('doc_is_fail_text', 'textarea');



        $fb->setData(array_intersect_key($settings, $fb->all()));

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $settings_repository->setData($form->getData());

            return $this->redirect($this->generateUrl('admin_settings_notifies_moscow_filials'));
        }

        return $this->render('AppBundle:Admin/SettingsNotifies:settings_notifies_moscow_filials.html.twig', array(
            'form'     => $form->createView(),
        ));

    }

    public function imageAjaxAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_CONTENT')) {
            throw $this->createNotFoundException();
        }

        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $result = array();

        if ($request->isMethod('post')) {
            $image = $this->em->getRepository('AppBundle:Image')->find($request->get('image_id'));
            if (!$image) {
                $image = new Image();
            }
            $form = $this->createForm(new ImageFormType(), $image);
            $form->handleRequest($request);
            if ($form->isValid()) {
                /** @var $image \My\AppBundle\Entity\Image */
                $image = $form->getData();
                $image->setUpdatedAt(new \DateTime());
                $this->em->persist($image);
                $this->em->flush();

                $result['image_id'] = $image->getId();
                $result['image_view'] = $this->renderView('AppBundle:Admin:image_view.html.twig', array(
                    'image' => $image,
                ));
            } else {
                foreach ($form->getErrors() as $error) {
                    $result['errors'][] = $error->getMessage();
                }
            }
        } else {
            $result['errors'][] = $this->get('translator')->trans('errors.not_post');
        }

        return new JsonResponse($result);
    }

    public function imageViewAjaxAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_CONTENT')) {
            throw $this->createNotFoundException();
        }

        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $trans = $this->get('translator');
        $result = array();

        if ($request->isMethod('post')) {
            $image_id = $request->get('image_id');
            $image = $this->em->getRepository('AppBundle:Image')->find($image_id);
            if ($image) {
                $result['image_view'] = $this->renderView('AppBundle:Admin:image_view.html.twig', array(
                    'image' => $image,
                ));
            } else {
                $result['errors'][] = $trans->trans('errors.image_not_found', array('%image_id%' => $image_id));
            }
        } else {
            $result['errors'][] = $trans->trans('errors.not_post');
        }

        return new JsonResponse($result);
    }

    public function promosAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $fb = $this->createFormBuilder(array(), array('csrf_protection' => false))
            ->add('active', 'choice', array(
                'label'   => 'Активна?',
                'choices' => array(
                    2 => 'Да',
                    1 => 'Нет',
                ),
            ))
        ;
        $fb->setMethod('get');
        $form = $fb->getForm();
        $form->handleRequest($request);

        $qb = $this->em->getRepository('AppBundle:Promo')->getBuilderForTriedRezervActiv();

        if (($active = $form->get('active')->getData()) && $active == 1) {
            $qb
                ->andWhere('p.active = :active')->setParameter('active', 0)
                ->orWhere('p.used_from > :now OR p.used_to < :now')->setParameter('now', new \DateTime())
            ;
        } else {
            $qb
                ->andWhere('p.active = :active')->setParameter('active', 1)
                ->andWhere('p.used_from <= :now AND p.used_to >= :now')->setParameter('now', new \DateTime())
            ;
        }

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($request->get('page'));

        return $this->render('AppBundle:Admin:promos.html.twig', array(
            'pagerfanta'  => $pagerfanta,
            'filter_form' => $form->createView(),
        ));
    }

    public function promoAddAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $promo = new Promo();

        $form = $this->createForm(new PromoFormType, $promo);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $promoService = $this->get('app.promo');

            if ($form->get('restricted')->getData() == 'keys') {
                $promo->setMaxUsers(0);
                $hashes = $promoService->generatePromoKeyHashes($form->get('generateKeysCount')->getData());
            } else {
                $hashes = $promoService->generatePromoKeyHashes(1);
            }
            $promo->setActive($promo->getActive()); //update to real status (depending on used_to, used_from)
            $this->em->persist($promo);

            //add new promo keys
            foreach ($hashes as $hash) {
                $promoKey = new PromoKey();
                $promoKey->setActive(true);
                $promoKey->setDiscount($form->get('discount')->getData());
                $promoKey->setHash($hash);
                $promoKey->setPromo($promo);
                $promoKey->setType($form->get('type')->getData());
                $this->em->persist($promoKey);
            }

            $this->em->flush();

            $this->get('session')->getFlashBag()->add('success', 'success_added');

            return $this->redirect($this->generateUrl('admin_promo_add'));
        }

        return $this->render('AppBundle:Admin:promo.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function promoEditAction(Request $request, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $translator = $this->get('translator');

        $promo = $this->em->getRepository('AppBundle:Promo')->find($id);
        if (!$promo) {
            throw $this->createNotFoundException('Promo for id "'.$id.'" not found.');
        }

        /** @var $form \Symfony\Component\Form\Form */
        $form = $this->createForm(new PromoFormType(), $promo)
            ->remove('generateKeysCount')
            ->remove('discount')
            ->remove('type')
        ;
        if ($request->isMethod('post')) {
            $form->handleRequest($request);

            if ($form->get('restricted')->getData() == 'users' && count($promo->getKeys()) > 1) {
                $form->get('restricted')->addError(new FormError($translator->trans('promo_error_cant_change')));
            }
            if ($form->isValid()) {
                $this->em->persist($promo);
                $this->em->flush();

                return $this->redirect($this->generateUrl('admin_promos'));
            }
        }

        return $this->render('AppBundle:Admin:promo.html.twig', array(
            'form'  => $form->createView(),
            'promo' => $promo,
        ));
    }

    public function promoDeleteAction($id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $promo = $this->em->getRepository('AppBundle:Promo')->find($id);
        if (!$promo) {
            throw $this->createNotFoundException('Promo for id "'.$id.'" not found.');
        }

        $isRemovable = $this->em->getRepository('AppBundle:Promo')->isRemovable($id);

        if ($isRemovable && !$promo->getAutoCreate()) {
            $this->em->remove($promo);
            $this->em->flush();
        } else {
            $this->get('session')->getFlashBag()->add('error', 'promo_error_cant_delete_logs');
        }

        return $this->redirect($this->generateUrl('admin_promos'));
    }

    public function promoKeysAction(Request $request, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $form_factory = $this->container->get('form.factory');
        $fb = $form_factory->createNamedBuilder('promo_key', 'form', array(), array('csrf_protection' => false))
            ->add('active', 'choice', array(
                'choices'     => array(
                    1 => 'Нет',
                    2 => 'Да',
                ),
                'empty_value' => 'choose_option',
                'required'    => false,
            ))
            ->add('type', 'choice', array(
                'choices'     => array(
                    'training'    => 'Пакет регистрации в ГИБДД',
                    'site_access' => 'Доступ к теоретическому курсу (устар.)',
                ),
                'empty_value' => 'choose_option',
                'required'    => false,
            ))
            ->add('used', 'choice', array(
                'choices'     => array(
                    'Не использованы',
                    'Использованы',
                ),
                'empty_value' => 'choose_option',
                'required'    => false,
            ))
            ->add('createdFrom', 'date', array('required' => false))
            ->add('createdTo', 'date', array('required' => false))
            ->add('activatedFrom', 'date', array('required' => false))
            ->add('activatedTo', 'date', array('required' => false))
        ;
        $fb->setMethod('get');
        $form = $fb->getForm();
        $form->handleRequest($request);

        $qb = $this->em->getRepository('AppBundle:PromoKey')->getBuilderForTriedRezervActiv();

        if ($active = $form->get('active')->getData()) {
            $qb->andWhere('pk.active = :act')->setParameter(':act', ($active - 1));
        }
        if ($type = $form->get('type')->getData()) {
            $qb->andWhere('pk.type = :type')->setParameter(':type', ($type));
        }
        if (null !== $used = $form->get('used')->getData()) {
            if ($used) {
                $qb->having('COUNT(pl.id) > 0');
            } else {
                $qb->having('COUNT(pl.id) = 0');
            }
        }
        if ($createdFrom = $form->get('createdFrom')->getData()) {
            $qb->andWhere('pk.created >= :createdFrom')->setParameter(':createdFrom', $createdFrom);
        }
        if ($createdTo = $form->get('createdTo')->getData()) {
            $qb->andWhere('pk.created <= :createdTo')->setParameter(':createdTo', $createdTo);
        }
        if ($activatedFrom = $form->get('activatedFrom')->getData()) {
            $qb->andWhere('pk.activated >= :activatedFrom')->setParameter(':activatedFrom', $activatedFrom);
        }
        if ($activatedTo = $form->get('activatedTo')->getData()) {
            $qb->andWhere('pk.activated <= :activatedTo')->setParameter(':activatedTo', $activatedTo);
        }
        if ($id) {
            $qb->andWhere('pk.promo = ?1')->setParameter(1, $id);
        }

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($request->get('page'));
        $pagerfanta->setMaxPerPage(50);

        $promo = null;
        if ($id) {
            $promo = $this->em->find('AppBundle:Promo', $id);
        }

        return $this->render('AppBundle:Admin:promo_keys.html.twig', array(
            'pagerfanta'  => $pagerfanta,
            'filter_form' => $form->createView(),
            'promo'       => $promo,
        ));
    }

    public function promoKeysActivatedAction(Request $request, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $key = $this->em->getRepository('AppBundle:PromoKey')->find($id);
        if (!$key) {
            throw $this->createNotFoundException('Promo key for id "'.$id.'" not found.');
        }

        $qb = $this->em->getRepository('PaymentBundle:Log')->createQueryBuilder('log')
            ->leftJoin('log.user', 'user')
            ->andWhere('log.promoKey = :key')->setParameter(':key', $key)
            ->andWhere('log.paid = :paid')->setParameter(':paid', true)
        ;

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($request->get('page'));
        $pagerfanta->setMaxPerPage(50);

        return $this->render('AppBundle:Admin:promo_keys_activated.html.twig', array(
            'pagerfanta' => $pagerfanta,
            'key'        => $key,
        ));
    }

    public function promoKeysFirstAction(Request $request, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        /** @var $key \My\AppBundle\Entity\PromoKey */
        $key = $this->em->find('AppBundle:PromoKey', $id);
        if (!$key) {
            throw $this->createNotFoundException('Promo key for id "'.$id.'" not found.');
        }

        $qb = $this->em->getRepository('AppBundle:User')->createQueryBuilder('u')
            ->leftJoin('u.tried_enters', 'te')
            ->andWhere('te.promo_key = :key_id')->setParameter('key_id', $id)
        ;


        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($request->get('page'));
        $pagerfanta->setMaxPerPage(30);

        return $this->render('AppBundle:Admin:promo_keys_first.html.twig', array(
            'pagerfanta' => $pagerfanta,
            'key'        => $key,
        ));
    }

    public function profileUserViewAction($id)
    {
        if ((false === $this->get('security.context')->isGranted('ROLE_MOD_PARADOX_USERS'))
            && (false === $this->get('security.context')->isGranted('ROLE_MOD_ADD_USER'))
        ) {
            throw $this->createNotFoundException();
        }

        $userRepo = $this->em->getRepository('AppBundle:User');
        $user = $userRepo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User for id "'.$id.'" not found.');
        }

        if ($user == $this->getUser()) {
            throw $this->createNotFoundException('You can\'t moderate yourself');
        }

        return $this->render('AppBundle:Admin:profile_user_view.html.twig', array(
            'user' => $user,
        ));
    }

    public function promoKeysReservedAction(Request $request, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $key = $this->em->find('AppBundle:PromoKey', $id);
        if (!$key) {
            throw $this->createNotFoundException('Promo key for id "'.$id.'" not found.');
        }

        $qb = $this->em->getRepository('PaymentBundle:Log')->createQueryBuilder('pl')
            ->andWhere('pl.promoKey = :promokey')->setParameter('promokey', $key)
            ->addGroupBy('pl.user')
            ->andHaving('SUM(pl.paid)=0')
        ;

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($request->get('page'));
        $pagerfanta->setMaxPerPage(50);

        return $this->render('AppBundle:Admin:promo_keys_reserved.html.twig', array(
            'pagerfanta' => $pagerfanta,
            'key'        => $key,
        ));
    }

    public function promoKeyAddAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $promoKey = new PromoKey();
        $hash = $this->get('app.promo')->generatePromoKeyHashes(1);
        $promoKey->setHash($hash['h0']);

        $form = $this->createForm(new PromoKeyFormType, $promoKey);

        //check promo type
        $requestData = $request->request->get('promo_key');
        if ($requestData['promo']) {
            if ($this->em->getRepository('AppBundle:Promo')->findOneBy(array(
                'id'         => $requestData['promo'],
                'restricted' => 'users',
            ))) {
                $error = 'С данным типом промо-кампании не может быть связано больше одного ключа.';
                $this->get('session')->getFlashBag()->add('error', $error);

                return $this->redirect($this->generateUrl('admin_promo_key_add'));
            }
        }

        $form->handleRequest($request);
        if ($form->isValid()) {
            $this->em->persist($promoKey);
            $this->em->flush();

            $this->get('session')->getFlashBag()->add('success', 'success_added');

            return $this->redirect($this->generateUrl('admin_promo_key_add'));
        }

        return $this->render('AppBundle:Admin:promo_key.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function promoKeyGenerateAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(new PromoKeyGenerateFormType);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $countToGenerate = $form->get('count')->getData();
            $type = $form->get('type')->getData();
            $active = $form->get('active')->getData();
            $discount = $form->get('discount')->getData();

            /** @var $promo \My\AppBundle\Entity\Promo */
            $promo = $form->get('promo')->getData();
            if (!$promo) {
                throw $this->createNotFoundException('Promo not found.');
            } elseif ($promo->getRestricted() == 'users' && count($promo->getKeys()) >= 1) {
                $error = new FormError('С данным типом промо-кампании не может быть связано больше одного ключа.');
                $form->get('promo')->addError($error);
            } else {
                //add new promo keys
                $promoService = $this->get('app.promo');
                $hashes = $promoService->generatePromoKeyHashes($countToGenerate);
                foreach ($hashes as $hash) {
                    $promoKey = new PromoKey();
                    $promoKey->setActive($active);
                    $promoKey->setDiscount($discount);
                    $promoKey->setHash($hash);
                    $promoKey->setPromo($promo);
                    $promoKey->setType($type);
                    $this->em->persist($promoKey);
                }
                $this->em->flush();

                $this->get('session')->getFlashBag()->add('success', 'success_added');

                return $this->redirect($this->generateUrl('admin_promo_key_generate'));
            }
        }

        return $this->render('AppBundle:Admin:promo_key_generate.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function promoKeyDeleteAction($id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $promoKey = $this->em->getRepository('AppBundle:PromoKey')->find($id);
        if (!$promoKey) {
            throw $this->createNotFoundException('Promo key for id "'.$id.'" not found.');
        }

        $flash = $this->get('session')->getFlashBag();

        $isRemovable = $this->em->getRepository('AppBundle:PromoKey')->isRemovable($id);

        if ($isRemovable) {
            $this->em->remove($promoKey);
            $this->em->flush();
            $flash->add('success', 'success_deleted');
        } else {
            $flash->add('error', 'promo_key_error_cant_delete_logs');
        }

        return $this->redirect($this->generateUrl('admin_promo_keys', array('id' => $promoKey->getPromo()->getId())));
    }

    //only site_access and traning types
    public function promoKeyExportAction($promo, $type)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $output = array();
        $condition = array(
            'promo'  => $promo,
            'active' => true,
        );
        if ($type != 'both') {
            $condition['type'] = $type;
        }
        $site_access_counter = 0;
        $training_counter = 0;
        $keys = $this->em->getRepository('AppBundle:PromoKey')->findBy($condition);
        foreach ($keys as $key) { /** @var $key \My\AppBundle\Entity\PromoKey */
            if (count($key->getLogs()) == 0) {
                if ($key->getType() == 'site_access') {
                    $counter = $site_access_counter;
                    $site_access_counter++;
                } elseif ($key->getType() == 'training') {
                    $counter = $training_counter;
                    $training_counter++;
                } else {
                    continue;
                }
                $output[$counter][$key->getType()] = $key->getHash();
            }
        }
        foreach ($output as $num => $line) {
            $output[$num] = implode('/', $line);
        }
        header('Content-type: text/csv');
        header('Content-disposition: attachment;filename=promo_keys_'.$promo.'_'.$type.'.csv');
        exit(implode(";\n", $output));
    }

    //only site_access and traning types
    public function promoKeyExportXlsAction($promo, $type)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $output = array();
        $condition = array(
            'promo'  => $promo,
            'active' => true,
        );
        if ($type != 'both') {
            $condition['type'] = $type;
        }
        $site_access_counter = 0;
        $training_counter = 0;
        $keys = $this->em->getRepository('AppBundle:PromoKey')->findBy($condition);
        foreach ($keys as $key) { /** @var $key \My\AppBundle\Entity\PromoKey */
            if (count($key->getLogs()) == 0) {
                if ($key->getType() == 'site_access') {
                    $counter = $site_access_counter;
                    $site_access_counter++;
                } elseif ($key->getType() == 'training') {
                    $counter = $training_counter;
                    $training_counter++;
                } else {
                    continue;
                }
                $output[$counter][$key->getType()] = $key->getHash();
            }
        }
        foreach ($output as $num => $line) {
            $output[$num] = implode('/', $line);
        }

        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename=list.xls");
        header("Content-Transfer-Encoding: binary ");

        echo pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);
        foreach ($output as $num => $cell) {
            echo pack("ssssss", 0x204, 8 + strlen($cell), $num, 0, 0x0, strlen($cell));
            echo $cell;
        }
        echo pack("ss", 0x0A, 0x00);

        exit();
    }

    public function promoKeysAutoAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $qb = $this->em->getRepository('AppBundle:PromoKey')->createQueryBuilder('pk')
            ->andWhere('pk.source = :source')->setParameter(':source', 'auto_overdue')
            ->orderBy('pk.created', 'DESC');

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($request->get('page'));
        $pagerfanta->setMaxPerPage(50);

        return $this->render('AppBundle:Admin:promo_keys_auto.html.twig', array(
            'pagerfanta' => $pagerfanta,
        ));
    }

    public function promoKeySearchAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $fb = $this->createFormBuilder()
            ->add('key', 'text', array(
                'label'       => 'Ключ',
                'constraints' => new Assert\NotBlank(),
            ))
        ;
        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $key = $this->em->getRepository('AppBundle:PromoKey')->findOneBy(array(
                'hash' => $form->get('key')->getData(),
            ));
            if ($key) {
                return $this->redirect($this->generateUrl('admin_promo_keys_activated', array('id' => $key->getId())));
            }
            $form->get('key')->addError(new FormError('К сожалению, такой ключ не найден.'));
        }

        return $this->render('AppBundle:Admin:promo_key_search.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function usersAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $form_factory = $this->container->get('form.factory');
        /** @var $fb \Symfony\Component\Form\FormBuilder */
        $fb = $form_factory->createNamedBuilder('user', 'form', array(
            'with_roles' => true,
        ), array(
            'csrf_protection' => false,
        ))
            ->add('with_roles', 'checkbox', array('required' => false))
            ->add('email', 'text', array('required' => false))
        ;
        $fb->setMethod('get');
        $filter_form = $fb->getForm();
        $filter_form->handleRequest($request);

        $qb = $this->em->getRepository('AppBundle:User')->createQueryBuilder('u');

        if ($filter_form->get('with_roles')->getData()) {
            $qb
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->like('u.roles', ':role_admin'),
                    $qb->expr()->like('u.roles', ':role_mod'),
                    $qb->expr()->like('u.roles', ':role_tester')
                ))
                ->setParameter(':role_admin', '%"ROLE_ADMIN"%')
                ->setParameter(':role_mod', '%"ROLE_MOD_%')
                ->setParameter(':role_tester', '%"ROLE_TESTER_TRAINING"%')
            ;
        }
        if ($data = $filter_form->get('email')->getData()) {
            $qb->andWhere('u.email LIKE :email')->setParameter(':email', '%'.$data.'%');
        }

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($request->get('page'));

        return $this->render('AppBundle:Admin:users.html.twig', array(
            'pagerfanta'  => $pagerfanta,
            'filter_form' => $filter_form->createView(),
        ));
    }

    public function userEditAction(Request $request, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $user = $this->em->getRepository('AppBundle:User')->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User for id "'.$id.'" not found.');
        }

        if ($user == $this->getUser()) {
            throw $this->createNotFoundException('You can\'t edit yourself');
        }

        $u_roles = array(
            'ROLE_ADMIN'                  => 'ROLE_ADMIN',
            'ROLE_MOD_CONTENT'            => 'ROLE_MOD_CONTENT',
            'ROLE_MOD_PRECHECK_USERS'     => 'ROLE_MOD_PRECHECK_USERS',
            'ROLE_MOD_PARADOX_USERS'      => 'ROLE_MOD_PARADOX_USERS',
            'ROLE_MOD_ADD_USER'           => 'ROLE_MOD_ADD_USER',
            'ROLE_MOD_SUPPORT'            => 'ROLE_MOD_SUPPORT',
            'ROLE_MOD_FINANCE'            => 'ROLE_MOD_FINANCE',
            'ROLE_MOD_REG_STAT'           => 'ROLE_MOD_REG_STAT',
            'ROLE_MOD_API_STAT'           => 'ROLE_MOD_API_STAT',
            'ROLE_MOD_MAILING'            => 'ROLE_MOD_MAILING',
            'ROLE_MOD_MANAGER'            => 'ROLE_MOD_MANAGER',
            'ROLE_MOD_ACCOUNTANT'         => 'ROLE_MOD_ACCOUNTANT',
            'ROLE_MOD_REPRESENTATIVE'     => 'ROLE_MOD_REPRESENTATIVE',
            'ROLE_TESTER_TRAINING'        => 'ROLE_TESTER_TRAINING',
        );

        $u_role_tips = array(
            'ROLE_ADMIN'                  => 'Полный доступ к порталу',
            'ROLE_MOD_CONTENT'            => 'Модерирует',
            'ROLE_MOD_PRECHECK_USERS'     => 'Проверяет пользователей',
            'ROLE_MOD_PARADOX_USERS'      => 'Редактирование пользователей Paradox',
            'ROLE_MOD_ADD_USER'           => 'Возможность добавления пользователей в систему через разделы "Добавить" и 
            "Добавить быстро", "Добавить старых пользователей"',
            'ROLE_MOD_SUPPORT'            => 'Возможность просматривать и отвечать на запросы пользователей в форме
             обратной связи. Перед сохранением необходимо выбрать хотя бы одну категорию поддержки',
            'ROLE_MOD_FINANCE'            => 'Редактирование финансовой информации',
            'ROLE_MOD_REG_STAT'           => 'Просмотр статистики регистраций',
            'ROLE_MOD_API_STAT'           => 'Просмотр статистики по API',
            'ROLE_MOD_MAILING'            => 'Возможность создавать и отправлять информационные рассылки пользователям
             портала',
            'ROLE_MOD_MANAGER'            => 'Управляет информацией сайта',
            'ROLE_MOD_ACCOUNTANT'         => 'Возможность делать возвраты денежных средств пользователям, совершившим 
            оплату',
            'ROLE_MOD_REPRESENTATIVE'     => 'Представитель рыбинской автошколы',
            'ROLE_TESTER_TRAINING'        => 'Тестирует программу обучения',
        );


        $regions = $this->em->getRepository('AppBundle:Region')->findAll();

        $virt = array();
        $notVirt = array();
        foreach ($regions as $region) {
            /** @var $region \My\AppBundle\Entity\Region */
            $id = $region->getId();
            if ($region->getFilialNotExisting()) {
                $virt[] = $id;
            } else {
                $notVirt[] = $id;
            }
        }

        $form_factory = $this->container->get('form.factory');
        $fb = $form_factory->createNamedBuilder('user', 'form', $user, array('validation_groups' => false));
        $fb->add('u_roles', 'choice', array(
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'choices'  => $u_roles,
        ));
        $fb->add('u_white_ips', 'textarea', array(
            'required' => false,
            'help'     => 'user_u_white_ips_help',
        ));
        $fb->add('moderated_support_categories', 'entity', array(
            'class'         => 'AppBundle:SupportCategory',
            'multiple'      => true,
            'expanded'      => true,
            'required'      => false,
            'query_builder' => function (EntityRepository $er) use ($user) {
                return $er->createQueryBuilder('sc')
                    ->andWhere('sc.parent IS NOT NULL')
                    ->orderBy('sc.createdAt')
                    ->orderBy('sc.parent')
                ;
            },
        ));
        $fb->add('manager_regions', 'entity', array(
            'class'    => 'My\AppBundle\Entity\Region',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
        ));

        $fb->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            /** @var  $user \My\AppBundle\Entity\User */
            $user = $form->getData();
            $roles = $user->getRoles();
            if (!in_array('ROLE_MOD_MANAGER', $roles)) {
                $regions = $this->em->getRepository('AppBundle:Region')->findAll();
                foreach ($regions as $region) {
                    $user->removeManagerRegion($region);
                }
                $this->em->persist($user);
                $this->em->flush();
            }
        });

        $form = $fb->getForm();

        $form->handleRequest($request);
        if ($form->isValid()) {
            $this->em->persist($user);
            $this->em->flush();

            return $this->redirect($this->generateUrl('admin_user_edit', array('id' => $user->getId())));
        }

        return $this->render('AppBundle:Admin:user.html.twig', array(
            'form'             => $form->createView(),
            'user'             => $user,
            'virt_filials'     => json_encode($virt),
            'not_virt_filials' => json_encode($notVirt),
            'u_role_tips'      => $u_role_tips,
        ));
    }

    public function precheckUsersAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_PRECHECK_USERS')
            and false === $this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')) {
            throw $this->createNotFoundException();
        }

        $categories = $this->em->getRepository('AppBundle:Category')->findAll();
        $services = $this->em->getRepository('AppBundle:Service')->findAll();
        $subjects = $this->em->getRepository('AppBundle:Subject')->findAll();

        $paids = array(
            'nopaid' => 'paids.nopaid',
            'paid_1' => 'paids.paid_1',
            'paid_2' => 'paids.paid_2',
        );

        $additional_services = array();
        foreach ($services as $service) {
            if (!$service->getType()) {
                $additional_services[$service->getId()] = $service->getName();
            }
        }

        $regions_choices = [];
        $regions = $this->em->getRepository('AppBundle:Region')->findAll();
        foreach ($regions as $region) {
            $regions_choices[$region->getId()] = $region->getName();
        }

        $exams = array();
        foreach ($subjects as $subject) {
            /** @var $subject \My\AppBundle\Entity\Subject */
            $exams[$subject->getId()] = $subject->getTitle();
        }

        if ($this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')) {
            $defaultRegion = $this->em->getRepository('AppBundle:Region')->findOneBy(['name' => 'Рыбинск']);
        } else {
            // default value — first region
            $defaultRegion = $this->em->getRepository('AppBundle:Region')->createQueryBuilder('r')
                ->setMaxResults(1)
                ->getQuery()->getSingleResult();
        }

        $form_factory = $this->container->get('form.factory');
        /** @var $fb \Symfony\Component\Form\FormBuilder */

        $fb = $form_factory->createNamedBuilder('user', 'form', array(), array('csrf_protection' => false))
            ->add('any_paid', 'checkbox', array(
                'required' => false,
                'data'     => true,
            ))
            ->add('paids', 'choice', array(
                'required'    => false,
                'empty_value' => 'choose_option',
                'choices'     => $paids,
            ));

        if (false === $this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')) {
            $fb->add('additional_paids', 'choice', array(
                'required' => false,
                'multiple' => true,
                'choices'  => $additional_services,
            ));
        };

        $fb->add('category', 'entity', array(
            'class'       => 'AppBundle:Category',
            'required'    => false,
            'empty_value' => 'choose_option',
        ))
        ->add('phone_home', 'text', array('required' => false))
        ->add('phone_mobile', 'text', array('required' => false))
        ->add('passport_number', 'text', array('required' => false))
        ->add('birthday', 'birthday', array(
            'years'       => range(1930, date('Y')),
            'required'    => false,
            'empty_value' => '--',
        ))
        ->add('last_name', 'text', array('required' => false))
        ->add('first_name', 'text', array('required' => false))
        ->add('patronymic', 'text', array('required' => false));

        if (false === $this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')) {
            $fb->add('region', 'choice', array(
                'required' => false,
                'multiple' => true,
                'choices'  => $regions_choices,
                'data'     => [$defaultRegion->getId()],
            ));
        }

        $fb->add('email', 'text', array('required' => false))
        ->add('phone_mobile_confirmed', 'choice', array(
            'required'    => false,
            'empty_value' => 'choose_option',
            'choices'     => array(
                'yes' => 'yes',
                'no'  => 'no',
            ),
        ))
        ->add('show_from', 'date', array(
            'years'       => range(2010, date('Y') + 1),
            'required'    => false,
            'empty_value' => '--',
        ))
        ->add('show_to', 'date', array(
            'years'       => range(2010, date('Y') + 1),
            'required'    => false,
            'empty_value' => '--',
        ))
        ->add('exams', 'choice', array(
            'required' => false,
            'multiple' => true,
            'expanded' => true,
            'choices'  => $exams,
        ))
        ->add('final_exam', 'checkbox', array('required' => false))
        ->add('mailing', 'choice', $this->getYesNoChoiceParams())
        ->add('payment_1_from', 'date', array(
            'years'       => range(2010, date('Y') + 1),
            'required'    => false,
            'empty_value' => '--',
        ))
        ->add('payment_1_to', 'date', array(
            'years'       => range(2010, date('Y') + 1),
            'required'    => false,
            'empty_value' => '--',
        ))
        ;
        $fb->setMethod('get');
        $filter_form = $fb->getForm();
        $filter_form->handleRequest($request);

        $qb = $this->em->getRepository('AppBundle:User')->createQueryBuilder('u')
            ->andWhere('u.moderated != :moderated')->setParameter(':moderated', true)
            ->andWhere('u.roles LIKE :paid')->setParameter(':paid', '%"ROLE_USER_PAID"%')
            ->andWhere('u.roles NOT LIKE :role_admin')->setParameter(':role_admin', '%"ROLE_ADMIN"%')
            ->andWhere('u.roles NOT LIKE :role_mod')->setParameter(':role_mod', '%"ROLE_MOD_%')
            ->orderBy('u.created_at')
            ->groupBy('u.id')
        ;
        if ($data = $filter_form->get('paids')->getData()) {
            if ($data == 'nopaid') {
                $qb
                    ->andWhere('u.roles NOT LIKE :paid1')->setParameter(':paid1', '%"ROLE_USER_PAID"%')
                    ->andWhere('u.roles NOT LIKE :paid2')->setParameter(':paid2', '%"ROLE_USER_PAID2"%')
                ;
            } elseif ($data == 'paid_1') {
                $qb
                    ->andWhere('u.roles LIKE :paid1')->setParameter(':paid1', '%"ROLE_USER_PAID"%')
                    ->andWhere('u.roles NOT LIKE :paid2')->setParameter(':paid2', '%"ROLE_USER_PAID2"%')
                ;
            } elseif ($data == 'paid_2') {
                $qb->andWhere('u.roles LIKE :paid2')->setParameter(':paid2', '%"ROLE_USER_PAID2"%');
            }
        }
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')
                and $data = $filter_form->get('additional_paids')->getData()
            ) {
            foreach ($filter_form->get('additional_paids')->getData() as $num => $paid) {
                $qb
                    ->leftJoin('u.payment_logs', 'log'.$num)
                    ->andWhere('regexp(:additional_paids'.$num.', log'.$num.'.comment) != false')
                    ->setParameter(':additional_paids'.$num, '"services":"[0-9,]*'.$paid.'[0-9,]*"')
                ;
            }
        }

        if ($data = $filter_form->get('any_paid')->getData()) {

            $aServices = $this->em->getRepository('AppBundle:Service')->createQueryBuilder('s')
                ->addSelect('rp.price')
                ->leftJoin('s.regions_prices', 'rp')
                ->andWhere('rp.active = :active')->setParameter(':active', true)
                ->andWhere('s.type != :type')->setParameter(':type', 'site_access')
                ->andWhere('s.type IS NOT NULL')
                ->getQuery()->execute();

            $serviceIds = array();
            foreach ($aServices as $service) {
                /** @var $s \My\AppBundle\Entity\Service */
                $s = $service[0];
                $serviceIds[] = $s->getId();
            }

            $category_string = '"categories"';
            $service_string  = '"services":"' . implode(',', $serviceIds) . '"';

            $qb->leftJoin('u.payment_logs', 'log')
                ->andWhere('log.sum > 0')
                ->andWhere('log.paid = 1')
                ->andWhere('log.s_type IN (:types)')->setParameter('types', ['robokassa', 'psb'])
                ->andWhere('log.comment LIKE :service_string OR log.comment LIKE :category_string')
                ->setParameter('service_string', '%'.$service_string.'%')
                ->setParameter('category_string', '%'.$category_string.'%')
            ;

        }

        if ($data = $filter_form->get('category')->getData()) {
            $qb->andWhere('u.category = :category')->setParameter(':category', $data);
        }
        if ($data = $filter_form->get('phone_home')->getData()) {
            $qb->andWhere('u.phone_home LIKE :phone_home')->setParameter(':phone_home', '%'.$data.'%');
        }
        if ($data = $filter_form->get('phone_mobile')->getData()) {
            $qb->andWhere('u.phone_mobile LIKE :phone_mobile')->setParameter(':phone_mobile', '%'.$data.'%');
        }
        if ($data = $filter_form->get('passport_number')->getData()) {
            $qb->andWhere('u.passport_number LIKE :passport_number')->setParameter(':passport_number', '%'.$data.'%');
        }
        if ($data = $filter_form->get('birthday')->getData()) {
            $qb->andWhere('u.birthday = :birthday')->setParameter(':birthday', $data);
        }
        if ($data = $filter_form->get('last_name')->getData()) {
            $qb->andWhere('u.last_name LIKE :last_name')->setParameter(':last_name', '%'.$data.'%');
        }
        if ($data = $filter_form->get('first_name')->getData()) {
            $qb->andWhere('u.first_name LIKE :first_name')->setParameter(':first_name', '%'.$data.'%');
        }
        if ($data = $filter_form->get('patronymic')->getData()) {
            $qb->andWhere('u.patronymic LIKE :patronymic')->setParameter(':patronymic', '%'.$data.'%');
        }

        if ($this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')) {
            $qb->leftJoin('u.region', 'r')->addSelect('r')
                ->andWhere('r.name = :region_name')->setParameter('region_name', 'Рыбинск');
        } else {
            if ($data = $filter_form->get('region')->getData()) {
                $qb->leftJoin('u.region', 'r')->addSelect('r')
                    ->andWhere('r.id IN (:region_ids)')->setParameter('region_ids', $data);
            }
        }

        if ($data = $filter_form->get('email')->getData()) {
            $qb->andWhere('u.email LIKE :email')->setParameter(':email', '%'.$data.'%');
        }
        if ($data = $filter_form->get('phone_mobile_confirmed')->getData()) {
            if ($data == 'yes') {
                $qb
                    ->andWhere('u.phone_mobile_status = :phone_mobile_status')
                    ->setParameter(':phone_mobile_status', 'confirmed')
                ;
            } else {
                $qb
                    ->andWhere('u.phone_mobile_status != :phone_mobile_status')
                    ->setParameter(':phone_mobile_status', 'confirmed')
                ;
            }
        }
        if ($data = $filter_form->get('show_from')->getData()) {
            $qb->andWhere('u.created_at >= :show_from')->setParameter(':show_from', $data);
        }
        if ($data = $filter_form->get('show_to')->getData()) {
            $qb->andWhere('u.created_at <= :show_to')->setParameter(':show_to', $data);
        }
        if ($data = $filter_form->get('exams')->getData()) {
            foreach ($data as $eid) {
                $qb2 = $this->em->getRepository('AppBundle:ExamLog')->createQueryBuilder('el_'.$eid)
                    ->andWhere('el_'.$eid.'.user = u')
                    ->andWhere('el_'.$eid.'.subject = :el_subject_'.$eid)
                    ->andWhere('el_'.$eid.'.passed = :el_passed_'.$eid)
                ;
                $qb
                    ->setParameter(':el_subject_'.$eid, $eid)
                    ->setParameter(':el_passed_'.$eid, true)
                    ->andWhere($qb->expr()->exists($qb2))
                ;
            }
        }
        if (($data = $filter_form->get('final_exam')->getData()) && $data) {
            $qb
                ->leftJoin('u.final_exams_logs', 'fel')
                ->andWhere('fel.passed = :fel_passed')->setParameter(':fel_passed', true)
            ;
        }
        if ($data = $filter_form->get('mailing')->getData()) {
            $qb->andWhere('u.mailing = :mailing')->setParameter(':mailing', ($data == 'yes'));
        }
        if ($data = $filter_form->get('payment_1_from')->getData()) {
            $qb->andWhere('u.payment_1_paid >= :payment_1_from')->setParameter(':payment_1_from', $data);
        }
        if ($data = $filter_form->get('payment_1_to')->getData()) {
            $qb->andWhere('u.payment_1_paid <= :payment_1_to')->setParameter(':payment_1_to', $data);
        }

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($request->get('page'));

        foreach ($pagerfanta->getIterator() as $user) { /** @var $user \My\AppBundle\Entity\User */
            $user->forcePromoInfo($categories, $services);
        }

        $filterHrefs = [];
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')) {
            $region_moscow = $this->em->getRepository('AppBundle:Region')->findOneBy(['name' => 'Москва']);

            $region_not_moscow = $this->em->getRepository('AppBundle:Region')->createQueryBuilder('r')
                ->andWhere('r.name != :name')->setParameter('name', 'Москва')
                ->andWhere('r.filial_not_existing = 0')
                ->getQuery()->getResult();
            $region_not_moscow_array = [];
            foreach ($region_not_moscow as $rnm) { /** @var $rnm \My\AppBundle\Entity\Region */
                $region_not_moscow_array[] = 'user[region][]='.$rnm->getId();
            }
            $region_not_moscow_path = implode('&', $region_not_moscow_array);

            $region_filial_not_existing = $this->em->getRepository('AppBundle:Region')->createQueryBuilder('r')
                ->andWhere('r.name != :name')->setParameter('name', 'Москва')
                ->andWhere('r.filial_not_existing = 1')
                ->getQuery()->getResult();
            $region_filial_not_existing_array = [];
            foreach ($region_filial_not_existing as $rfne) { /** @var $rfne \My\AppBundle\Entity\Region */
                $region_filial_not_existing_array[] = 'user[region][]='.$rfne->getId();
            }
            $region_filial_not_existings_path = implode('&', $region_filial_not_existing_array);

            $startUrl    = $this->generateUrl('admin_precheck_users').'?user[any_paid]=1';
            $filterHrefs = [
                'moscow'              => $startUrl.'&user[region][]='.$region_moscow->getId(),
                'not_moscow'          => $startUrl.'&'.$region_not_moscow_path,
                'filial_not_existing' => $startUrl.'&'.$region_filial_not_existings_path,
            ];
        }

        return $this->render('AppBundle:Admin:precheck_users.html.twig', array(
            'pagerfanta'     => $pagerfanta,
            'filter_form'    => $filter_form->createView(),
            'default_region' => $defaultRegion,
            'filter_hrefs'   => $filterHrefs,
        ));
    }

    public function precheckUserViewAction(Request $request, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_PRECHECK_USERS')
            and false === $this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')) {
            throw $this->createNotFoundException();
        }

        $userRepo = $this->em->getRepository('AppBundle:User');
        $user = $userRepo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User for id "'.$id.'" not found.');
        }

        if ($user == $this->getUser()) {
            throw $this->createNotFoundException('You can\'t moderate yourself');
        }

        if ($request->isMethod('post')) {
            $user->setModerated(true);
            $this->em->persist($user);
            $this->em->flush();

            return $this->redirect($this->generateUrl('admin_precheck_users'));
        }

        $categories = array();
        $categories_orig = $this->em->getRepository('AppBundle:Category')->createQueryBuilder('c')
            ->getQuery()->execute();
        foreach ($categories_orig as $category) { /** @var $category \My\AppBundle\Entity\Category */
            $categories[$category->getId()] = $category;
        }

        $services = array();
        $services_orig = $this->em->getRepository('AppBundle:Service')->createQueryBuilder('s')
            ->getQuery()->execute();
        foreach ($services_orig as $service) { /** @var $service \My\AppBundle\Entity\Service */
            $services[$service->getId()] = $service;
        }

        $paid_owe_stage = [];
        $payments = array();
        $logs = $this->em->getRepository('PaymentBundle:Log')->createQueryBuilder('l')
            ->andWhere('l.user = :user')->setParameter(':user', $user)
            ->andWhere('l.paid = :paid')->setParameter(':paid', true)
            ->leftJoin('l.promoKey', 'pk')
            ->addSelect('pk')
            ->leftJoin('pk.promo', 'p')
            ->addSelect('p')
            ->leftJoin('l.owe_stage', 'os')
            ->addSelect('os')
            ->leftJoin('l.package', 'lp')
            ->addSelect('lp')
            ->leftJoin('lp.condition', 'lpc')
            ->addSelect('lpc')
            ->addOrderBy('l.updated_at', 'ASC')
            ->getQuery()->getArrayResult();

        foreach ($logs as $log) {

            $comment = json_decode($log['comment'], true);
            $log['categories'] = array();
            $log['services'] = array();
            $log['driving_name'] = null;
            $log['driving_primary'] = null;

            if ($log['package']) {
                $log['driving_name'] = $log['package']['condition']['name'];
                $log['driving_primary'] = $log['package']['condition']['is_primary'];
            }

            //Модератор, который добавил пользователя
            $moderatorName = null;
            if (!empty($comment['moderator_id'])) {
                /** @var $moderator \My\AppBundle\Entity\User */
                $moderator = $userRepo->find($comment['moderator_id']);
                if ($moderator) {
                    $moderatorName = $moderator->getFullName();
                }
            }

            if (!empty($comment['categories'])) {
                $categories_ids = explode(',', $comment['categories']);
                foreach ($categories_ids as $category_id) {
                    if (isset($categories[$category_id])) {
                        $log['categories'][$category_id] = $categories[$category_id];
                    }
                }
                if (count($log['categories']) > 0) {
                    if ($moderatorName) {
                        $log['moderator_name'] = $moderatorName;
                    }
                    if (!empty($comment['auto_promo'])) {
                        $log['auto_promo'] = $comment['auto_promo'];
                    }
                    $payments[] = $log;
                }
            }

            if (!empty($comment['services'])) {
                $services_ids = explode(',', $comment['services']);
                foreach ($services_ids as $service_id) {
                    if (isset($services[$service_id])) {
                        $log['services'][$service_id] = $services[$service_id];
                        $log['required'] = true;
                    } else {
                        /** @CAUTION наследие %) */
                        $log['services'][$service_id] = array('name' => 'Доступ к теоретическому курсу');
                    }
                }
                if (count($log['services']) > 0) {
                    if ($moderatorName) {
                        $log['moderator_name'] = $moderatorName;
                    }
                    $payments[] = $log;
                }
            }

            if (!empty($comment['owe_stage'])) {
                $paid_owe_stage = [];
                if ($moderatorName) {
                    $log['moderator_name'] = $moderatorName;
                }
                $paid_owe_stage[$log['id']] = 'Должник этап №'.$log['owe_stage']['number_stage'];
                $payments[] = $log;
            }
        }

        $version = $this->em->getRepository('AppBundle:TrainingVersion')->createQueryBuilder('v')
            ->andWhere('v.category = :category')->setParameter(':category', $user->getCategory())
            ->andWhere('v.start_date <= :start_date')
            ->setParameter(':start_date', date_format($user->getCreatedAt(), 'Y-m-d'))
            ->addOrderBy('v.start_date', 'DESC')
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();

        $subjects_repository = $this->em->getRepository('AppBundle:Subject');
        $subjects = $subjects_repository->findAllAsArray($user, $version);

        $final_exams_logs_repository = $this->em->getRepository('AppBundle:FinalExamLog');
        $passed_date = $final_exams_logs_repository->getPassedDate($user);
        $is_passed = (bool)$passed_date;

        return $this->render('AppBundle:Admin:precheck_user_view.html.twig', array(
            'user'           => $user,
            'payments'       => $payments,
            'subjects'       => $subjects,
            'passed_date'    => $passed_date,
            'is_passed'      => $is_passed,
            'paid_owe_stage' => $paid_owe_stage ? $paid_owe_stage : null,
        ));
    }

    public function precheckUserEditAction(Request $request, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_PRECHECK_USERS')) {
            throw $this->createNotFoundException();
        }

        $user = $this->em->getRepository('AppBundle:User')->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User for id "'.$id.'" not found.');
        }

        if ($user == $this->getUser()) {
            throw $this->createNotFoundException('You can\'t edit yourself');
        }

        $regionTree = [];
        $regionTreeSource = $this->em->getRepository('AppBundle:Region')->createQueryBuilder('r')
            ->leftJoin('r.places', 'rp')
            ->leftJoin('r.categories_prices', 'cp', 'WITH', 'cp.active = 1')
            ->addSelect('cp')
            ->getQuery()->getResult();

        /** @var $region \My\AppBundle\Entity\Region*/
        foreach ($regionTreeSource as $region) {
            $regionId = $region->getId();
            if (!isset($regionTree[$regionId])) {
                $regionTree[$regionId] = ['name' => $region->getName(), 'cats' => []];
            }

            /** @var $categoryPrice \My\AppBundle\Entity\CategoryPrice */
            foreach ($region->getCategoriesPrices() as $categoryPrice) {
                $cat = $categoryPrice->getCategory();
                $regionTree[$regionId]['cats'][$cat->getId()] = ['name' => $cat->getName()];
            }
        }

        /** @var $form \Symfony\Component\Form\Form */
        $form = $this->createForm(new ProfileFormType(), $user)
            ->add('category', 'entity', array(
                'class'       => 'AppBundle:Category',
                'required'    => true,
                'empty_value' => 'choose_option',
            ))
        ;

        if ($request->isMethod('post')) {
            $form->handleRequest($request);

            $region = $user->getRegion();

            $categoryField = $form->get('category');
            /** @var $category \My\AppBundle\Entity\Category */
            $category = $categoryField->getData();

            if ($region && $category) {
                if (!isset($regionTree[$region->getId()]['cats'][$category->getId()])) {
                    $categoryField->addError(new FormError('Неверная категория.'));
                }
            }

            $validator = $this->get('validator');
            $not_registration = $form->get('not_registration')->getData();
            if ($not_registration) {
                $names = array(
                    'place_country',
                    'place_region',
                    'place_city',
                    'place_street',
                    'place_house',
                    'place_apartament',
                );
            } else {
                $names = array(
                    'registration_country',
                    'registration_region',
                    'registration_city',
                    'registration_street',
                    'registration_house',
                    'registration_apartament',
                );
            }
            foreach ($names as $name) {
                $field = $form->get($name);
                $errors = $validator->validateValue($field->getData(), new Assert\NotBlank());
                if (count($errors) > 0) {
                    $field->addError(new FormError($errors->get(0)->getMessage()));
                }
            }

            if ($form->isValid()) {
                $user->addRole('ROLE_USER_FULL_PROFILE');

                $this->em->persist($user);
                $this->em->flush();

                return $this->redirect($this->generateUrl('admin_precheck_user_view', array('id' => $user->getId())));
            }
        }

        return $this->render('AppBundle:Admin:precheck_user_edit.html.twig', array(
            'form' => $form->createView(),
            'user' => $user,
            'region_tree'  => $regionTree,
        ));
    }

    public function paradoxUsersAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_PARADOX_USERS')) {
            throw $this->createNotFoundException();
        }

        $categories = $this->em->getRepository('AppBundle:Category')->findAll();
        $services = $this->em->getRepository('AppBundle:Service')->findAll();

        $paids = array(
            'nopaid' => 'paids.nopaid',
            'paid_1' => 'paids.paid_1',
            'paid_2' => 'paids.paid_2',
        );

        $exams = array();
        $subjects = $this->em->getRepository('AppBundle:Subject')->findAll();
        foreach ($subjects as $subject) { /** @var $subject \My\AppBundle\Entity\Subject */
            $exams[$subject->getId()] = $subject->getTitle();
        }

        $additional_services = array();
        foreach ($services as $service) { /** @var $service \My\AppBundle\Entity\Service */
            if (!$service->getType()) {
                $additional_services[$service->getId()] = $service->getName();
            }
        }

        // default value — first region
        $defaultRegion = $this->em->getRepository('AppBundle:Region')->createQueryBuilder('r')
            ->setMaxResults(1)
            ->getQuery()->getSingleResult();

        $form_factory = $this->container->get('form.factory');
        /** @var $fb \Symfony\Component\Form\FormBuilder */
        $fb = $form_factory->createNamedBuilder('user', 'form', array(), array('csrf_protection' => false))
            ->add('all', 'checkbox', array('required' => false))
            ->add('offline', 'choice', array(
                'label'   => 'В офисе',
                'choices' => array(
                    'hide' => 'Не отображать',
                    'show' => 'Отображать',
                    'only' => 'Отображать только их',
                ),
            ))
            ->add('paids', 'choice', array(
                'required'    => false,
                'empty_value' => 'choose_option',
                'choices'     => $paids,
            ))
            ->add('additional_paids', 'choice', array(
                'required' => false,
                'multiple' => true,
                'choices'  => $additional_services,
            ))
            ->add('category', 'entity', array(
                'class'       => 'AppBundle:Category',
                'required'    => false,
                'empty_value' => 'choose_option',
            ))
            ->add('phone_home', 'text', array('required' => false))
            ->add('phone_mobile', 'text', array('required' => false))
            ->add('passport_number', 'text', array('required' => false))
            ->add('birthday', 'birthday', array(
                'years'       => range(1930, date('Y')),
                'required'    => false,
                'empty_value' => '--',
            ))
            ->add('last_name', 'text', array('required' => false))
            ->add('first_name', 'text', array('required' => false))
            ->add('patronymic', 'text', array('required' => false))
            ->add('region', 'entity', array(
                'class'       => 'AppBundle:Region',
                'required'    => false,
                'empty_value' => 'choose_option',
                'data'        => $defaultRegion,
            ))
            ->add('email', 'text', array('required' => false))
            ->add('webgroup', 'entity', array(
                'class'       => 'AppBundle:Webgroup',
                'required'    => false,
                'empty_value' => 'choose_option',
            ))
            ->add('paradox_id', 'text', array('required' => false))
            ->add('phone_mobile_confirmed', 'choice', array(
                'required'    => false,
                'empty_value' => 'choose_option',
                'choices'     => array(
                    'yes' => 'yes',
                    'no'  => 'no',
                ),
            ))
            ->add('show_from', 'date', array(
                'years'       => range(2010, date('Y') + 1),
                'required'    => false,
                'empty_value' => '--',
            ))
            ->add('show_to', 'date', array(
                'years'       => range(2010, date('Y') + 1),
                'required'    => false,
                'empty_value' => '--',
            ))
            ->add('exams', 'choice', array(
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices'  => $exams,
            ))
            ->add('final_exam', 'checkbox', array('required' => false))
            ->add('in_paradox', 'checkbox', array('required' => false))
            ->add('mailing', 'choice', $this->getYesNoChoiceParams())
            ->add('payment_1_from', 'date', array(
                'years'       => range(2010, date('Y') + 1),
                'required'    => false,
                'empty_value' => '--',
            ))
            ->add('payment_1_to', 'date', array(
                'years'       => range(2010, date('Y') + 1),
                'required'    => false,
                'empty_value' => '--',
            ))
            ->add('not_paid_driving', 'checkbox', array('required' => false))
            ->add('expired_owe_stage', 'checkbox', array('required' => false))
            ->add('exist_paid_driving', 'checkbox', array('required' => false))
        ;
        $fb->setMethod('get');
        $filter_form = $fb->getForm();
        $filter_form->handleRequest($request);

        $qb = $this->em->getRepository('AppBundle:User')->createQueryBuilder('u')
            ->orderBy('u.created_at')
            ->groupBy('u.id')
        ;

        $data = $filter_form->get('all')->getData();
        if (!$data) {
            $qb
                ->andWhere('u.moderated = :moderated')->setParameter(':moderated', true)
                ->andWhere('u.paradox_id IS NULL')
                ->andWhere('u.roles NOT LIKE :role_admin')->setParameter(':role_admin', '%"ROLE_ADMIN"%')
                ->andWhere('u.roles NOT LIKE :role_mod')->setParameter(':role_mod', '%"ROLE_MOD_%')
            ;
        }
        if ($data = $filter_form->get('offline')->getData()) {
            if ($data == 'show') {
            } elseif ($data == 'only') {
                $qb->andWhere('u.offline = :offline')->setParameter('offline', true);
            } else {
                $qb->andWhere('u.offline = :offline')->setParameter('offline', false);
            }
        }
        if ($data = $filter_form->get('paids')->getData()) {
            if ($data == 'nopaid') {
                $qb
                    ->andWhere('u.roles NOT LIKE :paid1')->setParameter(':paid1', '%"ROLE_USER_PAID"%')
                    ->andWhere('u.roles NOT LIKE :paid2')->setParameter(':paid2', '%"ROLE_USER_PAID2"%')
                ;
            } elseif ($data == 'paid_1') {
                $qb
                    ->andWhere('u.roles LIKE :paid1')->setParameter(':paid1', '%"ROLE_USER_PAID"%')
                    ->andWhere('u.roles NOT LIKE :paid2')->setParameter(':paid2', '%"ROLE_USER_PAID2"%')
                ;
            } elseif ($data == 'paid_2') {
                $qb->andWhere('u.roles LIKE :paid2')->setParameter(':paid2', '%"ROLE_USER_PAID2"%');
            }
        }
        if ($data = $filter_form->get('additional_paids')->getData()) {
            foreach ($filter_form->get('additional_paids')->getData() as $num => $paid) {
                $qb
                    ->leftJoin('u.payment_logs', 'log'.$num)
                    ->andWhere('regexp(:additional_paids'.$num.', log'.$num.'.comment) != false')
                    ->setParameter(':additional_paids'.$num, '"services":"[0-9,]*'.$paid.'[0-9,]*"')
                ;
            }
        }
        if ($data = $filter_form->get('category')->getData()) {
            $qb->andWhere('u.category = :category')->setParameter(':category', $data);
        }
        if ($data = $filter_form->get('phone_home')->getData()) {
            $qb->andWhere('u.phone_home LIKE :phone_home')->setParameter(':phone_home', '%'.$data.'%');
        }
        if ($data = $filter_form->get('phone_mobile')->getData()) {
            $qb->andWhere('u.phone_mobile LIKE :phone_mobile')->setParameter(':phone_mobile', '%'.$data.'%');
        }
        if ($data = $filter_form->get('passport_number')->getData()) {
            $qb->andWhere('u.passport_number LIKE :passport_number')->setParameter(':passport_number', '%'.$data.'%');
        }
        if ($data = $filter_form->get('birthday')->getData()) {
            $qb->andWhere('u.birthday = :birthday')->setParameter(':birthday', $data);
        }
        if ($data = $filter_form->get('last_name')->getData()) {
            $qb->andWhere('u.last_name LIKE :last_name')->setParameter(':last_name', '%'.$data.'%');
        }
        if ($data = $filter_form->get('first_name')->getData()) {
            $qb->andWhere('u.first_name LIKE :first_name')->setParameter(':first_name', '%'.$data.'%');
        }
        if ($data = $filter_form->get('patronymic')->getData()) {
            $qb->andWhere('u.patronymic LIKE :patronymic')->setParameter(':patronymic', '%'.$data.'%');
        }
        if ($data = $filter_form->get('region')->getData()) {
            $qb->andWhere('u.region = :region')->setParameter(':region', $data);
        }
        if ($data = $filter_form->get('email')->getData()) {
            $qb->andWhere('u.email LIKE :email')->setParameter(':email', '%'.$data.'%');
        }
        if ($data = $filter_form->get('webgroup')->getData()) {
            $qb->andWhere('u.webgroup = :webgroup')->setParameter(':webgroup', $data);
        }
        if ($data = $filter_form->get('paradox_id')->getData()) {
            $qb->andWhere('u.paradox_id = :paradox_id')->setParameter(':paradox_id', $data);
        }
        if ($data = $filter_form->get('phone_mobile_confirmed')->getData()) {
            if ($data == 'yes') {
                $qb
                    ->andWhere('u.phone_mobile_status = :phone_mobile_status')
                    ->setParameter(':phone_mobile_status', 'confirmed')
                ;
            } else {
                $qb
                    ->andWhere('u.phone_mobile_status != :phone_mobile_status')
                    ->setParameter(':phone_mobile_status', 'confirmed')
                ;
            }
        }
        if ($data = $filter_form->get('show_from')->getData()) {
            $qb->andWhere('u.created_at >= :show_from')->setParameter(':show_from', $data);
        }
        if ($data = $filter_form->get('show_to')->getData()) {
            $qb->andWhere('u.created_at <= :show_to')->setParameter(':show_to', $data);
        }
        if ($data = $filter_form->get('exams')->getData()) {
            foreach ($data as $eid) {
                $qb2 = $this->em->getRepository('AppBundle:ExamLog')->createQueryBuilder('el_'.$eid)
                    ->andWhere('el_'.$eid.'.user = u')
                    ->andWhere('el_'.$eid.'.subject = :el_subject_'.$eid)
                    ->andWhere('el_'.$eid.'.passed = :el_passed_'.$eid)
                ;
                $qb
                    ->setParameter(':el_subject_'.$eid, $eid)
                    ->setParameter(':el_passed_'.$eid, true)
                    ->andWhere($qb->expr()->exists($qb2))
                ;
            }
        }
        if (($data = $filter_form->get('final_exam')->getData()) && $data) {
            $qb
                ->leftJoin('u.final_exams_logs', 'fel')
                ->andWhere('fel.passed = :fel_passed')->setParameter(':fel_passed', true)
            ;
        }
        if (($data = $filter_form->get('in_paradox')->getData()) && $data) {
            $qb->andWhere('u.paradox_id IS NOT NULL');
        }
        if ($data = $filter_form->get('mailing')->getData()) {
            $qb->andWhere('u.mailing = :mailing')->setParameter(':mailing', ($data == 'yes'));
        }
        if ($data = $filter_form->get('payment_1_from')->getData()) {
            $qb->andWhere('u.payment_1_paid >= :payment_1_from')->setParameter(':payment_1_from', $data);
        }
        if ($data = $filter_form->get('payment_1_to')->getData()) {
            $qb->andWhere('u.payment_1_paid <= :payment_1_to')->setParameter(':payment_1_to', $data);
        }
        if ($data = $filter_form->get('expired_owe_stage')->getData()) {
            $qb->andWhere('u.owe_stage_end < :date')->setParameter('date', new \DateTime())
                ->andWhere('u.driving_paid_at IS NULL');
        }
        if ($data = $filter_form->get('not_paid_driving')->getData()) {
            $qb->andWhere('u.driving_paid_at IS NULL');
        }

        if ($data = $filter_form->get('exist_paid_driving')->getData()) {
            $qb->andWhere('u.owe_stage_end IS NOT NULL')
                ->andWhere('u.driving_paid_at IS NOT NULL');
        }

        if ($request->get('csv')) {
            $response = new StreamedResponse();
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="paradox_users.csv"');
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            $response->setCharset('utf8');
            $response->setCallback(function () use ($qb) {
                $handle = fopen('php://output', 'w+');
                fputcsv($handle, [
                    'Фамилия',
                    'Имя',
                    'Отчество',
                    'E-mail',
                    'Телефон',
                    'Дата регистрации',
                    'Дата первой оплаты',
                    'Дата второй оплаты',
                ], ';');
                $rows = $qb->getQuery()->iterate();
                foreach ($rows as $row) {
                    /** @var $user \My\AppBundle\Entity\User */
                    $user = $row[0];
                    fputcsv($handle, [
                        $user->getLastName(),
                        $user->getFirstName(),
                        $user->getPatronymic(),
                        $user->getEmail(),
                        $user->getPhoneMobileStatus() == 'confirmed' ? ('8'.$user->getPhoneMobile()) : '-',
                        date_format($user->getCreatedAt(), 'Y-m-d'),
                        $user->getPayment1Paid() ? date_format($user->getPayment1Paid(), 'Y-m-d') : '-',
                        $user->getPayment2Paid() ? date_format($user->getPayment2Paid(), 'Y-m-d') : '-',
                    ], ';');
                    $this->em->detach($user);
                }
                fclose($handle);
            });
            return $response;
        }

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($request->get('page'));

        foreach ($pagerfanta->getIterator() as $user) { /** @var $user \My\AppBundle\Entity\User */
            $user->forcePromoInfo($categories, $services);
        }

        return $this->render('AppBundle:Admin:paradox_users.html.twig', array(
            'pagerfanta'      => $pagerfanta,
            'filter_form'     => $filter_form->createView(),
            'default_region'  => $defaultRegion,
        ));
    }

    public function paradoxUserViewAction($id)
    {
        if ((false === $this->get('security.context')->isGranted('ROLE_MOD_PARADOX_USERS'))
            && (false === $this->get('security.context')->isGranted('ROLE_MOD_ADD_USER'))
        ) {
            throw $this->createNotFoundException();
        }

        $userRepo = $this->em->getRepository('AppBundle:User');
        $user = $userRepo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User for id "'.$id.'" not found.');
        }

        if ($user == $this->getUser()) {
            throw $this->createNotFoundException('You can\'t moderate yourself');
        }

        $categories = array();
        $categories_orig = $this->em->getRepository('AppBundle:Category')->createQueryBuilder('c')
            ->getQuery()->execute();
        foreach ($categories_orig as $category) { /** @var $category \My\AppBundle\Entity\Category */
            $categories[$category->getId()] = $category;
        }

        $services = array();
        $services_orig = $this->em->getRepository('AppBundle:Service')->createQueryBuilder('s')
            ->getQuery()->execute();
        foreach ($services_orig as $service) { /** @var $service \My\AppBundle\Entity\Service */
            $services[$service->getId()] = $service;
        }

        $paymentsOweStages = [];
        $payments = array();
        $auto_promo = array();
        $logs = $this->em->getRepository('PaymentBundle:Log')->createQueryBuilder('l')
            ->andWhere('l.user = :user')->setParameter(':user', $user)
            ->andWhere('l.paid = :paid')->setParameter(':paid', true)
            ->leftJoin('l.promoKey', 'pk')
            ->addSelect('pk')
            ->leftJoin('pk.promo', 'p')
            ->addSelect('p')
            ->leftJoin('l.owe_stage', 'os')
            ->addSelect('os')
            ->leftJoin('l.package', 'lp')
            ->addSelect('lp')
            ->leftJoin('lp.condition', 'lpc')
            ->addSelect('lpc')
            ->addOrderBy('l.updated_at', 'ASC')
            ->getQuery()->getArrayResult();

        foreach ($logs as $log) { /** @var $log \My\PaymentBundle\Entity\Log */

            $comment = json_decode($log['comment'], true);
            $log['categories'] = array();
            $log['services'] = array();
            $log['driving_name'] = null;
            $log['driving_primary'] = null;
            $log['moderate_name'] = '';

            $revertsLog = $this->em->getRepository('PaymentBundle:RevertLog')->findOneBy([
                'payment_log' => $log['id'],
                'paid'        => true,
            ]);
            $log['revert'] = $revertsLog;

            if ($log['package']) {
                $log['driving_name'] = $log['package']['condition']['name'];
                $log['driving_primary'] = $log['package']['condition']['is_primary'];
            }

            //Модератор, который добавил пользователя
            $moderatorName = null;
            if (!empty($comment['moderator_id'])) {
                /** @var $moderator \My\AppBundle\Entity\User */
                $moderator = $userRepo->find($comment['moderator_id']);
                if ($moderator) {
                    $moderatorName = $moderator->getFullName();
                }
            }

            if (!empty($comment['categories'])) {
                $categories_ids = explode(',', $comment['categories']);
                foreach ($categories_ids as $category_id) {
                    if (isset($categories[$category_id])) {
                        $log['categories'][$category_id] = $categories[$category_id];
                    }
                }
                if (count($log['categories']) > 0) {
                    if ($moderatorName) {
                        $log['moderator_name'] = $moderatorName;
                    }
                    if (isset($comment['auto_promo'])) {
                        $auto_promo[$log['id']] = $comment['auto_promo'];
                    }
                    $payments[] = $log;
                }
            }

            if (!empty($comment['services'])) {
                $services_ids = explode(',', $comment['services']);
                foreach ($services_ids as $service_id) {
                    if (isset($services[$service_id])) {
                        $log['services'][$service_id] = $services[$service_id];
                        $log['required'] = true;
                    } else {
                        /** @CAUTION наследие %) */
                        $log['services'][$service_id] = array('name' => 'Доступ к теоретическому курсу');
                    }
                }
                if (count($log['services']) > 0) {
                    if ($moderatorName) {
                        $log['moderator_name'] = $moderatorName;
                    }
                    $payments[] = $log;
                }
            }

            if (!empty($comment['owe_stage'])) {
                $paid_owe_stage = [];
                if ($moderatorName) {
                    $log['moderator_name'] = $moderatorName;
                }
                $paid_owe_stage[$log['id']] = 'Должник этап №'.$log['owe_stage']['number_stage'];
                $payments[] = $log;
            }
        }

        $subjects = [];
        $version = $this->em->getRepository('AppBundle:TrainingVersion')->createQueryBuilder('v')
            ->andWhere('v.category = :category')->setParameter(':category', $user->getCategory())
            ->andWhere('v.start_date <= :start_date')
            ->setParameter(':start_date', date_format($user->getCreatedAt(), 'Y-m-d'))
            ->addOrderBy('v.start_date', 'DESC')
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();

        if (!empty($version)) {
            $subjects_repository = $this->em->getRepository('AppBundle:Subject');
            $subjects = $subjects_repository->findAllAsArray($user, $version);
        }

        $final_exams_logs_repository = $this->em->getRepository('AppBundle:FinalExamLog');
        $passed_date = $final_exams_logs_repository->getPassedDate($user);
        $is_passed = (bool)$passed_date;

        $exp_limit = null;
        if ($user->getPayment2Paid()) {
            $exp_limit = clone $user->getPayment2Paid();
            $exp_limit->add(new \DateInterval('P'.$this->settings['access_time_after_2_payment'].'D'));
        }

        /** @var  $examAttemptLogs \My\AppBundle\Entity\ExamAttemptLog */
        $examAttemptLogs = $this->em->getRepository('AppBundle:ExamAttemptLog')->createQueryBuilder('eal')
            ->andWhere('eal.user = :user')->setParameter('user', $user)
            ->leftJoin('eal.final_exam_log', 'fel')->addSelect('fel')
            ->leftJoin('eal.exam_log', 'el')->addSelect('el')
            ->addOrderBy('eal.created_at')
            ->getQuery()->execute();

        $examAttemptsSum = 0;
        foreach ($examAttemptLogs as $attemptLog) { /** @var $attemptLog \My\AppBundle\Entity\ExamAttemptLog */
            $examAttemptsSum += $attemptLog->getAmount();
        }

        $examErrorsCount    = $this->get('app.exam_attempts')->getErrorsCountByUser($user);
        $examAttemptsRemain = $this->settings['attempts_to_reset'] - $examErrorsCount;

        $lastOweStage = $this->em->getRepository('AppBundle:OweStage')
            ->findOneBy(['user' => $user->getId()], ['end' => 'DESC']);
        $oweStages = $this->em->getRepository('AppBundle:OweStage')->createQueryBuilder('os')
            ->andWhere('os.user = :user')->setParameter('user', $user)
            ->addOrderBy('os.number_stage', 'ASC')
            ->getQuery()->getResult();

        return $this->render('AppBundle:Admin:paradox_user_view.html.twig', array(
            'user'                 => $user,
            'payments'             => $payments,
            'subjects'             => $subjects,
            'passed_date'          => $passed_date,
            'is_passed'            => $is_passed,
            'is_expired'           => $exp_limit && $exp_limit < new \DateTime(),
            'auto_promo'           => $auto_promo,
            'exam_attempt_logs'    => $examAttemptLogs,
            'exam_attempts_remain' => $examAttemptsRemain,
            'exam_attempts_sum'    => $examAttemptsSum,
            'last_stage'           => $lastOweStage,
            'paid_owe_stage'       => isset($paid_owe_stage) ? $paid_owe_stage : null,
            'owe_stages'           => $oweStages,
            'offline_owe_sum'      => isset($this->settings['cost_driving_payment_in_office']) ?
                $this->settings['cost_driving_payment_in_office'] : 0,
            'payments_stage'       => $paymentsOweStages,
        ));
    }

    public function paidDrivingAjaxAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $user = $this->em->find('AppBundle:User', $request->get('user_id'));
        if (!$user) {
            return new JsonResponse([
                'result'  => 'false',
                'message' => 'Не найден пользователь',
            ]);
        }

        if ($user->getDrivingPaidAt()) {
            return new JsonResponse([
                'result'  => 'false',
                'message' => 'Пользователь уже оплачивал вождение',
            ]);
        }

        $lastOweStage = $this->em->getRepository('AppBundle:OweStage')
            ->findOneBy(['user' => $user->getId()], ['end' => 'DESC']);
        $officeSumm = $this->em->getRepository('AppBundle:Setting')
            ->findOneBy(['_key' => 'cost_driving_payment_in_office']);
        if ($lastOweStage) {
            $user->setDrivingPaidAt(new \DateTime());

            $log = new PaymentLog();
            $log->setUser($user);
            $log->setSum($officeSumm->getValue());
            $comments = [
                'owe_stage'    => true,
                'moderator_id' => $this->user->getId(),
            ];
            $log->setComment(json_encode($comments));
            $log->setPaid(true);
            $log->setOweStage($lastOweStage);
            $lastOweStage->setLog($log);
            $lastOweStage->setPaid(true);

            $this->em->persist($lastOweStage);
            $this->em->persist($user);
            $this->em->persist($log);
            $this->em->flush();

            $paymentsStage = [];
            $paymentsStage[$lastOweStage->getId()] = ['moderator' => $this->user->getFullName()];

            $html = $this->renderView('AppBundle:Admin:paradox_user_view_owe_table.html.twig', [
                'user'           => $user,
                'payments_stage' => $paymentsStage,
            ]);

            return new JsonResponse([
                'result'  => 'success',
                'message' => 'Оплата произведена',
                'html'    => $html,
            ]);
        }

        return new JsonResponse([
            'result'  => 'false',
            'message' => 'Не удалось найти данные периода оплаты',
        ]);
    }

    public function saveSuccessPaidDrivingAjaxAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $date = new \DateTime($request->get('year').'-'.$request->get('month').'-'.$request->get('day'));
        $user = $this->em->find('AppBundle:User', $request->get('user_id'));

        if ($user) {
            /** @var $user User */
            $user->setDrivingPaidAt($date);

            $this->em->persist($user);
            $this->em->flush();

            return new JsonResponse([
                'result'  => 'success',
                'message' => 'Пользователь оплатил практические занятия',
            ]);
        }

        return new JsonResponse([
            'result'  => 'false',
            'message' => 'Не найден пользователь',
        ]);
    }

    public function owePaidDrivingAjaxAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $settings = $this->em->find('AppBundle:Setting', 'online_driving_payment');
        $onlinePayments = null;
        if ($settings) {
            $onlinePayments = unserialize($settings->getValue());
        }

        if (!$onlinePayments) {
            return new JsonResponse([
                'result'  => 'fail',
                'message' => 'Не найдены параметры для следующего напоминания',
            ]);
        }

        $user = $this->em->find('AppBundle:User', $request->get('user_id'));
        if (!$user) {
            return new JsonResponse([
                'result'  => 'false',
                'message' => 'Не найден пользователь',
            ]);
        }

        $numberOweStage = count($user->getOweStages());
        $paramOweStage = isset($onlinePayments[$numberOweStage]) ? $onlinePayments[$numberOweStage] : false;
        if (!$paramOweStage) {
            return new JsonResponse([
                'result'  => 'fail',
                'message' => 'Не найдены параметры для следующего напоминания',
            ]);
        }

        $date = new \DateTime();
        $endDate = $user->getOweStageEnd();
        if (!is_null($endDate) && ($endDate > $date)) {
            return new JsonResponse([
                'result'  => 'fail',
                'message' => 'Ошибка выполнения, текущий срок заканчивается '.$endDate->format('Y-m-d'),
            ]);
        }

        $interval     = $paramOweStage['timer'] ? new \DateInterval('P'.$paramOweStage['timer'].'D') : null;
        $dateEndStage = $interval ? date_create()->add($interval) : date_create();

        $stage = new OweStage();
        $stage->setSum($paramOweStage['cost']);
        $stage->setNumberStage(($numberOweStage + 1));
        $stage->setStart($date);
        $stage->setEnd($dateEndStage);
        $stage->setUser($user);

        $user->setOweStageEnd($dateEndStage);
        $user->addOweStage($stage);
        $user->setPaidPrimaryBoostingNotify(false);

        $this->em->persist($stage);
        $this->em->persist($user);
        $this->em->flush();

        $html = $this->renderView('AppBundle:Admin:paradox_user_view_owe_table.html.twig', [
            'user'           => $user,
            'payments_stage' => [],
        ]);

        $notify = $this->get('app.notify');
        $notify->sendPrimaryBoostingNotPaidDriving($user);

        return new JsonResponse([
            'result'       => 'success',
            'seconds_left' => Time::getAllSeconds($interval),
            'message'      => 'Пользователь не оплатил практические занятия',
            'number'       => $stage->getNumberStage(),
            'html'         => $html,
        ]);
    }

    public function paradoxUserSetAction(Request $request, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_PARADOX_USERS')) {
            throw $this->createNotFoundException();
        }

        $user = $this->em->getRepository('AppBundle:User')->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User for id "'.$id.'" not found.');
        }

        if ($user == $this->getUser()) {
            throw $this->createNotFoundException('You can\'t edit yourself');
        }

        if ($user->getParadoxId()) {
            throw $this->createNotFoundException('This user has already moved to the paradox.');
        }

        $form_factory = $this->container->get('form.factory');
        $form = $form_factory->createNamedBuilder('user', 'form', $user, [
            'constraints'       => new UniqueEntity([
                'message' => 'Такой ID уже существует. Введите другой номер ID.',
                'fields'  => ['paradox_id'],
                'groups'  => ['paradox'],
            ]),
            'validation_groups' => ['paradox'],
        ])
            ->add('paradox_id', null, [
                'required'          => true,
                'constraints'       => [
                    new Assert\NotBlank(['groups' => 'paradox']),
                    new Assert\Length(['max' => 6, 'min' => 6, 'groups' => 'paradox']),
                ],
                'validation_groups' => 'paradox',
            ])
            ->add('webgroup', null, [
                'empty_value'       => 'choose_option',
                'required'          => true,
                'constraints'       => [new Assert\NotBlank(['groups' => 'paradox'])],
                'validation_groups' => 'paradox',
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isValid()) {
            $this->em->persist($user);
            $this->em->flush();

            return $this->redirect($this->generateUrl('admin_paradox_users'));
        }

        return $this->render('AppBundle:Admin:paradox_user_set.html.twig', array(
            'form' => $form->createView(),
            'user' => $user,
        ));
    }

    public function paradoxToPrecheckAction($id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_PARADOX_USERS')) {
            throw $this->createNotFoundException();
        }

        $user = $this->em->getRepository('AppBundle:User')->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User for id "'.$id.'" not found.');
        }

        if ($user == $this->getUser()) {
            throw $this->createNotFoundException('You can\'t edit yourself');
        }

        if ($user->getParadoxId()) {
            throw $this->createNotFoundException('This user has already moved to the paradox.');
        }

        $user->setModerated(false);
        $this->em->persist($user);
        $this->em->flush();

        return $this->redirect($this->generateUrl('admin_paradox_users'));
    }

    public function paradoxUserLockAction($state, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_PARADOX_USERS')) {
            throw $this->createNotFoundException();
        }

        $notify = $this->get('app.notify');

        $user = $this->em->getRepository('AppBundle:User')->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User for id "'.$id.'" not found.');
        }

        if ($user == $this->getUser()) {
            throw $this->createNotFoundException('You can\'t lock and unlock yourself');
        }

        if ($state) {
            $user->setLocked(true);
            if ($this->settingsNotifies['lock_user_enabled']) {
                $subject = $this->settingsNotifies['lock_user_title'];
                $text = $this->settingsNotifies['lock_user_text'];
                $notify->sendEmail($user, $subject, $text, 'text/html');
            }
        } else {
            $user->setLocked(false);
            if ($this->settingsNotifies['unlock_user_enabled']) {
                $subject = $this->settingsNotifies['unlock_user_title'];
                $text = $this->settingsNotifies['unlock_user_text'];
                $notify->sendEmail($user, $subject, $text, 'text/html');
            }
        }

        $this->em->persist($user);
        $this->em->flush();

        return $this->redirect($this->generateUrl('admin_paradox_user_view', array('id' => $id)));
    }

    public function paradoxUserProlongAction($id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_PARADOX_USERS')) {
            throw $this->createNotFoundException();
        }

        $user = $this->em->getRepository('AppBundle:User')->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User for id "'.$id.'" not found.');
        }

        if ($user == $this->getUser()) {
            throw $this->createNotFoundException('You can\'t prolong yourself');
        }

        $exp_limit = null;
        if ($user->getPayment2Paid()) {
            $exp_limit = clone $user->getPayment2Paid();
            $exp_limit->add(new \DateInterval('P'.$this->settings['access_time_after_2_payment'].'D'));
        }
        if (!$exp_limit || $exp_limit >= new \DateTime()) {
            throw $this->createNotFoundException('Can\'t prolong');
        }

        $date = clone $user->getPayment2Paid();
        $date->add(new \DateInterval('P1Y'));
        $user->setPayment2Paid($date);
        $user->setExpired(false);

        $this->em->persist($user);
        $this->em->flush();

        return $this->redirect($this->generateUrl('admin_paradox_user_view', array('id' => $id)));
    }

    public function paradoxUserUnsubscribeXAction($id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_PARADOX_USERS')) {
            throw $this->createNotFoundException();
        }

        $user = $this->em->getRepository('AppBundle:User')->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User for id "'.$id.'" not found.');
        }

        $user->setUnsubscribedX(true);

        $this->em->persist($user);
        $this->em->flush();

        return $this->render('AppBundle:Admin:unsubscribe.html.twig', array(
            'userName'     => $user->getLastName() . ' ' . $user->getFirstName(),
        ));
    }

    public function sendEmailToAction(Request $request, $type, $id)
    {
        $user = $this->em->getRepository('AppBundle:User')->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User for id "'.$id.'" not found.');
        }

        $emails = $this->em->getRepository('AppBundle:FeedbackEmail')->findAll();
        $subjects = array();
        $search_emails = array();
        $search_subjects = array();
        foreach ($emails as $email) { /** @var $email \My\AppBundle\Entity\FeedbackEmail */
            $sbjs = $email->getSubjects();
            if ($sbjs) {
                $sbjs = explode(PHP_EOL, $sbjs);
                $sbjs_cnt = count($sbjs);
                if ($sbjs_cnt > 0) {
                    $subjects[$email->getName()] = array();
                    $id = $email->getId();
                    $search_emails[$id] = $email;
                    for ($i = 0; $i < $sbjs_cnt; $i++) {
                        $subjects[$email->getName()][$id.'_'.$i] = $sbjs[$i];
                        $search_subjects[$id.'_'.$i] = $sbjs[$i];
                    }
                }
            }
        }

        $form_factory = $this->container->get('form.factory');
        $form = $form_factory->createNamedBuilder('send_email')
            ->add('subject', 'choice', array(
                'choices'     => $subjects,
                'empty_value' => '-----',
                'constraints' => array(new Assert\NotBlank()),
            ))
            ->add('message', 'textarea', array(
                'attr'        => array('class' => 'ckeditor'),
                'constraints' => array(new Assert\NotBlank()),
            ))
            ->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $s_id = $form->get('subject')->getData();
            list($e_id) = explode('_', $s_id);
            $email = $search_emails[$e_id];

            if ($email->getEmail() == 'support@auto-online.ru') {
                $mailer = $this->get('swiftmailer.mailer.mailer_sup');
            } else {
                $mailer = $this->container->get('swiftmailer.mailer.mail_ru');
                /** @var $transport \Swift_Transport_EsmtpTransport */
                $transport = $mailer->getTransport();
                /** @var $handler \Swift_Transport_Esmtp_AuthHandler */
                $handler = $transport->getExtensionHandlers()[0];
                $handler->setUsername($email->getEmail());
                $handler->setPassword($email->getPassword());
            }

            $message = $form->get('message')->getData();
            $message = $this->get('templating')->render('AppBundle::_email.html.twig', array(
                'message' => $message,
                'title'   => $email->getName().' : '.$search_subjects[$s_id],
            ));

            /** @var $message \Swift_Mime_Message */
            $message = \Swift_Message::newInstance()
                ->setFrom(array($email->getEmail() => $this->container->getParameter('sender_name')))
                ->setTo($user->getEmail())
                ->setSubject($email->getName().' : '.$search_subjects[$s_id])
                ->setBody($message, 'text/html')
            ;
            $mailer->send($message);

            $this->get('session')->getFlashBag()->add('success', 'Письмо успешно отправленно пользователю!');

            return $this->redirect($this->generateUrl('admin_'.$type.'_user_view', array(
                'id' => $user->getId(),
            )));
        }

        return $this->render('AppBundle:Admin:send_email_to.html.twig', array(
            'form' => $form->createView(),
            'user' => $user,
        ));
    }

    public function payOfflineAction(Request $request, $type, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_PARADOX_USERS')) {
            throw $this->createNotFoundException();
        }

        $user = $this->em->find('AppBundle:User', $id);
        if (!$user || $user == $this->getUser() || !$user->isEnabled() || $user->hasRole('ROLE_USER_PAID2')) {
            throw $this->createNotFoundException();
        }

        $user_view_url = $this->generateUrl('admin_'.$type.'_user_view', array('id' => $id));

        $log = null;

        if ($user->getParadoxId()) {
            $region = $user->getRegion();
            $category = $user->getCategory();

            // Получение списка оплат
            $logs = $this->em->getRepository('PaymentBundle:Log')->createQueryBuilder('l')
                ->andWhere('l.user = :user')->setParameter(':user', $user)
                ->andWhere('l.paid = :paid')->setParameter(':paid', true)
                ->leftJoin('l.revert_logs', 'rl', 'WITH', 'rl.paid = 1')
                ->andWhere('rl.payment_log IS NULL')
                ->getQuery()->getArrayResult();

            $firstIsPaid = false;
            $secondIsPaid = false;

            // Получение списка услуг для второй оплаты
            $all_services = array();
            $services = $this->em->getRepository('AppBundle:Service')->createQueryBuilder('s')
                ->andWhere('s.type = :type')->setParameter(':type', 'training')
                ->getQuery()->getArrayResult();
            foreach ($services as $service) {
                $all_services[] = $service['id'];
            }

            // Проверка оплат
            foreach ($logs as $log) {
                $comment = json_decode($log['comment'], true);
                if (!empty($comment['services'])) {
                    $ids = explode(',', $comment['services']);
                    foreach ($ids as $id) {
                        if (in_array($id, $all_services)) {
                            unset($all_services[array_search($id, $all_services)]);
                        }
                    }
                }

                if (!empty($comment['categories'])) {
                    $ids = explode(',', $comment['categories']);
                    foreach ($ids as $id) {
                        if ($id == $category->getId()) {
                            $firstIsPaid = true;
                        }
                    }
                }
            }

            if (empty($all_services)) {
                $secondIsPaid = true;
            }

            $log = null;

            // Первая оплата
            if (!$firstIsPaid) {
                $sum = 0;
                $price = $this->em->getRepository('AppBundle:CategoryPrice')->createQueryBuilder('cp')
                    ->andWhere('cp.region = :region')->setParameter(':region', $region)
                    ->andWhere('cp.category = :category')->setParameter(':category', $category)
                    ->setMaxResults(1)
                    ->getQuery()->getOneOrNullResult();
                if ($price) {
                    $sum = $price->getPrice();
                }

                $log = new PaymentLog();
                $log->setUser($user);
                $log->setSum($sum);
                $comments = array('categories' => $category->getId());
                $comments['moderator_id'] = $this->user->getId();
                $log->setComment(json_encode($comments));
                $log->setPaid(true);
                $this->em->persist($log);

                $user->addRole('ROLE_USER_PAID');
                $user->setPayment1Paid(new \DateTime());
                $this->get('app.notify')->sendAfterFirstPayment($user);

                $firstIsPaid = true;
            }

            // Вторая оплата
            if (!$secondIsPaid) {
                $sum = 0;
                $prices = $this->em->getRepository('AppBundle:ServicePrice')->createQueryBuilder('sp')
                    ->andWhere('sp.region = :region')->setParameter(':region', $region)
                    ->leftJoin('sp.service', 's')
                    ->andWhere('s.id IN (:ids)')->setParameter(':ids', $all_services)
                    ->getQuery()->getResult();
                foreach ($prices as $price) { /** @var $price \My\AppBundle\Entity\ServicePrice */
                    $sum += $price->getPrice();
                }

                $log = new PaymentLog();
                $log->setUser($user);
                $log->setSum($sum);
                $comments = array('services' => implode(',', $all_services));
                $comments['moderator_id'] = $this->user->getId();
                $log->setComment(json_encode($comments));
                $log->setPaid(true);
                $this->em->persist($log);

                $user->addRole('ROLE_USER_PAID2');
                $user->setPayment2Paid(new \DateTime());
                $this->get('app.notify')->sendAfterSecondPayment($user);

                $secondIsPaid = true;
            }

            $user->setOffline(true);
            $this->em->persist($user);

            /** @var $userStat UserStat */
            $userStat = $user->getUserStat();
            if ($userStat) {
                if ($firstIsPaid) {
                    $userStat->setPay1Type($userStat::PAY_1_TYPE_OFFLINE);
                }

                if ($secondIsPaid) {
                    $userStat->setPay2Type($userStat::PAY_2_TYPE_OFFLINE);
                }

                $this->em->persist($userStat);
            }

            if ($secondIsPaid && $user->getByApi() && $log
                && in_array($this->container->getParameter('server_type'), ['prod', 'qa'])
            ) {
                $this->get('app.second_payment_post')->sendPayment($user->getId(), $log->getId());
            }

            $this->em->flush();

            return $this->redirect($user_view_url);
        } else {
            $fb = $this->createFormBuilder($user, array(
                'constraints'       => new UniqueEntity([
                    'message' => 'Такой ID уже существует. Введите другой номер ID.',
                    'fields'  => ['paradox_id'],
                    'groups'  => array('paradox'),
                ]),
                'validation_groups' => array('paradox'),
            ));
            $fb->add('paradox_id', 'text', array(
                'label'       => 'Paradox ID',
                'constraints' => [
                    new Assert\NotBlank(['groups' => 'paradox']),
                    new Assert\Length(['min' => 6, 'max' => 6, 'groups' => 'paradox']),
                ],
            ));
            $form = $fb->getForm();

            $form->handleRequest($request);
            if ($form->isValid()) {
                $user->setParadoxId($form->get('paradox_id')->getData());
                $this->em->persist($user);
                $this->em->flush();

                return $this->redirect($this->generateUrl('admin_pay_offline', array(
                    'type' => $type,
                    'id'   => $id,
                )));
            }

            return $this->render('AppBundle:Admin:set_paradox_id.html.twig', array(
                'form'     => $form->createView(),
                'back_url' => $user_view_url,
            ));
        }
    }

    public function addUserAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_ADD_USER')
            and false === $this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')) {
            throw $this->createNotFoundException();
        }

        $userManager = $this->get('fos_user.user_manager');

        $regionTree = [];
        $regionTreeSource = $this->em->getRepository('AppBundle:Region')->createQueryBuilder('r')
            ->leftJoin('r.places', 'rp')
            ->leftJoin('r.categories_prices', 'cp', 'WITH', 'cp.active = 1')
            ->addSelect('cp')
            ->getQuery()->getResult();

        /** @var $region \My\AppBundle\Entity\Region*/
        foreach ($regionTreeSource as $region) {
            $regionId = $region->getId();
            if (!isset($regionTree[$regionId])) {
                $regionTree[$regionId] = ['name' => $region->getName(), 'cats' => []];
            }

            /** @var $categoryPrice \My\AppBundle\Entity\CategoryPrice */
            foreach ($region->getCategoriesPrices() as $categoryPrice) {
                $cat = $categoryPrice->getCategory();
                $catId         = $cat->getId();
                $regionTree[$regionId]['cats'][$cat->getId()] = ['name' => $cat->getName(), 'places' => []];
                /** @var  $regionPlace \My\AppBundle\Entity\RegionPlace */
                foreach ($region->getPlaces() as $regionPlace) {
                    $regionPlaceId = $regionPlace->getId();
                    if ($cat->getRegionPlaces()->contains($regionPlace)) {
                        $regionTree[$regionId]['cats'][$catId]['places'][$regionPlaceId] = $regionPlace->getName();
                    }
                }
            }
        }

        $paids = array(
            'nopaid' => 'paids.nopaid',
            'paid_1' => 'paids.paid_1',
            'paid_2' => 'paids.paid_2',
        );

        /** @var $user \My\AppBundle\Entity\User */
        $user = $userManager->createUser();

        if ($this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')) {
            $region = $this->em->getRepository('AppBundle:Region')->findOneBy(['name' => 'Рыбинск']);
            $user->setRegion($region);
            $this->em->persist($user);

            $paids = array(
                'paid_1' => 'paids.paid_1',
            );
        }

        /** @var $form \Symfony\Component\Form\Form */
        $form = $this->createForm(new ProfileFormType(), $user, [
            'constraints'       => new UniqueEntity([
                'message' => 'Такой ID уже существует. Введите другой номер ID.',
                'fields'  => ['paradox_id'],
                'groups'  => ['paradox'],
            ]),
            'validation_groups' => [
                'profile',
                'Registration',
                'paradox',
            ],
        ])
            ->add('plain_password', 'text', [
                'data' => $this->generatePassword(),
                'constraints' => [
                    new Assert\NotBlank(['groups' => 'profile']),
                    new Assert\Length(['min' => 6, 'groups' => 'profile']),
                ],
            ])
            ->add('email', 'email')
            ->add('moderated', 'checkbox', ['required' => false])
            ->add('paids', 'choice', [
                'mapped'      => false,
                'required'    => false,
                'empty_value' => 'choose_option',
                'choices'     => $paids,
            ])
            ->add('category', 'entity', [
                'class'       => 'AppBundle:Category',
                'required'    => true,
                'empty_value' => 'choose_option',
                'constraints' => new Assert\NotBlank(['groups' => 'profile']),
            ])
            ->add('region', 'entity', [
                'class'         => 'AppBundle:Region',
                'required'      => true,
                'empty_value'   => 'choose_option',
                'constraints' => new Assert\NotBlank(['groups' => 'profile']),
                'query_builder' => function (EntityRepository $er) use ($user) {
                    if ($this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')) {
                        return $er->createQueryBuilder('r')
                            ->andWhere('r.id = :id')->setParameter('id', $user->getRegion());
                    } else {
                        return $er->createQueryBuilder('r');
                    }
                },
            ])
            ->add('region_place', 'entity', [
                'empty_value' => 'choose_option',
                'class'       => 'AppBundle:RegionPlace',
                'required'    => false,
            ])
            ->add('webgroup', 'entity', [
                'empty_value' => 'choose_option',
                'class'       => 'AppBundle:Webgroup',
                'required'    => false,
            ])
        ;

        if (false === $this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')) {
            $form->add('paradox_id', 'integer', array(
                'required'      => false,
                'constraints'   => [
                    new Assert\NotBlank(['groups' => 'paradox']),
                    new Assert\Length(['max' => 6, 'min' => 6, 'groups' => 'paradox']),
                ],
            ));
        }

        if ($request->isMethod('post')) {
            $form->handleRequest($request);

            $validator = $this->get('validator');

            $not_registration = $form->get('not_registration')->getData();
            if ($not_registration) {
                $names = array(
                    'place_country',
                    'place_region',
                    'place_city',
                    'place_street',
                    'place_house',
                    'place_apartament',
                );
            } else {
                $names = array(
                    'registration_country',
                    'registration_region',
                    'registration_city',
                    'registration_street',
                    'registration_house',
                    'registration_apartament',
                );
            }

            foreach ($names as $name) {
                $field = $form->get($name);
                $errors = $validator->validateValue($field->getData(), new Assert\NotBlank());
                if (count($errors) > 0) {
                    $field->addError(new FormError($errors->get(0)->getMessage()));
                }
            }

            /** @var $region \My\AppBundle\Entity\Region */
            $region = $form->get('region')->getData();

            $categoryField = $form->get('category');
            /** @var $category \My\AppBundle\Entity\Category */
            $category = $categoryField->getData();

            if ($region && $category) {
                if (!isset($regionTree[$region->getId()]['cats'][$category->getId()])) {
                    $categoryField->addError(new FormError('Неверная категория.'));
                } else {
                    $regionPlaceField = $form->get('region_place');
                    /** @var  $regionPlace \My\AppBundle\Entity\RegionPlace */
                    $regionPlace = $regionPlaceField->getData();
                    $regionId    = $region->getId();

                    if ($regionPlace &&
                        !isset($regionTree[$regionId]['cats'][$category->getId()]['places'][$regionPlace->getId()])) {
                        $regionPlaceField->addError(new FormError('Неверное место вождения.'));
                    }
                }
            }

            if ($form->isValid()) {
                $plainPassword = $form->get('plain_password')->getData();
                $user->addRole('ROLE_USER_FULL_PROFILE');
                $user->setConfirmationToken(null);
                $user->setEnabled($user->getModerated());
                $user->setOffline(true);
                if (!$user->getModerated()) {
                    $user->setPhoneMobileStatus('sended');
                } else {
                    $user->setPhoneMobileStatus('confirmed');
                }

                //statuses, paids, roles etc
                $paid = $form->get('paids')->getData();

                $moderatorId = ($this->getUser() instanceof User) ? $this->getUser()->getId() : null;

                $log = null;

                //set other fields
                if ($paid == 'paid_1' || $paid == 'paid_2') {
                    $user->addRole('ROLE_USER_PAID');

                    $user->setPayment1Paid(new \DateTime());
                    $user->setPayment1PaidNotNotify(false);

                    //save first payment
                    $categories_prices = $this->em->getRepository('AppBundle:CategoryPrice')->createQueryBuilder('cp')
                        ->andWhere('cp.active = :active')->setParameter(':active', true)
                        ->andWhere('cp.region = :region')->setParameter(':region', $form->get('region')->getData())
                        ->andWhere('cp.category = :category')
                        ->setParameter(':category', $form->get('category')->getData())
                        ->getQuery()->execute();
                    $categories_prices_sum = 0;
                    foreach ($categories_prices as $price) {
                        /** @var $price \My\AppBundle\Entity\CategoryPrice */

                        $categories_prices_sum += $price->getPrice();
                    }

                    $log = new PaymentLog();
                    $log->setUser($user);
                    $log->setSum($categories_prices_sum);
                    $log->setPaid(true);
                    $log->setComment(json_encode(array(
                        'categories'   => (string)$form->get('category')->getData()->getId(),
                        'moderator_id' => $moderatorId,
                    )));
                    $this->em->persist($log);
                }

                if ($paid == 'paid_2') {
                    $user->addRole('ROLE_USER_PAID2');

                    $user->setPayment2Paid(new \DateTime());
                    $user->setPayment2PaidNotNotify(false);

                    //save second payment
                    $services = $this->em->getRepository('AppBundle:Service')->createQueryBuilder('s')
                        ->addSelect('rp.price')
                        ->leftJoin('s.regions_prices', 'rp')
                        ->andWhere('rp.active = :active')->setParameter(':active', true)
                        ->andWhere('s.type   != :type')->setParameter(':type', 'site_access')
                        ->andWhere('s.type   IS NOT NULL')
                        ->andWhere('rp.region = :region')->setParameter(':region', $form->get('region')->getData())
                        ->getQuery()->execute();
                    $sids = array();
                    $services_prices_sum = 0;
                    foreach ($services as $service) {
                        $services_prices_sum += $service['price'];
                        /** @var $s \My\AppBundle\Entity\Service */
                        $s = $service[0];
                        $sids[] = $s->getId();
                    }

                    $log = new PaymentLog();
                    $log->setUser($user);
                    $log->setSum($services_prices_sum);
                    $log->setPaid(true);
                    $log->setComment(json_encode(array(
                        'services'     => implode(',', $sids),
                        'moderator_id' => $moderatorId,
                    )));
                    $this->em->persist($log);
                }

                $regType  = 'unpaid';
                $pay1Type = null;
                $pay2Type = null;

                if ($paid == 'paid_1' || $paid == 'paid_2') {
                    $regType  = $paid;
                    $pay1Type = 'offline';
                }

                if ($paid == 'paid_2') {
                    $pay2Type = 'offline';
                }

                $userStat = new UserStat();
                $userStat->setUser($user);
                $userStat->setRegBy($userStat::REG_BY_OFFLINE);
                $userStat->setRegType($regType);
                $userStat->setPay1Type($pay1Type);
                $userStat->setPay2Type($pay2Type);
                $this->em->persist($userStat);

                $userManager->updateUser($user);
                $this->em->flush(); //save logs etc

                if (!$user->getModerated()) {
                    $this->container->get('app.user_helper')->sendMessages($user, $plainPassword, true);
                }

                if ($paid == 'paid_1') {
                    $this->get('app.notify')->sendAfterFirstPayment($user);
                }

                $this->get('session')->getFlashBag()->add('success', 'success_added');

                if ($this->get('security.context')->isGranted('ROLE_MOD_PARADOX_USERS')) {
                    $url = $this->generateUrl('admin_paradox_user_view', array('id' => $user->getId()));
                } else {
                    $url = $this->generateUrl('admin_add_user');
                }
                return $this->redirect($url);
            }
        }

        return $this->render('AppBundle:Admin:add_user_action.html.twig', array(
            'form'        => $form->createView(),
            'region_tree' => $regionTree,
        ));
    }

    public function addSimpleUserAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_ADD_USER')
            and false === $this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')) {
            throw $this->createNotFoundException();
        }

        $userManager = $this->get('fos_user.user_manager');

        $regionTree = [];
        $regionTreeSource = $this->em->getRepository('AppBundle:Region')->createQueryBuilder('r')
            ->leftJoin('r.places', 'rp')
            ->leftJoin('r.categories_prices', 'cp', 'WITH', 'cp.active = 1')
            ->addSelect('cp')
            ->getQuery()->getResult();

        /** @var $region \My\AppBundle\Entity\Region*/
        foreach ($regionTreeSource as $region) {
            $regionId = $region->getId();
            if (!isset($regionTree[$regionId])) {
                $regionTree[$regionId] = ['name' => $region->getName(), 'cats' => []];
            }

            /** @var $categoryPrice \My\AppBundle\Entity\CategoryPrice */
            foreach ($region->getCategoriesPrices() as $categoryPrice) {
                $cat = $categoryPrice->getCategory();
                $regionTree[$regionId]['cats'][$cat->getId()] = ['name' => $cat->getName()];
            }
        }

        /** @var $user \My\AppBundle\Entity\User */
        $user = $userManager->createUser();
        $formType = new SimpleProfileFormType();
        $formType->setPassword($this->generatePassword());
        $form = $this->createForm($formType, $user);

        if ($this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')) {
            $region = $this->em->getRepository('AppBundle:Region')->findOneBy(['name' => 'Рыбинск']);
            $user->setRegion($region);

            $paids = array(
                'paid_1' => 'paids.paid_1',
            );

            $form->add('paids', 'choice', array(
                'mapped'      => false,
                'required'    => false,
                'empty_value' => 'choose_option',
                'choices'     => $paids,
            ));

            $form->add('region', 'entity', array(
                'class'       => 'AppBundle:Region',
                'required'    => true,
                'empty_value' => 'choose_option',
                'constraints' => new Assert\NotBlank(['groups' => 'simple_profile']),
                'query_builder' => function (EntityRepository $er) use ($user) {
                    if ($this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')) {
                        return $er->createQueryBuilder('r')
                            ->andWhere('r.id = :id')->setParameter('id', $user->getRegion());
                    } else {
                        return $er->createQueryBuilder('r');
                    }
                },
            ));
        }

        if ($request->isMethod('post')) {
            $form->handleRequest($request);

            $region = $form->get('region')->getData();

            $categoryField = $form->get('category');
            /** @var $category \My\AppBundle\Entity\Category */
            $category = $categoryField->getData();

            if ($region && $category) {
                if (!isset($regionTree[$region->getId()]['cats'][$category->getId()])) {
                    $categoryField->addError(new FormError('Неверная категория.'));
                }
            }

            if ($form->isValid()) {
                $plainPassword = $form->get('plain_password')->getData();
                $user->setConfirmationToken(null);
                $user->setEnabled(false);
                $user->setOffline(true);
                $user->setPhoneMobileStatus('sended');

                //statuses, paids, roles etc
                $paid = $form->get('paids')->getData();

                $moderatorId = ($this->getUser() instanceof User) ? $this->getUser()->getId() : null;

                $log = null;

                //set other fields
                if ($paid == 'paid_1' || $paid == 'paid_2') {
                    $user->addRole('ROLE_USER_PAID');

                    $user->setPayment1Paid(new \DateTime());
                    $user->setPayment1PaidNotNotify(false);

                    //save first payment
                    $categories_prices = $this->em->getRepository('AppBundle:CategoryPrice')->createQueryBuilder('cp')
                        ->andWhere('cp.active = :active')->setParameter(':active', true)
                        ->andWhere('cp.region = :region')->setParameter(':region', $form->get('region')->getData())
                        ->andWhere('cp.category = :category')
                        ->setParameter(':category', $form->get('category')->getData())
                        ->getQuery()->execute();

                    $categories_prices_sum = 0;
                    foreach ($categories_prices as $price) {
                        /** @var $price \My\AppBundle\Entity\CategoryPrice */
                        $categories_prices_sum += $price->getPrice();
                    }

                    $log = new PaymentLog();
                    $log->setUser($user);
                    $log->setSum($categories_prices_sum);
                    $log->setPaid(true);
                    $log->setComment(json_encode([
                        'categories'   => (string)$form->get('category')->getData()->getId(),
                        'moderator_id' => $moderatorId,
                    ]));
                    $this->em->persist($log);
                }

                if ($paid == 'paid_2') {
                    $user->addRole('ROLE_USER_PAID2');

                    $user->setPayment2Paid(new \DateTime());
                    $user->setPayment2PaidNotNotify(false);

                    //save second payment
                    $services            = $this->em->getRepository('AppBundle:Service')->createQueryBuilder('s')
                        ->addSelect('rp.price')
                        ->leftJoin('s.regions_prices', 'rp')
                        ->andWhere('rp.active = :active')->setParameter(':active', true)
                        ->andWhere('s.type != :type')->setParameter(':type', 'site_access')
                        ->andWhere('s.type IS NOT NULL')
                        ->andWhere('rp.region = :region')->setParameter(':region', $form->get('region')->getData())
                        ->getQuery()->execute();
                    $sids                = [];
                    $services_prices_sum = 0;
                    foreach ($services as $service) {
                        $services_prices_sum += $service['price'];
                        /** @var $s \My\AppBundle\Entity\Service */
                        $s      = $service[0];
                        $sids[] = $s->getId();
                    }

                    $log = new PaymentLog();
                    $log->setUser($user);
                    $log->setSum($services_prices_sum);
                    $log->setPaid(true);
                    $log->setComment(json_encode([
                        'services'     => implode(',', $sids),
                        'moderator_id' => $moderatorId,
                    ]));
                    $this->em->persist($log);
                }

                $regType  = 'unpaid';
                $pay1Type = null;
                $pay2Type = null;

                if ($paid == 'paid_1' || $paid == 'paid_2') {
                    $regType  = $paid;
                    $pay1Type = 'offline';
                }

                if ($paid == 'paid_2') {
                    $pay2Type = 'offline';
                }

                $userStat = new UserStat();
                $userStat->setUser($user);
                $userStat->setRegBy($userStat::REG_BY_OFFLINE_SIMPLE);
                $userStat->setRegType($regType);
                $userStat->setPay1Type($pay1Type);
                $userStat->setPay2Type($pay2Type);
                $this->em->persist($userStat);

                $userManager->updateUser($user);
                $this->em->flush(); //save logs etc

                if (!$user->getModerated()) {
                    $this->container->get('app.user_helper')->sendMessages($user, $plainPassword, true);
                }

                if ($paid == 'paid_1') {
                    $this->get('app.notify')->sendAfterFirstPayment($user);
                }

                $this->get('session')->getFlashBag()->add('success', 'success_added');

                if ($this->get('security.context')->isGranted('ROLE_MOD_PARADOX_USERS')) {
                    $url = $this->generateUrl('admin_paradox_user_view', ['id' => $user->getId()]);
                } else {
                    $url = $this->generateUrl('admin_simple_add_user');
                }
                return $this->redirect($url);
            }
        }

        return $this->render('AppBundle:Admin:addSimpleUser.html.twig', array(
            'form'        => $form->createView(),
            'region_tree' => $regionTree,
        ));
    }

    //Feedback Emails
    public function feedbackEmailsAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $qb = $this->em->getRepository('AppBundle:FeedbackEmail')->createQueryBuilder('a');

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($request->get('page'));

        return $this->render('AppBundle:Admin:feedbackEmails.html.twig', array(
            'pagerfanta' => $pagerfanta,
        ));
    }

    public function feedbackEmailAddAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(new FeedbackEmailFormType(), new FeedbackEmail());
        $form->handleRequest($request);
        if ($form->isValid()) {
            $feedbackEmail = $form->getData();

            $this->em->persist($feedbackEmail);
            $this->em->flush();

            $this->get('session')->getFlashBag()->add('success', 'success_added');

            return $this->redirect($this->generateUrl('admin_feedbackEmail_add'));
        }

        return $this->render('AppBundle:Admin:feedbackEmail.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function feedbackEmailEditAction(Request $request, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $feedbackEmail = $this->em->getRepository('AppBundle:FeedbackEmail')->find($id);
        if (!$feedbackEmail) {
            throw $this->createNotFoundException('FeedbackEmail for id "'.$id.'" not found.');
        }

        $form = $this->createForm(new FeedbackEmailFormType(), $feedbackEmail);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $this->em->persist($feedbackEmail);
            $this->em->flush();

            return $this->redirect($this->generateUrl('admin_feedbackEmails'));
        }

        return $this->render('AppBundle:Admin:feedbackEmail.html.twig', array(
            'form'          => $form->createView(),
            'feedbackEmail' => $feedbackEmail,
        ));
    }

    public function feedbackEmailDeleteAction($id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $feedbackEmail = $this->em->getRepository('AppBundle:FeedbackEmail')->find($id);
        if (!$feedbackEmail) {
            throw $this->createNotFoundException('FeedbackEmail for id "'.$id.'" not found.');
        }

        $this->em->remove($feedbackEmail);
        $this->em->flush();

        return $this->redirect($this->generateUrl('admin_feedbackEmails'));
    }

    //Feedback Teacher Emails
    public function feedbackTeacherEmailsAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $qb = $this->em->getRepository('AppBundle:FeedbackTeacherEmail')->createQueryBuilder('a');

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($request->get('page'));

        return $this->render('AppBundle:Admin:feedbackTeacherEmails.html.twig', array(
            'pagerfanta' => $pagerfanta,
        ));
    }

    public function feedbackTeacherEmailAddAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(new FeedbackTeacherEmailFormType(), new FeedbackTeacherEmail());
        $form->handleRequest($request);
        if ($form->isValid()) {
            $feedbackTeacherEmail = $form->getData();

            $this->em->persist($feedbackTeacherEmail);
            $this->em->flush();

            $this->get('session')->getFlashBag()->add('success', 'success_added');

            return $this->redirect($this->generateUrl('admin_feedbackTeacherEmail_add'));
        }

        return $this->render('AppBundle:Admin:feedbackTeacherEmail.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function feedbackTeacherEmailEditAction(Request $request, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $feedbackTeacherEmail = $this->em->getRepository('AppBundle:FeedbackTeacherEmail')->find($id);
        if (!$feedbackTeacherEmail) {
            throw $this->createNotFoundException('FeedbackTeacherEmail for id "'.$id.'" not found.');
        }

        $form = $this->createForm(new FeedbackTeacherEmailFormType(), $feedbackTeacherEmail);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $this->em->persist($feedbackTeacherEmail);
            $this->em->flush();

            return $this->redirect($this->generateUrl('admin_feedbackTeacherEmails'));
        }

        return $this->render('AppBundle:Admin:feedbackTeacherEmail.html.twig', array(
            'form'                 => $form->createView(),
            'feedbackTeacherEmail' => $feedbackTeacherEmail,
        ));
    }

    public function feedbackTeacherEmailDeleteAction($id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $feedbackTeacherEmail = $this->em->getRepository('AppBundle:FeedbackTeacherEmail')->find($id);
        if (!$feedbackTeacherEmail) {
            throw $this->createNotFoundException('FeedbackTeacherEmail for id "'.$id.'" not found.');
        }

        $this->em->remove($feedbackTeacherEmail);
        $this->em->flush();

        return $this->redirect($this->generateUrl('admin_feedbackTeacherEmails'));
    }

    private function getYesNoChoiceParams()
    {
        return array(
            'required'    => false,
            'empty_value' => 'choose_option',
            'choices'     => array(
                'yes' => 'yes',
                'no'  => 'no',
            ),
        );
    }

    public function supportCategoriesAction()
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $sc = $this->em->getRepository('AppBundle:SupportCategory')->createQueryBuilder('sc')
            ->leftJoin('sc.children', 'c')->addSelect('c')
            ->addOrderBy('sc.name')
            ->addOrderBy('c.name')
            ->getQuery()->execute();

        $categories = array();
        foreach ($sc as $category) { /** @var $category \My\AppBundle\Entity\SupportCategory */
            if (!isset($categories[$category->getType()])) {
                $categories[$category->getType()] = array();
            }
            if (!$category->getParent()) {
                $categories[$category->getType()][$category->getId()] = $category;
            }
        }

        return $this->render('AppBundle:Admin:support_categories.html.twig', array(
            'categories' => $categories,
        ));
    }

    public function supportCategoryAddAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(new SupportCategoryFormType(), new SupportCategory());
        if ($request->isMethod('post')) {
            $form->submit($request);
            if ($form->isValid()) {
                $category = $form->getData();
                $this->em->persist($category);
                $this->em->flush();

                /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
                $session = $this->get('session');
                $session->getFlashBag()->add('success', 'success_added');

                return $this->redirect($this->generateUrl('admin_support_category_add'));
            }
        }

        return $this->render('AppBundle:Admin:support_category.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function supportCategoryEditAction(Request $request, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        /** @var $theme \My\AppBundle\Entity\SupportCategory */
        $category = $this->em->getRepository('AppBundle:SupportCategory')->find($id);
        if (!$category) {
            throw $this->createNotFoundException('Support category for id "'.$id.'" not found.');
        }

        $form = $this->createForm(new SupportCategoryFormType(), $category);
        if ($request->isMethod('post')) {
            $form->handleRequest($request);


            if ($category == $category->getParent()) {
                $form->get('parent')->addError(new FormError('Категория не может быть сама себе родительской!'));
            }

            if ($form->isValid()) {
                $this->em->persist($category);
                $this->em->flush();

                return $this->redirect($this->generateUrl('admin_support_categories'));
            }
        }

        return $this->render('AppBundle:Admin:support_category.html.twig', array(
            'form'  => $form->createView(),
            'category' => $category,
        ));
    }

    public function supportCategoryDeleteAction($id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        /** @var $theme \My\AppBundle\Entity\SupportCategory */
        $category = $this->em->getRepository('AppBundle:SupportCategory')->find($id);
        if (!$category) {
            throw $this->createNotFoundException('Support category for id "'.$id.'" not found.');
        }

        $this->em->remove($category);
        $this->em->flush();

        return $this->redirect($this->generateUrl('admin_support_categories'));
    }

    public function supportDialogsAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_SUPPORT')
            and false === $this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')) {
            throw $this->createNotFoundException();
        }

        $categoriesTree = array();
        $categories = $this->em->getRepository('AppBundle:SupportCategory')->createQueryBuilder('sc')
            ->orderBy('sc.createdAt')
            ->orderBy('sc.parent')
            ->getQuery()->getResult();
        foreach ($categories as $category) {
            /** @var $category \My\AppBundle\Entity\SupportCategory */

            if ($category->getParent()) {
                //for optgroup
                if (!isset($categoriesTree[$category->getParent()->getName()])) {
                    $categoriesTree[$category->getParent()->getName()] = array();
                }
                $categoriesTree[$category->getParent()->getName()][$category->getId()] = $category->getName();
            }
        }

        /** @var $form_factory \Symfony\Component\Form\FormFactory */
        $form_factory = $this->container->get('form.factory');
        /** @var $fb \Symfony\Component\Form\FormBuilder */
        $fb = $form_factory->createNamedBuilder('support_dialog', 'form', array(), array('csrf_protection' => false))
            ->add('category_name', 'choice', array(
                'required'    => false,
                'empty_value' => 'choose_option',
                'choices'     => $categoriesTree,
            ))
            ->add('user_first_name', 'text', array('required' => false))
            ->add('user_last_name', 'text', array('required' => false))
            ->add('user_patronymic', 'text', array('required' => false))
            ->add('user_email', 'text', array('required' => false))
            ->add('answered', 'choice', $this->getYesNoChoiceParams())
        ;
        $fb->setMethod('get');
        $filter_form = $fb->getForm();
        $filter_form->handleRequest($request);

        $qb = $this->em->getRepository('AppBundle:SupportDialog')->getModeratorAvailableDialogs($this->getUser(), true);
        if ($this->get('security.context')->isGranted('ROLE_MOD_REPRESENTATIVE')) {
            $qb->leftJoin('u.region', 'r')
                ->andWhere('r.name = :region_name')->setParameter('region_name', 'Рыбинск');
        }

        $qb->addOrderBy('sd.last_message_time', 'DESC');

        //filter dialogs
        if ($category = $filter_form->get('category_name')->getData()) {
            $qb->andWhere('sd.category = :category')->setParameter('category', $category);
        }
        if ($ufn = $filter_form->get('user_first_name')->getData()) {
            $qb->andWhere('u.first_name = :ufn')->setParameter('ufn', $ufn);
        }
        if ($uln = $filter_form->get('user_last_name')->getData()) {
            $qb->andWhere('u.last_name = :uln')->setParameter('uln', $uln);
        }
        if ($up = $filter_form->get('user_patronymic')->getData()) {
            $qb->andWhere('u.patronymic = :up')->setParameter('up', $up);
        }
        if ($ue = $filter_form->get('user_email')->getData()) {
            $qb->andWhere('u.email = :ue')->setParameter('ue', $ue);
        }
        if ($answered = $filter_form->get('answered')->getData()) {
            $qb->andWhere('sd.answered = :answered')->setParameter('answered', 'yes' == $answered);
        }

        $adapter = new DoctrineORMAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(20);
        $page = intval($request->get('page'));
        $page = max($page, 1);
        $pagerfanta->setCurrentPage($page);

        return $this->render('AppBundle:Admin:support_dialogs.html.twig', array(
            'pagerfanta'  => $pagerfanta,
            'filter_form' => $filter_form->createView(),
        ));
    }

    public function supportDialogShowAction(Request $request, $id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_SUPPORT')) {
            throw $this->createNotFoundException();
        }

        $dialog = $this->em->getRepository('AppBundle:SupportDialog')->find($id);
        if (!$dialog) {
            throw $this->createNotFoundException('Dialog for id "'.$id.'" not found.');
        }

        $message = new SupportMessage();
        $form = $this->createForm(new SupportMessageFormType(), $message);

        if ($request->isMethod('post')) {
            $form->submit($request);
            if ($form->isValid()) {
                $dialog->setLastMessageText($message->getText());
                $dialog->setLastMessageTime(new \DateTime());
                $dialog->setLastModerator($this->getUser());
                $dialog->setAnswered(true);
                $dialog->setUserRead(false);

                $message->setDialog($this->em->getReference('AppBundle:SupportDialog', $id));
                $message->setUser($this->getUser());

                $this->em->persist($message);
                $this->em->flush();

                //send notifying email
                $this->get('app.notify')->sendSupportAnswered($dialog->getUser());

                return $this->redirect($this->generateUrl('admin_support_dialog_show', array('id' => $id)));
            }
        }

        return $this->render('AppBundle:Admin:support_dialog_show.html.twig', array(
            'form'     => $form->createView(),
            'dialog'   => $dialog,
        ));
    }

    public function supportStatisticsAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $now = new \DateTime('tomorrow midnight');
        $monthAgo = new \DateTime('today midnight');
        $monthAgo = $monthAgo->modify('-1 month');

        /** @var $form_factory \Symfony\Component\Form\FormFactory */
        $form_factory = $this->container->get('form.factory');
        /** @var $fb \Symfony\Component\Form\FormBuilder */
        $fb = $form_factory->createNamedBuilder('statistics', 'form', array(), array('csrf_protection' => false))
            ->add('from', 'date', array('data' => $monthAgo))
            ->add('to', 'date', array('data' => $now))
        ;
        $fb->setMethod('get');
        $filter_form = $fb->getForm();
        $filter_form->handleRequest($request);

        $from = $filter_form->get('from')->getData();
        $to = $filter_form->get('to')->getData();
        $data = array(
            'moderators'    => $this->em->getRepository('AppBundle:User')->getSupportStatistics($from, $to),
            'subCategories' => $this->em->getRepository('AppBundle:SupportCategory')->getSupportStatistics($from, $to),
        );
        return $this->render('AppBundle:Admin:support_statistics.html.twig', array(
            'data'        => $data,
            'filter_form' => $filter_form->createView(),
            'form'        => $from,
            'to'          => $to,
        ));
    }

    public function holidaysAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $startDate = new \DateTime('01.01.'.(date('Y')-1));
        $holidays = $this->em->getRepository('AppBundle:Holiday')->createQueryBuilder('h')
            ->orderBy('h.entry_value')
            ->andWhere('h.entry_value >= :prevYear')->setParameter('prevYear', $startDate)
            ->getQuery()->getResult();

        if ($request->isMethod('post')) {
            $answer = new \stdClass;
            $answer->success = false;
            if ($request->request->get('entryValue') !== null) {
                $date = new \DateTime($request->request->get('entryValue'));
                $holidays = $this->em->getRepository('AppBundle:Holiday')->findBy(array(
                    'entry_value' => $date,
                ));
                if ($holidays) {
                    foreach ($holidays as $holiday) {
                        $this->em->remove($holiday);
                        $this->em->flush();
                    }
                } else {
                    $holiday = new Holiday();
                    $holiday->setEntryValue($date);
                    if (in_array($date->format('N'), array(6, 7))) {
                        $holiday->setException(true);
                    } else {
                        $holiday->setException(false);
                    }
                    $this->em->persist($holiday);
                    $this->em->flush();
                }

                $answer->success = true;
            }
            return new JsonResponse($answer);
        }

        return $this->render('AppBundle:Admin:holidays.html.twig', array(
            'holidays' => $holidays,
        ));
    }

    public function getXmlHolidaysAction(Request $request)
    {
        $files = $request->files->get('files');
        /** @var $file \Symfony\Component\HttpFoundation\File\UploadedFile */
        $file = $files[0];

        $strXml = file_get_contents($file->getRealPath());

        if (!$strXml) {
            return new JsonResponse([
                'message' => 'Не удалось загрузить файл с праздниками.',
                'success' => false]);
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($strXml);
        if (!$xml) {
            return new JsonResponse([
                'message' => 'Файл с праздниками загружен, но не удалось его обработать.',
                'success' => false]);
        }

        $holidays = [];
        $year = $xml->attributes()->year;
        foreach ($xml->days->day as $day) {
            if ($day['r']) {
                continue;
            }
            $txtDate = $year.'-'.str_replace('.', '-', $day['d']);
            if ($day['t'] != 2) {
                $holidays[] = [$txtDate, $day['t'] == 3 ? true : false];
            }
        }

        $this->em->getRepository('AppBundle:Holiday')->createQueryBuilder('h')
            ->delete()
            ->andWhere('h.entry_value IN (:holidays)')->setParameter('holidays', $holidays)
            ->getQuery()->execute();

        foreach ($holidays as $holidayDate) {
            $holiday = new Holiday();
            $holiday->setEntryValue(new \DateTime($holidayDate[0]));
            if ($holidayDate[1]) {
                $holiday->setException(true);
            }
            $this->em->persist($holiday);
        }
        $this->em->flush();

        return new JsonResponse([
            'message' =>  'Импортированно '.count($holidays).' праздничных дней',
            'success' => true,
        ]);
    }

    public function calcPayAmountAction(Request $request)
    {
        $regionId = $request->query->get('region_id');
        $categoryId = $request->query->get('category_id');
        $paids = $request->query->get('paids');
        if (!($categoryId && $regionId)) {
            throw new HttpException(400);
        }

        $region = $this->em->getRepository('AppBundle:Region')->find($regionId);
        if (!$region) {
            throw new HttpException(404, 'Region not found');
        }
        $category = $this->em->getRepository('AppBundle:Category')->find($categoryId);
        if (!$category) {
            throw new HttpException(404, 'Category not found');
        }
        /** @var CategoryPriceRepository $categoryPriceRepo */
        $categoryPriceRepo = $this->em->getRepository('AppBundle:CategoryPrice');

        $categoriesPriceSum = $categoryPriceRepo->getCategoriesPricesSum($region, $category);

        $discount = 0;
        if ($categoriesPriceSum && $region->getDiscount1Amount() > 0) {
            $today = new \DateTime('today');
            $from  = $region->getDiscount1DateFrom();
            $to    = $region->getDiscount1DateTo();
            if (($today <= $to && $today >= $from) || $region->getDiscount1TimerPeriod() > 0) {
                $discount = $region->getDiscount1Amount();
                if ($discount > $categoriesPriceSum) {
                    $discount = $categoriesPriceSum;
                }
            }
        }

        $servicePrices = $this->em->getRepository('AppBundle:ServicePrice')->getPriceByRegion($region);
        $secondPaidPrice = 0;
        foreach ($servicePrices as $servicePrice) {
            /** @var $servicePrice ServicePrice */

            $secondPaidPrice += $servicePrice->getPrice();
        }

        $result = array();
        if ($paids == 'paid_1' || $paids == 'paid_2') {
            $result['categories_price_sum'] = $categoriesPriceSum;
            $result['categories_discount'] = $discount;
        }
        if ($paids == 'paid_2') {
            $result['services_price_sum'] = $secondPaidPrice;
        }
        return new JsonResponse($result);
    }

    private function generatePassword($length = 8)
    {
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= rand(0, 9);
        }
        return $password;
    }

    public function overdueStatisticsAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_FINANCE')) {
            throw $this->createNotFoundException();
        }

        $form_factory = $this->container->get('form.factory');
        /** @var $fb \Symfony\Component\Form\FormBuilder */
        $fb = $form_factory->createNamedBuilder('user', 'form', array(), array('csrf_protection' => false))
            ->add('show_from', 'date', array(
                'years'       => range(2010, date('Y') + 1),
                'required'    => false,
                'empty_value' => '--',
            ))
            ->add('show_to', 'date', array(
                'years'       => range(2010, date('Y') + 1),
                'required'    => false,
                'empty_value' => '--',
            ))
        ;
        $fb->setMethod('get');
        $filter_form = $fb->getForm();
        $filter_form->handleRequest($request);

        $qb = $this->em->getRepository('AppBundle:User')->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->andWhere('u.payment_1_paid IS NULL')
        ;
        if ($data = $filter_form->get('show_from')->getData()) {
            $qb->andWhere('u.created_at >= :show_from')->setParameter(':show_from', $data);
        }
        if ($data = $filter_form->get('show_to')->getData()) {
            $qb->andWhere('u.created_at <= :show_to')->setParameter(':show_to', $data);
        }

        //unpaid
        $unpaid = $qb->getQuery()->getScalarResult();
        $unpaid = $unpaid[0][1];

        //paid by promo code
        $qb = $this->em->getRepository('PaymentBundle:Log')->createQueryBuilder('l')
            ->select('COUNT(u)')
            ->andWhere('l.comment LIKE :comments')->setParameter(':comments', '%categories%')
            ->leftJoin('l.user', 'u')
            ->andWhere('l.user IS NOT NULL')
            ->leftJoin('l.promoKey', 'pk')
            ->andWhere('l.promoKey IS NOT NULL')
            ->andWhere('pk.source = :pk_source')->setParameter(':pk_source', 'auto_overdue')
            ->andWhere('pk.type = :pk_type')->setParameter(':pk_type', 'site_access')
        ;
        if ($data = $filter_form->get('show_from')->getData()) {
            $qb->andWhere('u.created_at >= :show_from')->setParameter(':show_from', $data);
        }
        if ($data = $filter_form->get('show_to')->getData()) {
            $qb->andWhere('u.created_at <= :show_to')->setParameter(':show_to', $data);
        }
        $paidPromo = $qb->getQuery()->getScalarResult();
        $paidPromo = $paidPromo[0][1];

        $qb = $this->em->getRepository('PaymentBundle:Log')->createQueryBuilder('l')
            ->select('SUM(l.sum) summ, pk.overdue_letter_num num')
            ->andWhere('l.comment LIKE :comments')->setParameter(':comments', '%categories%')
            ->leftJoin('l.user', 'u')
            ->andWhere('l.user IS NOT NULL')
            ->leftJoin('l.promoKey', 'pk')
            ->andWhere('l.promoKey IS NOT NULL')
            ->andWhere('pk.source = :pk_source')->setParameter(':pk_source', 'auto_overdue')
            ->andWhere('pk.type = :pk_type')->setParameter(':pk_type', 'site_access')
            ->groupBy('pk.overdue_letter_num')
        ;
        if ($data = $filter_form->get('show_from')->getData()) {
            $qb->andWhere('u.created_at >= :show_from')->setParameter(':show_from', $data);
        }
        if ($data = $filter_form->get('show_to')->getData()) {
            $qb->andWhere('u.created_at <= :show_to')->setParameter(':show_to', $data);
        }
        $dataByDay = $qb->getQuery()->getScalarResult();

        return $this->render('AppBundle:Admin:overdue_statistics.html.twig', array(
            'filter_form' => $filter_form->createView(),
            'unpaid'      => $unpaid,
            'paidPromo'   => $paidPromo,
            'dataByDay'   => $dataByDay,
        ));
    }

    public function regStatAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_REG_STAT')) {
            throw $this->createNotFoundException();
        }

        $form_factory = $this->container->get('form.factory');
        /** @var $fb \Symfony\Component\Form\FormBuilder */
        $fb = $form_factory->createNamedBuilder('user', 'form', array(), array('csrf_protection' => false))
            ->add('show_from', 'date', array(
                'years'       => range(2016, date('Y')),
            ))
            ->add('show_to', 'date', array(
                'years'       => range(2016, date('Y')),
                'required'    => false,
                'empty_value' => '--',
            ))
        ;
        $fb->setMethod('get');
        $filter_form = $fb->getForm();
        $filter_form->handleRequest($request);

        $odata = array(
            'all'          => 0,
            'dupl'         => 0,
            'clear'        => 0,
            'no_email'     => 0,
            'email'        => 0,
            'something'    => 0,
            'paid_1'       => 0,
            'paid_1_mix'   => 0,
            'paid_1_promo' => 0,
            'paid_2'       => 0,
            'paid_2_mix'   => 0,
            'paid_2_promo' => 0,
            'offline_1'    => 0,
            'offline_2'    => 0,
        );

        $qb = $this->em->getRepository('AppBundle:User')->createQueryBuilder('u');

        $data = $filter_form->get('show_from')->getData();
        $data = $data ? $data : new \DateTime('2016-01-01');
        $qb->andWhere('u.created_at >= :show_from')->setParameter(':show_from', $data);

        $data = $filter_form->get('show_to')->getData();
        if ($data) {
            $qb->andWhere('u.created_at <= :show_to')->setParameter(':show_to', $data);
        }

        $qbc = clone $qb;
        $qbc->select('COUNT(u.id) AS cnt');
        $odata['all'] = intval($qbc->getQuery()->getSingleScalarResult());

        $qbc = clone $qb;
        $qbc->select('COUNT(u.id) AS cnt');
        $qbc->andWhere('u.phone_mobile IS NOT NULL');
        $qbc->addGroupBy('u.phone_mobile');
        $qbc->andHaving('cnt > 1');
        $rows = $qbc->getQuery()->getScalarResult();
        foreach ($rows as $row) {
            $odata['dupl'] += $row['cnt'];
        }

        $qbc = clone $qb;
        $qbc->select('GROUP_CONCAT(u.id) AS ids');
        $qbc->addGroupBy('u.phone_mobile');
        $qbc->andWhere('u.phone_mobile IS NOT NULL');
        $qbc->andWhere('u.phone_mobile_status = :phone_status')->setParameter('phone_status', 'confirmed');
        $rows = $qbc->getQuery()->getScalarResult();

        $chunk_rows = array_chunk($rows, 50);
        foreach ($chunk_rows as $chunk_row) {
            $ids = [];
            foreach ($chunk_row as $row) {
                $ids = array_merge($ids, explode(',', $row['ids']));
            }

            $users_arr = [];
            $users = $this->em->getRepository('AppBundle:User')->createQueryBuilder('u')
                ->andWhere('u.id IN (:ids)')->setParameter('ids', $ids)
                ->leftJoin('u.required_notify', 'rn')->addSelect('rn')
                ->leftJoin('u.api_question_log', 'aql')->addSelect('aql')
                ->leftJoin('u.user_stat', 'us')->addSelect('us')
                ->leftJoin('u.payment_logs', 'pl')->addSelect('pl')
                ->leftJoin('pl.owe_stage', 'os')->addSelect('os')
                ->leftJoin('pl.promoKey', 'pk')->addSelect('pk')
                ->getQuery()->execute();
            foreach ($users as $user) { /** @var $user \My\AppBundle\Entity\User */
                $users_arr[$user->getId()] = $user;
            }

            foreach ($chunk_row as $row) {
                $combine = true;
                $ids = explode(',', $row['ids']);
                $cnt = count($ids)-1;
                for ($i = 0; $i <= $cnt; $i ++) {
                    $user = $users_arr[$ids[$i]];
                    $combine = $user->isSomethingPaid() ? false : $combine;
                    if ($user->isSomethingPaid() || ($cnt === $i && $combine)) {
                        $odata['clear'] ++;
                        $odata['no_email'] += $user->getConfirmationToken() ? 1 : 0;
                        $odata['email'] += !$user->getConfirmationToken() ? 1 : 0;
                        $odata['something'] += $user->isSomethingPaid() ? 1 : 0;
                        $odata['paid_1'] += $user->is1PaidOnlineCash() && !$user->isPromoUsedFor1() ? 1 : 0;
                        $odata['paid_1_mix'] += $user->isPromoUsedFor1() && $user->get1Sum() > 0 ? 1 : 0;
                        $odata['paid_1_promo'] += $user->isPromoUsedFor1() && $user->get1Sum() == 0 ? 1 : 0;
                        $odata['paid_2'] += $user->is2PaidOnlineCash() && !$user->isPromoUsedFor2() ? 1 : 0;
                        $odata['paid_2_mix'] += $user->isPromoUsedFor2() && $user->get2Sum() > 0 ? 1 : 0;
                        $odata['paid_2_promo'] += $user->isPromoUsedFor2() && $user->get2Sum() == 0 ? 1 : 0;
                        $odata['offline_1'] += $user->is1OfflinePaid() ? 1 : 0;
                        $odata['offline_2'] += $user->is2OfflinePaid() ? 1 : 0;
                    }
                }
            }

            $this->em->clear();
        }

        return $this->render('AppBundle:Admin:reg_stat.html.twig', array(
            'filter_form' => $filter_form->createView(),
            'odata'       => $odata,
        ));
    }

    public function apiStatAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_API_STAT')) {
            throw $this->createNotFoundException();
        }

        $form_factory = $this->container->get('form.factory');
        /** @var $fb \Symfony\Component\Form\FormBuilder */
        $fb = $form_factory->createNamedBuilder('user', 'form', array(), array('csrf_protection' => false))
            ->add('show_from', 'date', array(
                'years'       => range(2016, date('Y')),
            ))
            ->add('show_to', 'date', array(
                'years'       => range(2016, date('Y')),
                'required'    => false,
                'empty_value' => '--',
            ))
        ;
        $fb->setMethod('get');
        $filter_form = $fb->getForm();
        $filter_form->handleRequest($request);

        $odata = array(
            'all'            => 0,
            'want_pay'       => 0,
            'training_after' => 0,
            'not_going'      => 0,
        );

        $qb = $this->em->getRepository('AppBundle:ApiQuestionLog')->createQueryBuilder('aql');
        if ($data = $filter_form->get('show_from')->getData()) {
            $qb->andWhere('aql.created_at >= :show_from')->setParameter(':show_from', $data);
        } else {
            $qb->andWhere('aql.created_at >= :show_from')->setParameter(':show_from', new \DateTime('2016-01-01'));
        }
        if ($data = $filter_form->get('show_to')->getData()) {
            $qb->andWhere('aql.created_at <= :show_to')->setParameter(':show_to', $data);
        }

        $qbc = clone $qb;
        $qbc->select('COUNT(aql.id) AS cnt');
        $odata['all'] = intval($qbc->getQuery()->getSingleScalarResult());

        $qbc = clone $qb;
        $qbc->addGroupBy('aql.radio');
        $qbc->select('aql.radio, COUNT(aql.id) AS cnt');
        $results = $qbc->getQuery()->getArrayResult();
        foreach ($results as $result) {
            $odata[$result['radio']] = $result['cnt'];
        }

        return $this->render('AppBundle:Admin:api_stat.html.twig', array(
            'filter_form' => $filter_form->createView(),
            'odata'       => $odata,
        ));
    }

    public function reservistStatAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $form_factory = $this->container->get('form.factory');
        /** @var $fb \Symfony\Component\Form\FormBuilder */
        $fb = $form_factory->createNamedBuilder('user', 'form', array(), array('csrf_protection' => false))
            ->add('show_from', 'date', array(
                'years' => range(2016, date('Y')),
            ))
            ->add('show_to', 'date', array(
                'years'       => range(2016, date('Y')),
                'required'    => false,
                'empty_value' => '--',
            ));
        $fb->setMethod('get');
        $filter_form = $fb->getForm();
        $filter_form->handleRequest($request);
        $formData = [];

        $sql = 'SELECT COUNT(rs.user_id) AS cnt, 
                SUM(IF(HOUR(TIMEDIFF(rs.created, pl.created_at)) <= 24, 1,0)) AS first, 
                SUM(IF(HOUR(TIMEDIFF(rs.created, pl.created_at)) > 24, 1,0)) AS others 
                FROM (SELECT * FROM reservist_stat WHERE created IN
                (SELECT MAX(created) FROM reservist_stat AS reserv 
                 WHERE type = 0 AND reserv.created >= :show_from AND reserv.created <= :show_to 
                 GROUP BY user_id)) AS rs
                LEFT JOIN payments_logs AS pl
                ON rs.user_id = pl.uid
                AND paid = 1
                AND comment LIKE \'%"categories"%\'';
        $rsm = new Query\ResultSetMapping($this->em);
        $rsm->addScalarResult('cnt', 'cnt');
        $rsm->addScalarResult('first', 'first');
        $rsm->addScalarResult('others', 'others');

        $query = $this->em->createNativeQuery($sql, $rsm);
        if ($data = $filter_form->get('show_from')->getData()) {
            $query->setParameter('show_from', $data);
        } else {
            $query->setParameter('show_from', new \DateTime('2016-01-01'));
        }
        if ($data = $filter_form->get('show_to')->getData()) {
            $query->setParameter('show_to', $data);
        } else {
            $query->setParameter('show_to', new \DateTime());
        }
        $formData[0]['name'] = 'Доступ к теоретическому курсу';
        $formData[0]['result'] = $query->getSingleResult();

        $sql = 'SELECT COUNT(rs.user_id) AS cnt, 
                SUM(IF(HOUR(TIMEDIFF(rs.created, pl.created_at)) <= 24, 1,0)) AS first, 
                SUM(IF(HOUR(TIMEDIFF(rs.created, pl.created_at)) > 24, 1,0)) AS others 
                FROM (SELECT * FROM reservist_stat WHERE created IN
                (SELECT MAX(created) FROM reservist_stat AS reserv 
                 WHERE type = 1 AND reserv.created >= :show_from AND reserv.created <= :show_to 
                 GROUP BY user_id)) AS rs
                LEFT JOIN payments_logs AS pl
                ON rs.user_id = pl.uid
                AND paid = 1
                AND comment NOT LIKE \'%"categories"%\' AND promo_key_id IS NOT NULL';
        $rsm = new Query\ResultSetMapping($this->em);
        $rsm->addScalarResult('cnt', 'cnt');
        $rsm->addScalarResult('first', 'first');
        $rsm->addScalarResult('others', 'others');

        $query = $this->em->createNativeQuery($sql, $rsm);
        if ($data = $filter_form->get('show_from')->getData()) {
            $query->setParameter('show_from', $data);
        } else {
            $query->setParameter('show_from', new \DateTime('2016-01-01'));
        }
        if ($data = $filter_form->get('show_to')->getData()) {
            $query->setParameter('show_to', $data);
        } else {
            $query->setParameter('show_to', new \DateTime());
        }

        $formData[1]['name'] = 'Пакет регистрации в ГИБДД';
        $formData[1]['result'] = $query->getSingleResult();

        return $this->render('AppBundle:Admin:reservist_stat.html.twig', array(
            'filter_form' => $filter_form->createView(),
            'formData'    => $formData,
        ));
    }

    public function regStatUsersAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_REG_STAT')) {
            throw $this->createNotFoundException();
        }

        $discount = [
            'unpaid'               => 'discount.unpaid',
            'first'                => 'discount.first',
            'between_first_second' => 'discount.between_first_second',
            'second'               => 'discount.second',
            'after_second'         => 'discount.after_second',
        ];

        $formFactory = $this->container->get('form.factory');
        /** @var $fb \Symfony\Component\Form\FormBuilder */
        $fb = $formFactory->createNamedBuilder('user', 'form', [], ['csrf_protection' => false])
            ->add('last_name', 'text', ['required' => false])
            ->add('first_name', 'text', ['required' => false])
            ->add('patronymic', 'text', ['required' => false])
            ->add('email', 'text', ['required' => false])
            ->add('category', 'entity', [
                'class'       => 'AppBundle:Category',
                'required'    => false,
                'empty_value' => 'choose_option',
            ])
            ->add('reg_offline', 'checkbox', ['required' => false])
            ->add('reg_offline_simple', 'checkbox', ['required' => false])
            ->add('reg_api_paid_2', 'checkbox', ['required' => false])
            ->add('reg_api_paid_1', 'choice', [
                'required'    => false,
                'empty_value' => 'choose_option',
                'choices'     => $discount,
            ])
            ->add('show_from', 'date', [
                'years'       => range(2010, date('Y') + 1),
                'required'    => false,
                'empty_value' => '--',
            ])
            ->add('show_to', 'date', [
                'years'       => range(2010, date('Y') + 1),
                'required'    => false,
                'empty_value' => '--',
            ])
        ;

        $fb->setMethod('get');
        $filterForm = $fb->getForm();
        $filterForm->handleRequest($request);

        $qb = $this->em->getRepository('AppBundle:User')->createQueryBuilder('u')
            ->leftJoin('u.api_question_log', 'aql')->addSelect('aql') //for query optimization needs
            ->leftJoin('u.user_stat', 'us')->addSelect('us')
            ->andWhere('u.roles NOT LIKE :role_admin')->setParameter(':role_admin', '%"ROLE_ADMIN"%')
            ->andWhere('u.roles NOT LIKE :role_mod')->setParameter(':role_mod', '%"ROLE_MOD_%')
            ->orderBy('u.created_at')
            ->groupBy('u.id')
        ;

        $data = $filterForm->get('last_name')->getData();
        if ($data) {
            $qb->andWhere('u.last_name LIKE :last_name')->setParameter(':last_name', '%'.$data.'%');
        }

        $data = $filterForm->get('first_name')->getData();
        if ($data) {
            $qb->andWhere('u.first_name LIKE :first_name')->setParameter(':first_name', '%'.$data.'%');
        }

        $data = $filterForm->get('patronymic')->getData();
        if ($data) {
            $qb->andWhere('u.patronymic LIKE :patronymic')->setParameter(':patronymic', '%'.$data.'%');
        }

        $data = $filterForm->get('email')->getData();
        if ($data) {
            $qb->andWhere('u.email LIKE :email')->setParameter(':email', '%'.$data.'%');
        }

        $data = $filterForm->get('category')->getData();
        if ($data) {
            $qb->andWhere('u.category = :category')->setParameter(':category', $data);
        }

        $regArr = [];
        $data = $filterForm->get('reg_offline')->getData();
        if ($data) {
            $regArr[] = 'us.reg_by = \'offline\'';
        }

        $data = $filterForm->get('reg_offline_simple')->getData();
        if ($data) {
            $regArr[] = 'us.reg_by = \'offline_simple\'';
        }

        $data = $filterForm->get('reg_api_paid_2')->getData();
        if ($data) {
            $regArr[] = 'us.reg_by = \'api\' AND us.reg_type = \'paid_2\'';
        }

        $data = $filterForm->get('reg_api_paid_1')->getData();
        if ($data) {
            if ($data == 'unpaid') {
                $regArr[] = 'us.reg_by = \'api\' AND us.pay_2_type IS NULL';
            } else {
                $regArr[] = 'us.reg_by = \'api\' AND us.discount_2_type = :discount_2_type';
                $qb->setParameter(':discount_2_type', $data);
            }
        }

        if (count($regArr)) {
            $qb->andWhere(
                $qb->expr()->orX()->addMultiple($regArr)
            );
        }

        $data = $filterForm->get('show_from')->getData();
        if ($data) {
            $qb->andWhere('u.created_at >= :show_from')->setParameter(':show_from', $data);
        }

        $data = $filterForm->get('show_to')->getData();
        if ($data) {
            $qb->andWhere('u.created_at <= :show_to')->setParameter(':show_to', $data);
        }

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setCurrentPage($request->get('page'));

        return $this->render('AppBundle:Admin:reg_stat_users.html.twig', [
            'pagerfanta'  => $pagerfanta,
            'filter_form' => $filterForm->createView(),
        ]);
    }

    public function regStatUserViewAction($id)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_REG_STAT')) {
            throw $this->createNotFoundException();
        }

        $userRepo = $this->em->getRepository('AppBundle:User');
        $user     = $userRepo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User for id "'.$id.'" not found.');
        }

        if ($user == $this->getUser()) {
            throw $this->createNotFoundException('You can\'t moderate yourself');
        }

        $categories     = [];
        $categoriesOrig = $this->em->getRepository('AppBundle:Category')->createQueryBuilder('c')
            ->getQuery()->execute();
        foreach ($categoriesOrig as $category) { /** @var $category \My\AppBundle\Entity\Category */
            $categories[$category->getId()] = $category;
        }

        $services     = [];
        $servicesOrig = $this->em->getRepository('AppBundle:Service')->createQueryBuilder('s')
            ->getQuery()->execute();
        foreach ($servicesOrig as $service) { /** @var $service \My\AppBundle\Entity\Service */
            $services[$service->getId()] = $service;
        }

        $paidOweStage = [];
        $payments     = [];

        $logs = $this->em->getRepository('PaymentBundle:Log')->createQueryBuilder('l')
            ->andWhere('l.user = :user')->setParameter(':user', $user)
            ->andWhere('l.paid = :paid')->setParameter(':paid', true)
            ->leftJoin('l.promoKey', 'pk')
            ->addSelect('pk')
            ->leftJoin('pk.promo', 'p')
            ->addSelect('p')
            ->leftJoin('l.owe_stage', 'os')
            ->addSelect('os')
            ->addOrderBy('l.updated_at', 'ASC')
            ->getQuery()->getArrayResult();
        foreach ($logs as $log) {
            $comment = json_decode($log['comment'], true);

            $log['categories'] = [];
            $log['services']   = [];

            //Модератор, который добавил пользователя
            $moderatorName = null;
            if (!empty($comment['moderator_id'])) {
                /** @var $moderator \My\AppBundle\Entity\User */
                $moderator = $userRepo->find($comment['moderator_id']);
                if ($moderator) {
                    $moderatorName = $moderator->getFullName();
                }
            }

            if (!empty($comment['categories'])) {
                $categoriesIds = explode(',', $comment['categories']);
                foreach ($categoriesIds as $categoryId) {
                    if (isset($categories[$categoryId])) {
                        $log['categories'][$categoryId] = $categories[$categoryId];
                    }
                }

                if (count($log['categories']) > 0) {
                    if ($moderatorName) {
                        $log['moderator_name'] = $moderatorName;
                    }

                    if (!empty($comment['auto_promo'])) {
                        $log['auto_promo'] = $comment['auto_promo'];
                    }

                    $payments[] = $log;
                }
            }

            if (!empty($comment['services'])) {
                $servicesIds = explode(',', $comment['services']);
                foreach ($servicesIds as $serviceId) {
                    if (isset($services[$serviceId])) {
                        $log['services'][$serviceId] = $services[$serviceId];
                    } else {
                        /** @CAUTION наследие %) */
                        $log['services'][$serviceId] = ['name' => 'Доступ к теоретическому курсу'];
                    }
                }

                if (count($log['services']) > 0) {
                    if ($moderatorName) {
                        $log['moderator_name'] = $moderatorName;
                    }

                    $payments[] = $log;
                }
            }

            if (!empty($comment['owe_stage'])) {
                $paidOweStage = [];
                if ($moderatorName) {
                    $log['moderator_name'] = $moderatorName;
                }

                $paidOweStage[$log['id']] = 'Должник этап №'.$log['owe_stage']['number_stage'];
                $payments[]               = $log;
            }
        }

        $version = $this->em->getRepository('AppBundle:TrainingVersion')->createQueryBuilder('v')
            ->andWhere('v.category = :category')->setParameter(':category', $user->getCategory())
            ->andWhere('v.start_date <= :start_date')
            ->setParameter(':start_date', date_format($user->getCreatedAt(), 'Y-m-d'))
            ->addOrderBy('v.start_date', 'DESC')
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();

        $subjectsRepository = $this->em->getRepository('AppBundle:Subject');
        $subjects           = $subjectsRepository->findAllAsArray($user, $version);

        $finalExamsLogsRepository = $this->em->getRepository('AppBundle:FinalExamLog');
        $passedDate               = $finalExamsLogsRepository->getPassedDate($user);
        $isPassed                 = (bool)$passedDate;

        return $this->render('AppBundle:Admin:reg_stat_user_view.html.twig', [
            'user'           => $user,
            'payments'       => $payments,
            'subjects'       => $subjects,
            'passed_date'    => $passedDate,
            'is_passed'      => $isPassed,
            'paid_owe_stage' => $paidOweStage ? $paidOweStage : null,
        ]);
    }

    public function paymentReportAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_REG_STAT')) {
            throw $this->createNotFoundException();
        }

        $service_type_choices = [
            ''                     => 'Все',
            'payment_1'            => 'Т1 - Оплата 1',
            'payment_2'            => 'Т2 - Оплата 2',
            'payment_1,2'          => 'Т1,2 - Оплата 1,2',
            'first_drive'          => 'ВОСН - Вождение, основной талон',
            'second_drive'         => 'ВДОП - Вождение, дополнительный талон',
            'attempt_packages'     => 'ПСД - Пакеты попыток',
            'owe_stage'            => 'ПРДЛ - Просрочка обучения',
            'dop_attempts'         => 'ДОП - Дополнительные услуги'
        ];

        $acquiring_choices = [
            'psb'       => 'ДО ПСБ',
            'robokassa' => 'ДО Робокасса',
            'api'       => 'АО ПСБ',
        ];

        $form_factory = $this->container->get('form.factory');
        /** @var $fb \Symfony\Component\Form\FormBuilder */
        $fb = $form_factory->createNamedBuilder('user', 'form', array(), array('csrf_protection' => false))
            ->add('show_from', 'date', array(
                'years'       => range(2014, date('Y')),
                'required'    => false,
                'empty_value' => '--',
                'label'       => 'Дата начала',
            ))
            ->add('show_to', 'date', array(
                'years'       => range(2014, date('Y')),
                'required'    => false,
                'empty_value' => '--',
                'label'       => 'Дата окончания',
            ))
            ->add('service_type', 'choice', array(
                'label'       => 'Тип услуги',
                'empty_value' => 'Все',
                'required'    => false,
                'choices'     => $service_type_choices,
            ))
            ->add('acquiring', 'choice', array(
                'label'       => 'Эквайринг',
                'empty_value' => 'Все',
                'required'    => false,
                'choices'     => $acquiring_choices,
                'multiple'    => true,
            ))
            ->add('last_name', 'text', array('required' => false))
            ->add('first_name', 'text', array('required' => false))
            ->add('patronymic', 'text', array('required' => false))
            ->add('transaction_number', 'text', ['required' => false, 'label' => 'Номер транзакции'])
            ->add('paradox_id', 'text', ['required' => false, 'label' => 'Код слушателя'])
        ;
        $fb->setMethod('get');
        $filter_form = $fb->getForm();
        $filter_form->handleRequest($request);

        $statistics             = [];
        $pagerfanta             = null;
        $transactions           = [];
        $info['show_to']        = time();
        $info['service_type']   = '';
        $info['paradox_id']     = '';
        $all_services_true      = [];
        $all_services_false     = [];

        if ($filter_form->isValid()) {
            $services = $this->em->getRepository('AppBundle:Service')->createQueryBuilder('s')
                ->getQuery()->getArrayResult();
            foreach ($services as $service) {
                if ($service['type']) {
                    $all_services_true[] = $service['id'];
                } else {
                    $all_services_false[] = $service['id'];
                }
            }

            $services_second_pay = '%"services":"'.implode(',', $all_services_true).'"%';

            $qb = $this->em->getRepository('PaymentBundle:Log')->createQueryBuilder('l')
                ->leftJoin('l.user', 'u')
                ->andWhere('l.paid = :paid')->setParameter('paid', true)
                ->andWhere('l.s_id IS NOT NULL')
                ->andWhere('l.sum > 0')
            ;

            $data = $filter_form->get('show_from')->getData();
            if ($data) {
                $qb->andWhere('l.updated_at >= :show_from')->setParameter(':show_from', $data);
                $info['show_from'] = $filter_form->get('show_from')->getData();
            }

            $data = $filter_form->get('show_to')->getData();
            if ($data) {
                $qb->andWhere('l.updated_at <= :show_to')->setParameter(':show_to', $data);
                $info['show_to'] = $filter_form->get('show_to')->getData();
            }

            $data = $filter_form->get('last_name')->getData();
            if ($data) {
                $qb->andWhere('u.last_name LIKE :last_name')->setParameter(':last_name', '%'.$data.'%');
            }

            $data = $filter_form->get('first_name')->getData();
            if ($data) {
                $qb->andWhere('u.first_name LIKE :first_name')->setParameter(':first_name', '%'.$data.'%');
            }

            $data = $filter_form->get('patronymic')->getData();
            if ($data) {
                $qb->andWhere('u.patronymic LIKE :patronymic')->setParameter(':patronymic', '%'.$data.'%');
            }

            $data = $filter_form->get('transaction_number')->getData();
            if ($data) {
                $qb->andWhere('l.s_id = :transaction_number')->setParameter('transaction_number', $data);
            }

            $data = $filter_form->get('paradox_id')->getData();
            if ($data) {
                $qb->andWhere('u.paradox_id = :paradox_id')->setParameter('paradox_id', $data);
                $info['paradox_id'] = $data;
            }

            $data = $filter_form->get('service_type')->getData();

            if ($data == 'payment_1') {
                $qb->andWhere('l.comment LIKE :categories')->setParameter(':categories', '%"categories"%');
                $qb->andWhere('l.comment NOT LIKE :not_paid')->setParameter(':not_paid', '%"paid"%');
                $qb->andWhere('l.comment NOT LIKE :not_services')->setParameter(':not_services', '%"services"%');
                $info['service_type'] = $service_type_choices['payment_1'];
            }

            if ($data == 'payment_2') {
                $qb->andWhere('l.comment LIKE :services')->setParameter(':services', $services_second_pay);
                $qb->andWhere('l.comment NOT LIKE :categories')->setParameter(':categories', '%"categories"%');
                $info['service_type'] = $service_type_choices['payment_2'];
            }

            if ($data == 'payment_1,2') {
                $qb->andWhere('l.comment LIKE :categories')->setParameter(':categories', '%"categories"%');
                $qb->andWhere('l.comment NOT LIKE :not_paid')->setParameter(':not_paid', '%"paid"%');
                $qb->andWhere('l.comment LIKE :services')->setParameter(':services', $services_second_pay);
                $info['service_type'] = $service_type_choices['payment_1,2'];
            }

            if ($data == 'first_drive') {
                $qb->andWhere('l.comment LIKE :comment')->setParameter(':comment', '%"first_drive"%');
                $info['service_type'] = $service_type_choices['first_drive'];
            }

            if ($data == 'second_drive') {
                $qb->andWhere('l.comment LIKE :comment')->setParameter(':comment', '%"second_drive"%');
                $info['service_type'] = $service_type_choices['second_drive'];
            }

            if ($data == 'attempt_packages') {
                $qb->andWhere('l.comment LIKE :comment')->setParameter(':comment', '%"attemptsPackage"%');
                $info['service_type'] = $service_type_choices['attempt_packages'];
            }

            if ($data == 'owe_stage') {
                $qb->andWhere('l.owe_stage IS NOT NULL');
                $info['service_type'] = $service_type_choices['owe_stage'];
            }

            if ($data == 'dop_attempts') {
                $string_query = [];
                foreach ($all_services_false as $all_service_false) {
                    $string_query[] = 'l.comment LIKE :not_services_da'.$all_service_false;
                    $string_search = '%"services":"'.$all_service_false.'"%';
                    $qb->setParameter(':not_services_da'.$all_service_false, $string_search);
                }

                $string_query_true = implode(' OR ', $string_query);
                $qb->andWhere('('.$string_query_true.')');

                $info['service_type'] = $service_type_choices['dop_attempts'];
            }

            $data = $filter_form->get('acquiring')->getData();

            if (!empty($data) and $data[0] != '') {
                $qb->andWhere('l.s_type IN (:s_types)')->setParameter('s_types', $data);
            }

            $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
            $pagerfanta->setMaxPerPage(50);
            $pagerfanta->setCurrentPage($request->get('page', 1));

            $transactions = $pagerfanta->getCurrentPageResults();

            $info['transaction_amount'] = $qb->select('count(l.id)')->getQuery()->getSingleScalarResult();
            $info['transaction_sum']    = $qb->select('sum(l.sum)')->getQuery()->getSingleScalarResult();
        }

        foreach ($transactions as $transaction) { /** @var $transaction \My\PaymentBundle\Entity\Log */
            $date = $transaction->getUpdatedAt()->format('Y-m-d');

            if (!isset($statistics[$date]['transaction_amount'])) {
                $statistics[$date]['transaction_amount'] = 0;
            }

            $statistics[$date]['transaction_amount']++;

            if (!isset($statistics[$date]['transaction_sum'])) {
                $statistics[$date]['transaction_sum'] = 0;
            }

            if (!isset($statistics[$date]['service_type'])) {
                $statistics[$date]['service_type'] = '';
            }

            $statistics[$date]['transaction_sum'] += $transaction->getSum();
            $statistics[$date]['service_type'] = $info['service_type'];

            $log_transactions = array(
                'id_transaction' => $transaction->getId(),
                'updated_at'     => $transaction->getUpdatedAt(),
                's_id'           => $transaction->getSId(),
                's_type'         => $transaction->getSType(),
                'last_name'      => $transaction->getUser()->getLastName(),
                'first_name'     => $transaction->getUser()->getFirstName(),
                'patronymic'     => $transaction->getUser()->getPatronymic(),
                'paradox_id'     => $transaction->getUser()->getParadoxId(),
                'sum'            => $transaction->getSum(),
            );

            $comment = json_decode($transaction->getComment(), true);
            $services_first_two = false;

            if (isset($comment['services'])) {
                $ids = explode(',', $comment['services']);
                if ($ids == $all_services_true) {
                    $log_transactions['service_type'] = 'Т2';
                    $services_first_two = true;
                }
                if (array_intersect($ids, $all_services_false)) {
                    $log_transactions['service_type'] = 'ДОП';
                }
            }

            if (isset($comment['categories'])) {
                if (!isset($comment['services'])) {
                    if (!isset($comment['paid'])) {
                        $log_transactions['service_type'] = 'Т1';
                    } elseif (isset($comment['paid'])) {
                        if ($comment['paid'] == "first_drive") {
                            $log_transactions['service_type'] = 'ВОСН';
                        }

                        if ($comment['paid'] == "second_drive") {
                            $log_transactions['service_type'] = 'ВДОП';
                        }
                    }
                } elseif ($services_first_two) {
                    $log_transactions['service_type'] = 'Т1,2';
                }
            }

            if (isset($comment['attemptsPackage'])) {
                $log_transactions['service_type'] = 'ПСД';
            }

            if ($transaction->getOweStage()) {
                $log_transactions['service_type'] = 'ПРДЛ';
            }

            $statistics[$date]['transactions'][] = $log_transactions;
        }

        return $this->render('AppBundle:Admin:payment_report.html.twig', array(
            'filter_form'   => $filter_form->createView(),
            'transactions'  => $transactions,
            'info'          => $info,
            'statistics'    => $statistics,
            'pagerfanta'    => $pagerfanta,
        ));
    }
}
