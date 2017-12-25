<?php

namespace My\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use My\AppBundle\Entity\CategoryPrice;
use My\AppBundle\Entity\Notify as NotifyEntity;
use My\AppBundle\Entity\PromoKey;
use My\AppBundle\Entity\ServicePrice;
use My\AppBundle\Entity\Subject;
use My\AppBundle\Entity\User;
use My\SmsUslugiRuBundle\Service\SmsUslugiRu;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Routing\RouterInterface;

class Notify
{
    protected $em;
    protected $mailer;
    protected $router;
    protected $templating;
    protected $sender;
    protected $settings;
    protected $settingsNotifies;
    protected $codeSymbols = '2456789QWRYUSDFGJLZVN';
    /** @var \My\SmsUslugiRuBundle\Service\SmsUslugiRu */
    protected $smsUslugi;

    public function __construct(
        EntityManager $em,
        \Swift_Mailer $mailer,
        RouterInterface $router,
        EngineInterface $tempating,
        SmsUslugiRu $smsUslugiRu,
        $senderName,
        $senderEmail
    ) {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->router = $router;
        $this->templating = $tempating;
        $this->smsUslugi = $smsUslugiRu;
        $this->sender = array($senderEmail => $senderName);
    }

    public function sendEmail($to, $subject, $message, $type = 'text/html', $mailing = false, $link = false)
    {
        if ($to instanceof User) {
            $to_email = $to->getEmail();
        } elseif (is_array($to)) {
            $to_email = $to['email'];
        } else {
            $to_email = $to;
        }

        $subject = $this->commonPlaceholders($to, $subject);
        $message = $this->commonPlaceholders($to, $message);

        if ($mailing) {
            $message = $this->templating->render("AppBundle::_mailing.html.twig", array(
                'message' => $message,
                'title' => $subject,
                'unsubscribe_link' => $link
            ));
        } else {
            $message = $this->templating->render("AppBundle::_email.html.twig", array(
                'message' => $message,
                'title' => $subject,
            ));
        }

        /** @var $email \Swift_Mime_Message */
        $email = \Swift_Message::newInstance()
            ->setFrom($this->sender)
            ->setTo($to_email)
            ->setSubject($subject)
            ->setBody($message, $type)
        ;
        $this->mailer->send($email);
    }

    public function sendNotify(User $to, $subject, $message, $isRequired = false)
    {
        $notify = new NotifyEntity();
        $notify->setTitle($subject);
        $notify->setText($message);
        $notify->setUser($to);
        $this->em->persist($notify);

        if ($isRequired) {
            $to->setRequiredNotify($notify);
        }
        $to->setNotifiesCnt($to->getNotifiesCnt() + 1);

        $this->em->persist($to);
        $this->em->flush();
    }

    public function send(User $to, $subject, $message, $isRequired = false)
    {
        $this->sendEmail($to, $subject, $message);
        $this->sendNotify($to, $subject, $message, $isRequired);
    }

    public function sendHurryEmail(User $to, PromoKey $key, $sum)
    {
        $subject  = $this->getSettingNotify('hurry_email_title');
        $message  = $this->getSettingNotify('hurry_email_text');
        $placeholders = array();
        if ($key->getPromo()) {
            $placeholders['{{ promo_expiration }}'] = $key->getPromo()->getUsedTo()->format('d.m.Y');
        } elseif ($key->getValidTo()) {
            $placeholders['{{ promo_expiration }}'] = $key->getValidTo()->format('d.m.Y');
        } else {
            $placeholders['{{ promo_expiration }}'] = '';
        }
        $placeholders['{{ promo_key }}'] = $key->getHash();
        $placeholders['{{ discount }}'] = $key->getDiscount();
        $placeholders['{{ price }}'] = $sum;
        $placeholders['{{ new_price }}'] = max($sum - $key->getDiscount(), 0);

        $message = str_replace(array_keys($placeholders), array_values($placeholders), $message);
        $this->sendEmail($to, $subject, $message);
    }

