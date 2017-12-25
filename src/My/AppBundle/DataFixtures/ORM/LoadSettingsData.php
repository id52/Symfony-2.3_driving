<?php

namespace My\AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use My\AppBundle\Entity\Setting;

class LoadSettingsData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $setting = new Setting();
        $setting->setKey('theme_test_correct_answers');
        $setting->setValue(2);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('theme_test_correct_answers_in_row');
        $setting->setValue(false);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('theme_test_questions_method');
        $setting->setValue('shuffle');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('theme_test_time');
        $setting->setValue(0);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('theme_test_shuffle_answers');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('slice_tickets');
        $setting->setValue(1);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('slice_questions_in_ticket');
        $setting->setValue(20);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('slice_not_repeat_questions_in_tickets');
        $setting->setValue(false);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('slice_max_errors_in_ticket');
        $setting->setValue(2);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('slice_ticket_time');
        $setting->setValue(0);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('slice_shuffle_answers');
        $setting->setValue(1);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('exam_shuffle');
        $setting->setValue(false);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('exam_tickets');
        $setting->setValue(2);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('exam_questions_in_ticket');
        $setting->setValue(20);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('exam_not_repeat_questions_in_tickets');
        $setting->setValue(false);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('exam_max_errors_in_ticket');
        $setting->setValue(1);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('exam_ticket_time');
        $setting->setValue(10);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('exam_shuffle_answers');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('exam_retake_time');
        $setting->setValue(24);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('final_exam_1_shuffle');
        $setting->setValue(false);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('final_exam_1_tickets');
        $setting->setValue(2);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('final_exam_1_max_errors_in_ticket');
        $setting->setValue(1);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('final_exam_1_ticket_time');
        $setting->setValue(10);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('final_exam_1_shuffle_answers');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('final_exam_2_tickets');
        $setting->setValue(1);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('final_exam_2_questions_in_ticket');
        $setting->setValue(10);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('final_exam_2_not_repeat_questions_in_tickets');
        $setting->setValue(false);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('final_exam_2_max_errors_in_ticket');
        $setting->setValue(1);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('final_exam_2_ticket_time');
        $setting->setValue(10);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('final_exam_2_shuffle_answers');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('access_time_after_1_payment');
        $setting->setValue(14);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('access_time_after_2_payment');
        $setting->setValue(365);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('attempts_days_of_retake');
        $setting->setValue(10);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('attempts_to_reset');
        $setting->setValue(30);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('attempts_reset_progress_text');
        $setting->setValue('Текст уведомления пользователю о том, что его прогресс обучения сброшен {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('access_time_end_notify_after_1_payment_1');
        $setting->setValue(1);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('access_time_end_notify_after_1_payment_2');
        $setting->setValue(3);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('access_time_end_notify_after_1_payment_3');
        $setting->setValue(7);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('access_time_end_notify_after_1_payment_4');
        $setting->setValue(14);
        $setting->setType('integer');
        $manager->persist($setting);

        for ($i = 5; $i <= 16; $i ++) {
            $setting = new Setting();
            $setting->setKey('access_time_end_notify_after_1_payment_'.$i);
            $setting->setValue(0);
            $setting->setType('integer');
            $manager->persist($setting);
        }

        $setting = new Setting();
        $setting->setKey('access_time_end_notify_after_2_payment_1');
        $setting->setValue(1);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('access_time_end_notify_after_2_payment_2');
        $setting->setValue(3);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('access_time_end_notify_after_2_payment_3');
        $setting->setValue(7);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('access_time_end_notify_after_2_payment_4');
        $setting->setValue(14);
        $setting->setType('integer');
        $manager->persist($setting);

        for ($i = 5; $i <= 16; $i ++) {
            $setting = new Setting();
            $setting->setKey('access_time_end_notify_after_2_payment_'.$i);
            $setting->setValue(0);
            $setting->setType('integer');
            $manager->persist($setting);
        }

        $setting = new Setting();
        $setting->setKey('notify_no_payments_1');
        $setting->setValue(7);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('notify_no_payments_2');
        $setting->setValue(14);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('notify_no_payments_3');
        $setting->setValue(21);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('notify_no_payments_4');
        $setting->setValue(28);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('notify_no_payments_5');
        $setting->setValue(35);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('landing_title');
        $setting->setValue('Title');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('landing_keywords');
        $setting->setValue('Meta keywords');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('landing_description');
        $setting->setValue('Meta description');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('contact_email');
        $setting->setValue('contact@example.com');
        $setting->setType('string');
        $manager->persist($setting);
        
        $setting = new Setting();
        $setting->setKey('social_vk');
        $setting->setValue('http://vk.com/example');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('social_twitter');
        $setting->setValue('http://twitter.com/example');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('confirmation_registration_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('confirmation_registration_title');
        $setting->setValue('[Email] Подтверждение регистрации {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('confirmation_registration_text');
        $setting->setValue('[Email] Подтверждение регистрации {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('confirmed_registration_title');
        $setting->setValue('[Text] Текст на странице после регистрации {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('confirmed_registration_text');
        $setting->setValue('[Text] Текст на странице после регистрации {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('resetting_password_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('resetting_password_title');
        $setting->setValue('[Email] Сброс пароля {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('resetting_password_text');
        $setting->setValue('[Email] Сброс пароля {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('no_payments_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('no_payments_title');
        $setting->setValue('[Email] Оповещение об отсутствии оплат {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('no_payments_text');
        $setting->setValue('[Email] Оповещение об отсутствии оплат {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('pay_now_title');
        $setting->setValue('[Popup] Не оплачен второй платёж {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('pay_now_text');
        $setting->setValue('[Popup] Не оплачен второй платёж {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_without_2_payment_title');
        $setting->setValue('[Text] Не оплачен второй платёж {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_without_2_payment_text');
        $setting->setValue('[Text] Не оплачен второй платёж {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('first_payment_text');
        $setting->setValue('[Text] Текст на странице первой оплаты {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('first_payment_promo_discount_text');
        $setting->setValue('[Text] Текст на странице первой оплаты {Если есть скидка по промо-кампании}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('second_payment_title');
        $setting->setValue('[Text] Текст на странице второго платежа {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('second_payment_text');
        $setting->setValue('[Text] Текст на странице второго платежа {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('payments_mobile_not_confirmed_title');
        $setting->setValue('[Text] Номер мобильного телефона не подтверждён {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('payments_mobile_not_confirmed_text');
        $setting->setValue('[Text] Номер мобильного телефона не подтверждён {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_confirm_mobile_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_confirm_mobile_title');
        $setting->setValue('[Notify] Подтверждения номера мобильного телефона {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_confirm_mobile_text');
        $setting->setValue('[Notify] Подтверждения номера мобильного телефона {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_confirm_mobile_email_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_confirm_mobile_email_title');
        $setting->setValue('[Email] Подтверждения номера мобильного телефона {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_confirm_mobile_email_text');
        $setting->setValue('[Email] Подтверждения номера мобильного телефона {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_1_payment_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_1_payment_title');
        $setting->setValue('[Notify] После первой оплаты {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_1_payment_text');
        $setting->setValue('[Notify] После первой оплаты {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_1_promo_payment_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_1_promo_payment_title');
        $setting->setValue('[Notify] После первой оплаты с Промокодом {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_1_promo_payment_text');
        $setting->setValue('[Notify] После первой оплаты с Промокодом {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_1_payment_email_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_1_payment_email_title');
        $setting->setValue('[Email] После первой оплаты {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_1_payment_email_text');
        $setting->setValue('[Email] После первой оплаты {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_1_promo_payment_email_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_1_promo_payment_email_title');
        $setting->setValue('[Email] После первой оплаты с Промокодом {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_1_promo_payment_email_text');
        $setting->setValue('[Email] После первой оплаты с Промокодом {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('timelimit_after_1_payment_title');
        $setting->setValue('[Text] Тестовый период закончился {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('timelimit_after_1_payment_text');
        $setting->setValue('[Text] Тестовый период закончился {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_2_payment_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_2_payment_title');
        $setting->setValue('[Notify] После второй оплаты {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_2_payment_text');
        $setting->setValue('[Notify] После второй оплаты {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_2_promo_payment_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_2_promo_payment_title');
        $setting->setValue('[Notify] После второй оплаты с Промокодом {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_2_promo_payment_text');
        $setting->setValue('[Notify] После второй оплаты с Промокодом {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_2_payment_email_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_2_payment_email_title');
        $setting->setValue('[Email] После второй оплаты {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_2_payment_email_text');
        $setting->setValue('[Email] После второй оплаты {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_2_promo_payment_email_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_2_promo_payment_email_title');
        $setting->setValue('[Email] После второй оплаты с Промокодом {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_2_promo_payment_email_text');
        $setting->setValue('[Email] После второй оплаты с Промокодом {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_payment_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_payment_title');
        $setting->setValue('[Notify] После любой другой оплаты {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_payment_text');
        $setting->setValue('[Notify] После любой другой оплаты {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_promo_payment_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_promo_payment_title');
        $setting->setValue('[Notify] После любой другой оплаты с Промокодом {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_promo_payment_text');
        $setting->setValue('[Notify] После любой другой оплаты с Промокодом {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_payment_email_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_payment_email_title');
        $setting->setValue('[Email] После любой другой оплаты {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_payment_email_text');
        $setting->setValue('[Email] После любой другой оплаты {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_promo_payment_email_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_promo_payment_email_title');
        $setting->setValue('[Email] После любой другой оплаты с Промокодом {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_promo_payment_email_text');
        $setting->setValue('[Email] После любой другой оплаты с Промокодом {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $subjects = $manager->getRepository('AppBundle:Subject')->findAll();
        foreach ($subjects as $subject) {
            $setting = new Setting();
            $setting->setKey('after_all_slices_'.$subject->getId().'_enabled');
            $setting->setValue(true);
            $setting->setType('boolean');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_all_slices_'.$subject->getId().'_title');
            $setting->setValue('[Notify] После всех срезов по предмету «'.$subject->getTitle().'» {Заголовок}');
            $setting->setType('string');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_all_slices_'.$subject->getId().'_text');
            $setting->setValue('[Notify] После всех срезов по предмету «'.$subject->getTitle().'» {Текст}');
            $setting->setType('string');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_all_slices_'.$subject->getId().'_email_enabled');
            $setting->setValue(true);
            $setting->setType('boolean');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_all_slices_'.$subject->getId().'_email_title');
            $setting->setValue('[Email] После всех срезов по предмету «'.$subject->getTitle().'» {Заголовок}');
            $setting->setType('string');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_all_slices_'.$subject->getId().'_email_text');
            $setting->setValue('[Email] После всех срезов по предмету «'.$subject->getTitle().'» {Текст}');
            $setting->setType('string');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_fail_exam_'.$subject->getId().'_enabled');
            $setting->setValue(true);
            $setting->setType('boolean');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_fail_exam_'.$subject->getId().'_title');
            $setting->setValue('[Notify] После провала экзамена по предмету «'.$subject->getTitle().'» {Заголовок}');
            $setting->setType('string');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_fail_exam_'.$subject->getId().'_text');
            $setting->setValue('[Notify] После провала экзамена по предмету «'.$subject->getTitle().'» {Текст}');
            $setting->setType('string');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_fail_exam_'.$subject->getId().'_email_enabled');
            $setting->setValue(true);
            $setting->setType('boolean');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_fail_exam_'.$subject->getId().'_email_title');
            $setting->setValue('[Email] После провала экзамена по предмету «'.$subject->getTitle().'» {Заголовок}');
            $setting->setType('string');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_fail_exam_'.$subject->getId().'_email_text');
            $setting->setValue('[Email] После провала экзамена по предмету «'.$subject->getTitle().'» {Текст}');
            $setting->setType('string');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_exam_'.$subject->getId().'_enabled');
            $setting->setValue(true);
            $setting->setType('boolean');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_exam_'.$subject->getId().'_title');
            $setting->setValue('[Notify] После экзамена по предмету «'.$subject->getTitle().'» {Заголовок}');
            $setting->setType('string');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_exam_'.$subject->getId().'_text');
            $setting->setValue('[Notify] После экзамена по предмету «'.$subject->getTitle().'» {Текст}');
            $setting->setType('string');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_exam_'.$subject->getId().'_email_enabled');
            $setting->setValue(true);
            $setting->setType('boolean');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_exam_'.$subject->getId().'_email_title');
            $setting->setValue('[Email] После экзамена по предмету «'.$subject->getTitle().'» {Заголовок}');
            $setting->setType('string');
            $manager->persist($setting);

            $setting = new Setting();
            $setting->setKey('after_exam_'.$subject->getId().'_email_text');
            $setting->setValue('[Email] После экзамена по предмету «'.$subject->getTitle().'» {Текст}');
            $setting->setType('string');
            $manager->persist($setting);
        }

        $setting = new Setting();
        $setting->setKey('after_all_exams_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_all_exams_title');
        $setting->setValue('[Notify] После всех экзаменов по предметам {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_all_exams_text');
        $setting->setValue('[Notify] После всех экзаменов по предметам {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_all_exams_email_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_all_exams_email_title');
        $setting->setValue('[Email] После всех экзаменов по предметам {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_all_exams_email_text');
        $setting->setValue('[Email] После всех экзаменов по предметам {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_final_exam_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_final_exam_title');
        $setting->setValue('[Notify] После финального экзамена {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_final_exam_text');
        $setting->setValue('[Notify] После финального экзамена {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_final_exam_email_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_final_exam_email_title');
        $setting->setValue('[Email] После финального экзамена {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_final_exam_email_text');
        $setting->setValue('[Email] После финального экзамена {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('final_exam_1_extra_time');
        $setting->setValue(5);
        $setting->setType('integer');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('before_access_time_end_after_1_payment_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('before_access_time_end_after_1_payment_title');
        $setting->setValue('[Notify] До окончания периода после первой оплаты {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('before_access_time_end_after_1_payment_text');
        $setting->setValue('[Notify] До окончания периода после первой оплаты {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('before_access_time_end_after_1_payment_email_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('before_access_time_end_after_1_payment_email_title');
        $setting->setValue('[Email] До окончания периода после первой оплаты {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('before_access_time_end_after_1_payment_email_text');
        $setting->setValue('[Email] До окончания периода после первой оплаты {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_access_time_end_after_1_payment_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_access_time_end_after_1_payment_title');
        $setting->setValue('[Email] После окончания периода после первой оплаты {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_access_time_end_after_1_payment_text');
        $setting->setValue('[Email] После окончания периода после первой оплаты {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('before_access_time_end_after_2_payment_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('before_access_time_end_after_2_payment_title');
        $setting->setValue('[Notify] До окончания периода после второй оплаты {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('before_access_time_end_after_2_payment_text');
        $setting->setValue('[Notify] До окончания периода после второй оплаты {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('before_access_time_end_after_2_payment_email_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('before_access_time_end_after_2_payment_email_title');
        $setting->setValue('[Email] До окончания периода после второй оплаты {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('before_access_time_end_after_2_payment_email_text');
        $setting->setValue('[Email] До окончания периода после второй оплаты {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_access_time_end_after_2_payment_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_access_time_end_after_2_payment_title');
        $setting->setValue('[Email] После окончания периода после второй оплаты {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('after_access_time_end_after_2_payment_text');
        $setting->setValue('[Email] После окончания периода после второй оплаты {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('profile_final_exam');
        $value = 'Сообщения в профиле {Текст о сдаче финального экзамена с ссылкой';
        $value .= ' (вставьте %certificate_link% для замены на адрес для скачивания)}';
        $setting->setValue($value);
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_theme_test_success');
        $setting->setValue('Сообщения в тестировании после темы {Верно ответили на вопрос}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_theme_test_error');
        $setting->setValue('Сообщения в тестировании после темы {Неверно ответили на вопрос}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_theme_test_timeout');
        $setting->setValue('Сообщения в тестировании после темы {Время закончилось}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_theme_test_complete_next');
        $setting->setValue('Сообщения в тестировании после темы {Тестирование завершено, но есть следующая тема}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_theme_test_complete_list');
        $setting->setValue('Сообщения в тестировании после темы {Тестирование завершено, но нет следующей темы}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_theme_test_long_time');
        $setting->setValue('Сообщения в тестировании после темы {Долго отсутствовал}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_slice_success');
        $setting->setValue('Сообщения в срезах {Верно ответили на вопрос}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_slice_error');
        $setting->setValue('Сообщения в срезах {Неверно ответили на вопрос}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_slice_timeout');
        $setting->setValue('Сообщения в срезах {Время закончилось}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_slice_complete');
        $setting->setValue('Сообщения в срезах {Срез завершён}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_slice_long_time');
        $setting->setValue('Сообщения в срезах {Долго отсутствовал}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_slice_max_errors');
        $setting->setValue('Сообщения в срезах {Много ошибок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_exam_success');
        $setting->setValue('Сообщения в экзаменах {Верно ответили на вопрос}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_exam_error');
        $setting->setValue('Сообщения в экзаменах {Неверно ответили на вопрос}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_exam_timeout');
        $setting->setValue('Сообщения в экзаменах {Время закончилось}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_exam_complete');
        $setting->setValue('Сообщения в экзаменах {Экзамен завершён}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_exam_long_time');
        $setting->setValue('Сообщения в экзаменах {Долго отсутствовал}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_exam_max_errors');
        $setting->setValue('Сообщения в экзаменах {Много ошибок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_exam_retake');
        $setting->setValue('Сообщения в экзаменах {Пересдача не возможна}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_final_exam_description');
        $setting->setValue('Сообщения в финальном экзамене {Описание}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_final_exam_success');
        $setting->setValue('Сообщения в финальном экзамене {Верно ответили на вопрос}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_final_exam_error');
        $setting->setValue('Сообщения в финальном экзамене {Неверно ответили на вопрос}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_final_exam_timeout');
        $setting->setValue('Сообщения в финальном экзамене {Время закончилось}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_final_exam_complete');
        $setting->setValue('Сообщения в финальном экзамене {Финальный экзамен завершён}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_final_exam_long_time');
        $setting->setValue('Сообщения в финальном экзамене {Долго отсутствовал}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_final_exam_max_errors');
        $setting->setValue('Сообщения в финальном экзамене {Много ошибок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_final_exam_retake');
        $setting->setValue('Сообщения в финальном экзамене {Пересдача не возможна}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_final_exam_denied');
        $setting->setValue('Сообщения в финальном экзамене {Не доступен}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_final_exam_passed');
        $value = 'Сообщения в финальном экзамене';
        $value .= ' {Уже прошли (%certificate_link% заменится на адрес для скачивания сертификата)}';
        $setting->setValue($value);
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_test_success');
        $setting->setValue('Сообщения в тесте как в ГИБДД по билетам {Верно ответили на вопрос}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_test_error');
        $setting->setValue('Сообщения в тесте как в ГИБДД по билетам {Неверно ответили на вопрос}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_test_timeout');
        $setting->setValue('Сообщения в тесте как в ГИБДД по билетам {Время закончилось}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_test_complete');
        $setting->setValue('Сообщения в тесте как в ГИБДД по билетам {Тестирование завершено}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_test_long_time');
        $setting->setValue('Сообщения в тесте как в ГИБДД по билетам {Долго отсутствовал}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_test_max_errors');
        $setting->setValue('Сообщения в тесте как в ГИБДД по билетам {Много ошибок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_test_knowledge_success');
        $setting->setValue('Сообщения в тесте как в ГИБДД по темам {Верно ответили на вопрос}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_test_knowledge_error');
        $setting->setValue('Сообщения в тесте как в ГИБДД по темам {Неверно ответили на вопрос}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_test_knowledge_timeout');
        $setting->setValue('Сообщения в тесте как в ГИБДД по темам {Время закончилось}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_test_knowledge_complete');
        $setting->setValue('Сообщения в тесте как в ГИБДД по темам {Тестирование завершено}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_test_knowledge_long_time');
        $setting->setValue('Сообщения в тесте как в ГИБДД по темам {Долго отсутствовал}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('training_test_knowledge_max_errors');
        $setting->setValue('Сообщения в тесте как в ГИБДД по темам {Много ошибок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('trainings_help_btn');
        $setting->setValue('Кнопки помощи {В обучении}');
        $setting->setType('string');
        $manager->persist($setting);

        foreach ($subjects as $subject) {
            $setting = new Setting();
            $setting->setKey('training_'.$subject->getId().'_help_btn');
            $setting->setValue('Кнопки помощи {В предмете «'.$subject->getTitle().'»}');
            $setting->setType('string');
            $manager->persist($setting);
        }

        $setting = new Setting();
        $setting->setKey('theme_help_btn');
        $setting->setValue('Кнопки помощи {В темах}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('question_help_btn');
        $setting->setValue('Кнопки помощи {В вопросах}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('promo_help_btn');
        $setting->setValue('Кнопки помощи {О Промокодах}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('discount_2_notify_first');
        $setting->setValue('Скидки для регистрации в ГИБДД {[Popup] В начале первого этапа}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('discount_2_notify_second');
        $setting->setValue('Скидки для регистрации в ГИБДД {[Popup] В начале второго этапа}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('discount_2_warning_sms_first');
        $setting->setValue('Скидки для регистрации в ГИБДД {[SMS] Перед завершением первого этапа}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('discount_2_missing_sms');
        $setting->setValue('Скидки для регистрации в ГИБДД {[SMS] В начале второго этапа}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('discount_2_warning_sms_second');
        $setting->setValue('Скидки для регистрации в ГИБДД {[SMS] Перед завершением второго этапа}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('discount_2_notify_first_email_title');
        $setting->setValue('[E-mail] В начале первого этапа скидки для регистрации в ГИБДД {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('discount_2_notify_first_email_text');
        $setting->setValue('[E-mail] В начале первого этапа скидки для регистрации в ГИБДД {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('discount_2_warning_first_email_title');
        $setting->setValue('[E-mail] Перед завершением первого этапа скидки для регистрации в ГИБДД {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('discount_2_warning_first_email_text');
        $setting->setValue('[E-mail] Перед завершением первого этапа скидки для регистрации в ГИБДД {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('discount_2_missing_email_title');
        $setting->setValue('[E-mail] В начале второго этапа скидки для регистрации в ГИБДД {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('discount_2_missing_email_text');
        $setting->setValue('[E-mail] В начале второго этапа скидки для регистрации в ГИБДД {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('discount_2_warning_second_email_title');
        $setting->setValue('[E-mail] Перед завершением второго этапа скидки для регистрации в ГИБДД {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('discount_2_warning_second_email_text');
        $setting->setValue('[E-mail] Перед завершением второго этапа скидки для регистрации в ГИБДД {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('birthday_greeting_title');
        $setting->setValue('[E-mail] Письмо поздравления с днем рождения {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('birthday_greeting_text');
        $setting->setValue('[E-mail] Письмо поздравления с днем рождения {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        for ($i = 1; $i <= 5; $i++) {
            $setting = new Setting();
            $setting->setKey('sign_'.$i);
            $setting->setValue('Текст подписи №'.$i);
            $setting->setType('string');
            $manager->persist($setting);
        }

        $setting = new Setting();
        $setting->setKey('lock_user_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('lock_user_title');
        $setting->setValue('[Email] Письма о блокировке {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('lock_user_text');
        $setting->setValue('[Email] Письма о блокировке {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('unlock_user_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('unlock_user_title');
        $setting->setValue('[Email] Письма о разблокировке {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('unlock_user_text');
        $setting->setValue('[Email] Письма о разблокировке {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('error_account_locked');
        $setting->setValue('Ошибки {Для заблокированного пользователя}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('info_attempts_title');
        $setting->setValue('Уведомления о прохождении экзаменов и покупке попыток {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('info_attempts_text');
        $setting->setValue('Уведомления о прохождении экзаменов и покупке попыток {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('attempts_buy_title');
        $setting->setValue('Уведомление об отсутствии попыток и их покупке {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('attempts_buy_text');
        $setting->setValue('Уведомление об отсутствии попыток и их покупке {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('second_attempt_title');
        $setting->setValue('Уведомление о наличии повторных попыток {Заголовок}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('second_attempt_text');
        $setting->setValue('Уведомление о наличии повторных попыток {Текст}');
        $setting->setType('string');
        $manager->persist($setting);
        
        $setting = new Setting();
        $setting->setKey('contact_phone');
        $setting->setValue('0000000');
        $setting->setType('string');
        $manager->persist($setting);
        
        $setting = new Setting();
        $setting->setKey('social_facebook');
        $setting->setValue('me');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('activation_add_user_title');
        $setting->setValue('Тема письма активации пользователя');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('activation_add_user_text');
        $setting->setValue(
            'Текст письма активации пользователя'.PHP_EOL
            .'email: {{ email }}'.PHP_EOL
            .'password: {{ password }}'.PHP_EOL
            .'link_confirm: {{ link_confirm }}'.PHP_EOL
        );
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('api_contract_sign');
        $setting->setValue('[Popup] Вы не подписали договор {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('api_med_con');
        $setting->setValue('[Popup] Вы не оформили мед справку и не подписали договор {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('api_med_form');
        $setting->setValue('[Popup] Вы не оформили мед справку {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('unpaid_driving_email_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('unpaid_driving_email_title');
        $setting->setValue('[E-Mail] Тема письма о том, что нужно оплатить вождение {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('unpaid_driving_email_text');
        $setting->setValue('[E-Mail] Текст письма о том, что нужно оплатить вождение {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('unpaid_driving_sms_enabled');
        $setting->setValue(true);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('unpaid_driving_sms_text');
        $setting->setValue('[SMS] Текст СМС о том, что нужно оплатить вождение {Текст}');
        $setting->setType('string');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('ticket_test_old_style');
        $setting->setValue(false);
        $setting->setType('boolean');
        $manager->persist($setting);

        $setting = new Setting();
        $setting->setKey('confirmation_code_time_to_resend_text');
        $setting->setValue('Повторная отправка кода возможна через {{ timer_resending }}');
        $setting->setType('string');
        $manager->persist($setting);

        $manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }
}