    public function sendConfirmationRegistration(User $to)
    {
        if ($this->getSettingNotify('confirmation_registration_enabled')) {
            $subject  = $this->getSettingNotify('confirmation_registration_title');
            $message  = $this->getSettingNotify('confirmation_registration_text');
            $link = $this->router->generate('fos_user_registration_confirm', array(
                'token' => $to->getConfirmationToken(),
            ), true);
            $message = str_replace('{{ link_confirm }}', $link, $message);
            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendResettingPassword(User $to)
    {
        if ($this->getSettingNotify('resetting_password_enabled')) {
            $subject  = $this->getSettingNotify('resetting_password_title');
            $message  = $this->getSettingNotify('resetting_password_text');
//            $message .= PHP_EOL.$this->router->generate('fos_user_resetting_reset', array(
            $resetLink = $this->router->generate('fos_user_resetting_reset', array(
                'token' => $to->getConfirmationToken(),
            ), true);
            $message = str_replace('{{ link_reset }}', $resetLink, $message);
            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendAfterConfirmMobile(User $to)
    {
        if ($this->getSettingNotify('after_confirm_mobile_enabled')) {
            $subject = $this->getSettingNotify('after_confirm_mobile_title');
            $message = $this->getSettingNotify('after_confirm_mobile_text');
            $btn = trim($this->templating->render('AppBundle::_btn_second_payment.html.twig'));
            $message = str_replace('{{ btn_second_payment }}', $btn, $message);
            $this->sendNotify($to, $subject, $message);
        }

        if ($this->getSettingNotify('after_confirm_mobile_email_enabled')) {
            $subject = $this->getSettingNotify('after_confirm_mobile_email_title');
            $message = $this->getSettingNotify('after_confirm_mobile_email_text');
            $btn = trim($this->templating->render('AppBundle::_link_second_payment.html.twig'));
            $message = str_replace('{{ link_second_payment }}', $btn, $message);
            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendAfterFirstPayment(User $to, $modifier = '')
    {
        if ($modifier) {
            $modifier .= '_';
        }
        if ($this->getSettingNotify('after_1_'.$modifier.'payment_enabled')) {
            $subject = $this->getSettingNotify('after_1_'.$modifier.'payment_title');
            $message = $this->getSettingNotify('after_1_'.$modifier.'payment_text');
            $btn = trim($this->templating->render('AppBundle::_btn_trainings.html.twig'));
            $message = str_replace('{{ btn_trainings }}', $btn, $message);
            $btn = trim($this->templating->render('AppBundle::_btn_profile_edit.html.twig'));
            $message = str_replace('{{ btn_profile_edit }}', $btn, $message);
            $this->sendNotify($to, $subject, $message, true);
        }

        if ($this->getSettingNotify('after_1_'.$modifier.'payment_email_enabled')) {
            $subject = $this->getSettingNotify('after_1_'.$modifier.'payment_email_title');
            $message = $this->getSettingNotify('after_1_'.$modifier.'payment_email_text');
            $this->sendEmail($to, $subject, $message);
        }

        if ($to->isDiscount2FirstEnabled()) {
            $region = $to->getRegion();
            $date = new \DateTime('today');
            $date->add(new \DateInterval('P'.($region->getDiscount2FirstDays()+1).'D'));
            $date->sub(new \DateInterval('P1D'));
            $end_time = $date->format('d.m.Y 23:59:59');
            $discount = $region->getDiscount2FirstAmount();
            $price = 0;
            foreach ($region->getServicesPrices() as $service_price) {
                /** @var $service_price ServicePrice */

                if ($service_price->getActive() && $service_price->getService()->getType() == 'training') {
                    $price += $service_price->getPrice();
                }
            }
            $new_price = max($price - $discount, 0);

            $subject = $this->getSettingNotify('discount_2_notify_first_email_title');
            $message = $this->getSettingNotify('discount_2_notify_first_email_text');
            $message = str_replace('{{ end_time }}', $end_time, $message);
            $message = str_replace('{{ discount }}', $discount, $message);
            $message = str_replace('{{ price }}', $price, $message);
            $message = str_replace('{{ new_price }}', $new_price, $message);
            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendAfterSecondPayment(User $to, $modifier = '')
    {
        if ($modifier) {
            $modifier .= '_';
        }
        if ($this->getSettingNotify('after_2_'.$modifier.'payment_enabled')) {
            $subject = $this->getSettingNotify('after_2_'.$modifier.'payment_title');
            $message = $this->getSettingNotify('after_2_'.$modifier.'payment_text');
            $btn = trim($this->templating->render('AppBundle::_btn_trainings.html.twig'));
            $message = str_replace('{{ btn_trainings }}', $btn, $message);
            $this->sendNotify($to, $subject, $message, true);
        }

        if ($this->getSettingNotify('after_2_'.$modifier.'payment_email_enabled')) {
            $subject = $this->getSettingNotify('after_2_'.$modifier.'payment_email_title');
            $message = $this->getSettingNotify('after_2_'.$modifier.'payment_email_text');
            $btn = trim($this->templating->render('AppBundle::_link_trainings.html.twig'));
            $message = str_replace('{{ link_trainings }}', $btn, $message);
            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendAfterPayment(User $to, $modifier = '')
    {
        if ($modifier) {
            $modifier .= '_';
        }
        if ($this->getSettingNotify('after_'.$modifier.'payment_enabled')) {
            $subject = $this->getSettingNotify('after_'.$modifier.'payment_title');
            $message = $this->getSettingNotify('after_'.$modifier.'payment_text');
            $this->sendNotify($to, $subject, $message);
        }

        if ($this->getSettingNotify('after_'.$modifier.'payment_email_enabled')) {
            $subject = $this->getSettingNotify('after_'.$modifier.'payment_email_title');
            $message = $this->getSettingNotify('after_'.$modifier.'payment_email_text');
            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendAfterAllSlices(User $to, Subject $oSubject)
    {
        if ($this->getSettingNotify('after_all_slices_'.$oSubject->getId().'_enabled')) {
            $subject = $this->getSettingNotify('after_all_slices_'.$oSubject->getId().'_title');
            $message = $this->getSettingNotify('after_all_slices_'.$oSubject->getId().'_text');
            $this->sendNotify($to, $subject, $message);
        }

        if ($this->getSettingNotify('after_all_slices_'.$oSubject->getId().'_email_enabled')) {
            $subject = $this->getSettingNotify('after_all_slices_'.$oSubject->getId().'_email_title');
            $message = $this->getSettingNotify('after_all_slices_'.$oSubject->getId().'_email_text');
            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendAfterExam(User $to, Subject $oSubject)
    {
        if ($this->getSettingNotify('after_exam_'.$oSubject->getId().'_enabled')) {
            $subject = $this->getSettingNotify('after_exam_'.$oSubject->getId().'_title');
            $message = $this->getSettingNotify('after_exam_'.$oSubject->getId().'_text');
            $this->sendNotify($to, $subject, $message);
        }

        if ($this->getSettingNotify('after_exam_'.$oSubject->getId().'_email_enabled')) {
            $subject = $this->getSettingNotify('after_exam_'.$oSubject->getId().'_email_title');
            $message = $this->getSettingNotify('after_exam_'.$oSubject->getId().'_email_text');
            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendAfterFailExam(User $to, Subject $oSubject)
    {
        if ($this->getSettingNotify('after_fail_exam_'.$oSubject->getId().'_enabled')) {
            $subject = $this->getSettingNotify('after_fail_exam_'.$oSubject->getId().'_title');
            $message = $this->getSettingNotify('after_fail_exam_'.$oSubject->getId().'_text');
            $this->sendNotify($to, $subject, $message);
        }

        if ($this->getSettingNotify('after_fail_exam_'.$oSubject->getId().'_email_enabled')) {
            $subject = $this->getSettingNotify('after_fail_exam_'.$oSubject->getId().'_email_title');
            $message = $this->getSettingNotify('after_fail_exam_'.$oSubject->getId().'_email_text');
            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendAfterAllExams(User $to)
    {
        if ($this->getSettingNotify('after_all_exams_enabled')) {
            $subject = $this->getSettingNotify('after_all_exams_title');
            $message = $this->getSettingNotify('after_all_exams_text');

            $settings_attempts_days_of_retake = $this->em->getRepository('AppBundle:Setting')->findOneBy([
                '_key' => 'attempts_days_of_retake'
            ]);

            $placeholders = [];
            if (!empty($settings_attempts_days_of_retake->getValue())) {
                $placeholders['{{ days }}'] = $settings_attempts_days_of_retake->getValue();
            }

            $message = str_replace(array_keys($placeholders), array_values($placeholders), $message);
            $this->sendNotify($to, $subject, $message);
        }

        if ($this->getSettingNotify('after_all_exams_email_enabled')) {
            $subject = $this->getSettingNotify('after_all_exams_email_title');
            $message = $this->getSettingNotify('after_all_exams_email_text');

            $settings_attempts_days_of_retake = $this->em->getRepository('AppBundle:Setting')->findOneBy([
                '_key' => 'attempts_days_of_retake'
            ]);

            $placeholders = [];
            if (!empty($settings_attempts_days_of_retake->getValue())) {
                $placeholders['{{ days }}'] = $settings_attempts_days_of_retake->getValue();
            }

            $message = str_replace(array_keys($placeholders), array_values($placeholders), $message);
            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendAfterFinalExam(User $to)
    {
        if ($this->getSettingNotify('after_final_exam_enabled')) {
            $subject = $this->getSettingNotify('after_final_exam_title');
            $message = $this->getSettingNotify('after_final_exam_text');
            $this->sendNotify($to, $subject, $message);
        }

        if ($this->getSettingNotify('after_final_exam_email_enabled')) {
            $subject = $this->getSettingNotify('after_final_exam_email_title');
            $message = $this->getSettingNotify('after_final_exam_email_text');
            $link = trim($this->templating->render('AppBundle::_link_certificate.html.twig', array('user' => $to)));
            $message = str_replace('{{ link_certificate }}', $link, $message);
            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendNoPayments(User $to, Promo $promoService)
    {
        if ($this->getSettingNotify('no_payments_enabled')) {
            $now = new \DateTime();

            $region_discount = 0;
            $today = new \DateTime('today');
            $region = $to->getRegion();
            if ($region->getDiscount1Amount() > 0) {
                if (($region->getDiscount1DateFrom() <= $today && $today <= $region->getDiscount1DateTo())
                    || $region->getDiscount1TimerPeriod() > 0
                ) {
                    $region_discount = $region->getDiscount1Amount();
                }
            }

            // Guess what number of letter it is
            for ($i = 1; $i <= 5; $i++) {
                $date = clone $to->getCreatedAt();
                $date->add(new \DateInterval('P'.$this->getSetting('notify_no_payments_'.$i).'D'));
                if ($now->format('d-m-Y') === $date->format('d-m-Y')) {
                    $hash = current($promoService->generatePromoKeyHashes(1));

                    $validTo = clone $now;
                    $days = $this->getSetting('notify_no_payments_promo_expiration_'.$i);
                    $validTo = $validTo->add(new \DateInterval('P'.$days.'D'));

                    $key = new PromoKey;
                    $key->setActive(true);
                    $key->setDiscount($this->getSetting('notify_no_payments_promo_discount_'.$i));
                    $key->setHash($hash);
                    $key->setOverdueLetterNum($i);
                    $key->setPromo(null);
                    $key->setSource('auto_overdue');
                    $key->setType('site_access');
                    $key->setValidTo($validTo);
                    $this->em->persist($key);
                    $this->em->flush();

                    $price = 0;
                    // Take a look only on categories prices - no obsolete services with site_access
                    $categories_prices = $this->em->getRepository('AppBundle:CategoryPrice')->createQueryBuilder('cp')
                        ->andWhere('cp.active = :active')->setParameter(':active', true)
                        ->andWhere('cp.region = :region')->setParameter(':region', $to->getRegion())
                        ->andWhere('cp.category = :category')->setParameter(':category', $to->getCategory())
                        ->getQuery()->execute();
                    foreach ($categories_prices as $categories_price) {
                        /** @var $categories_price CategoryPrice */

                        $price += $categories_price->getPrice();
                    }

                    $newPrice = max($price - $key->getDiscount(), 0);
                    $regionNewPrice = max($newPrice - $region_discount, 0);

                    $placeholders = array();
                    $placeholders['{{ discount }}'] = $key->getDiscount();
                    $placeholders['{{ promo_key }}'] = $hash;
                    $placeholders['{{ promo_expiration }}'] = $key->getValidTo()->format('d.m.Y');
                    $placeholders['{{ price }}'] = $price;
                    $placeholders['{{ new_price }}'] = $newPrice;
                    $placeholders['{{ region_new_price }}'] = $regionNewPrice;
                    $link = $this->router->generate('my_unsubscribe_overdue', array('email'=>$to->getEmail()), true);
                    $placeholders['{{ unsubscribe_link }}'] = $link;

                    $subject = $this->getSettingNotify('no_payments_title');
                    $subject = str_replace(array_keys($placeholders), array_values($placeholders), $subject);
                    $message = $this->getSettingNotify('no_payments_text');
                    $message = str_replace(array_keys($placeholders), array_values($placeholders), $message);
                    $this->sendEmail($to, $subject, $message, 'text/html');

                    break;
                }
            }
        }
    }

    public function sendBeforeAccessTimeEndAfter1Payment(User $to)
    {
        if ($this->getSettingNotify('before_access_time_end_after_1_payment_enabled')) {
            $subject = $this->getSettingNotify('before_access_time_end_after_1_payment_title');
            $message = $this->getSettingNotify('before_access_time_end_after_1_payment_text');
            $this->sendNotify($to, $subject, $message);
        }

        if ($this->getSettingNotify('before_access_time_end_after_1_payment_email_enabled')) {
            $subject = $this->getSettingNotify('before_access_time_end_after_1_payment_email_title');
            $message = $this->getSettingNotify('before_access_time_end_after_1_payment_email_text');
            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendAfterAccessTimeEndAfter1Payment(User $to)
    {
        if ($this->getSettingNotify('after_access_time_end_after_1_payment_enabled')) {
            $subject  = $this->getSettingNotify('after_access_time_end_after_1_payment_title');
            $message  = $this->getSettingNotify('after_access_time_end_after_1_payment_text');
            $message .= PHP_EOL.$this->router->generate('my_unsubscribe_payment_1', array(
                'email' => $to->getEmail(),
            ), true);
            $this->sendEmail($to, $subject, $message, 'text/html');
        }
    }

    public function sendBeforeAccessTimeEndAfter2Payment(User $to)
    {
        if ($this->getSettingNotify('before_access_time_end_after_2_payment_enabled')) {
            $subject = $this->getSettingNotify('before_access_time_end_after_2_payment_title');
            $message = $this->getSettingNotify('before_access_time_end_after_2_payment_text');
            $this->sendNotify($to, $subject, $message);
        }

        if (!$to->getUnsubscribedX()
            && $this->getSettingNotify('before_access_time_end_after_2_payment_email_enabled')
        ) {
            $subject = $this->getSettingNotify('before_access_time_end_after_2_payment_email_title');
            $message = $this->getSettingNotify('before_access_time_end_after_2_payment_email_text');
            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendAfterAccessTimeEndAfter2Payment(User $to)
    {
        if (!$to->getUnsubscribedX()
            && $this->getSettingNotify('after_access_time_end_after_2_payment_enabled')
        ) {
            $subject  = $this->getSettingNotify('after_access_time_end_after_2_payment_title');
            $message  = $this->getSettingNotify('after_access_time_end_after_2_payment_text');
            $message .= PHP_EOL.$this->router->generate('my_unsubscribe_payment_2', array(
                'email' => $to->getEmail(),
            ), true);
            $this->sendEmail($to, $subject, $message, 'text/html');
        }
    }

    public function sendSupportAnswered(User $to)
    {
        if ($this->getSettingNotify('support_answered_email_enabled')) {
            $subject  = $this->getSettingNotify('support_answered_email_title');
            $message  = $this->getSettingNotify('support_answered_email_text');
            $this->sendEmail($to, $subject, $message, 'text/html');
        }
    }

    public function sendMailing($to, $subject, $message)
    {
        $message = str_replace('{{ username }}', $to['first_name'].' '.$to['last_name'], $message);
        $link = trim($this->templating->render('AppBundle::_link_unsubscribe_mailing.html.twig', array('user' => $to)));
        $message = str_replace('{{ unsubscribe_link }}', $link, $message);
        $this->sendEmail($to, $subject, $message, 'text/html', true, $link);
    }

    public function sendCongratulationBirthday($to)
    {
        if (!$to['unsubscribed_x']) {
            $subject = $this->getSettingNotify('birthday_greeting_title');
            $message = $this->getSettingNotify('birthday_greeting_text');

            $promo = '';
            for ($i = 0; $i < 10; $i ++) {
                $promo .= $this->codeSymbols[rand(0, strlen($this->codeSymbols)-1)];
            }

            $date = new \DateTime();
            $date->modify('+1 month');
            $expiryDate = $date->format('j / n / Y');

            $placeholders = array(
                '{{ promo }}' => $promo,
                '{{ date }}'  => $expiryDate,
            );

            $message = str_replace(array_keys($placeholders), array_values($placeholders), $message);

            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendTicketsIsSended($to)
    {
        if ($to) {
            $subject = $this->getSettingNotify('tickets_is_sended_title');
            $message = $this->getSettingNotify('tickets_is_sended_text');

            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendDocIsDone($to)
    {
        if ($to) {
            $subject = $this->getSettingNotify('doc_is_done_title');
            $message = $this->getSettingNotify('doc_is_done_text');

            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendDocsIsConfirm($to)
    {
        if ($to) {
            $subject = $this->getSettingNotify('doc_is_confirm_title');
            $message = $this->getSettingNotify('doc_is_confirm_text');

            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendDocsIsFail($to)
    {
        if ($to) {
            $subject = $this->getSettingNotify('doc_is_fail_title');
            $message = $this->getSettingNotify('doc_is_fail_text');

            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendPrimaryBoostingNotPaidDriving(User $to)
    {
        if ($this->getSettingNotify('primary_boosting_not_paid_driving_email_enabled')) {
            $subject = $this->getSettingNotify('primary_boosting_not_paid_driving_email_title');
            $message = $this->getSettingNotify('primary_boosting_not_paid_driving_email_text');

            $lastSatge = $this->em->getRepository('AppBundle:OweStage')
                ->findOneBy(['user' => $to->getId()], ['end' => 'DESC']);

            $placeholders = [];
            $placeholders['{{ paid_to }}'] = $lastSatge->getEnd()->format('d-m-Y');
            $placeholders['{{ sum_office }}'] = $this->getSettingNotify('cost_driving_payment_in_office');
            $placeholders['{{ sum_online }}'] = $lastSatge->getSum();
            $placeholders['{{ last_name }}'] = $to->getLastName();
            $placeholders['{{ first_name }}'] = $to->getFirstName();
            $placeholders['{{ patronymic }}'] = $to->getPatronymic();
            for ($i = 1; $i <= 5; $i++) {
                $placeholders['{{ sign_'.$i.' }}'] = $this->getSettingNotify('sign_'.$i);
            }

            $message = str_replace(array_keys($placeholders), array_values($placeholders), $message);
            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendUserNotPaidPrimaryBoostingNotPaidDriving(User $to)
    {
        if ($this->getSettingNotify('user_not_paid_primary_boosting_not_paid_driving_email_enabled')) {
            $subject = $this->getSettingNotify('user_not_paid_primary_boosting_not_paid_driving_email_title');
            $message = $this->getSettingNotify('user_not_paid_primary_boosting_not_paid_driving_email_text');

            $lastSatge = $this->em->getRepository('AppBundle:OweStage')
                ->findOneBy(['user' => $to->getId()], ['end' => 'DESC']);

            $placeholders = [];
            $placeholders['{{ sum_office }}'] = $this->getSettingNotify('cost_driving_payment_in_office');
            $placeholders['{{ sum_online }}'] = $lastSatge->getSum();
            $placeholders['{{ last_name }}'] = $to->getLastName();
            $placeholders['{{ first_name }}'] = $to->getFirstName();
            $placeholders['{{ patronymic }}'] = $to->getPatronymic();
            for ($i = 1; $i <= 5; $i++) {
                $placeholders['{{ sign_'.$i.' }}'] = $this->getSettingNotify('sign_'.$i);
            }

            $message = str_replace(array_keys($placeholders), array_values($placeholders), $message);
            $this->sendEmail($to, $subject, $message);
        }
    }

    public function sendUnpaidDriving(User $to)
    {
        $emailEnabled = $this->getSetting('unpaid_driving_email_enabled');
        if ($emailEnabled) {
            $emailSubject = $this->getSetting('unpaid_driving_email_title');
            $emailMessage = $this->getSetting('unpaid_driving_email_text');

            $this->sendEmail($to, $emailSubject, $emailMessage);
        }

        $smsEnabled = $this->getSetting('unpaid_driving_sms_enabled');
        if ($smsEnabled) {
            $phoneNumber = $to->getPhoneMobile();
            $smsMessage  = $this->getSetting('unpaid_driving_sms_text');
            $smsMessage  = $this->commonPlaceholders($to, $smsMessage);

            $this->smsUslugi->query('+7'.$phoneNumber, $smsMessage);
        }
    }

    protected function getSettingsNotifies()
    {
        if (!$this->settingsNotifies) {
            $this->settingsNotifies = $this->em->getRepository('AppBundle:Setting')->getAllData();
        }
        return $this->settingsNotifies;
    }

    protected function getSettingNotify($name)
    {
        $settings = $this->getSettingsNotifies();
        return isset($settings[$name]) ? $settings[$name] : '';
    }

    protected function getSettings()
    {
        if (!$this->settings) {
            $this->settings = $this->em->getRepository('AppBundle:Setting')->getAllData();
        }
        return $this->settings;
    }

    protected function getSetting($name)
    {
        $settings = $this->getSettings();
        return isset($settings[$name]) ? $settings[$name] : '';
    }

    protected function commonPlaceholders($to, $text)
    {
        $placeholders = [];
        if ($to instanceof User) {
            $placeholders['{{ last_name }}']  = $to->getLastName();
            $placeholders['{{ first_name }}'] = $to->getFirstName();
            $placeholders['{{ patronymic }}'] = $to->getPatronymic();

            if ($to->getSex()) {
                $placeholders['{{ dear }}'] = ($to->getSex() == 'female' ? 'Уважаемая' : 'Уважаемый');
            } else {
                $placeholders['{{ dear }}'] = 'Уважаемый/ая';
            }
        } elseif (is_array($to)) {
            $placeholders['{{ last_name }}']  = isset($to['last_name']) ? $to['last_name'] : '';
            $placeholders['{{ first_name }}'] = isset($to['first_name']) ? $to['first_name'] : '';
            $placeholders['{{ patronymic }}'] = isset($to['patronymic']) ? $to['patronymic'] : '';

            if (isset($to['sex'])) {
                $placeholders['{{ dear }}'] = ($to['sex'] == 'female' ? 'Уважаемая' : 'Уважаемый');
            } else {
                $placeholders['{{ dear }}'] = 'Уважаемый/ая';
            }
        } else {
            $placeholders['{{ dear }}'] = 'Уважаемый/ая';
        }

        for ($i = 1; $i <= 5; $i ++) {
            $placeholders['{{ sign_'.$i.' }}'] = $this->getSetting('sign_'.$i);
        }

        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }
}
