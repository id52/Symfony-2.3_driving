<?php

namespace My\AppBundle\Controller;

use Doctrine\ORM\Query;
use My\AppBundle\Entity\ServicePrice;
use My\AppBundle\Exception\AppResponseException;
use My\AppBundle\Util\Time;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\LockedException;

abstract class MyAbstract extends Controller
{
    /** @var $em \Doctrine\ORM\EntityManager */
    public $em;
    /** @var $user \My\AppBundle\Entity\User */
    public $user;

    public $settings = array();
    public $settingsNotifies = array();

    public function init()
    {
        $cntxt = $this->get('security.context');
        $twig = $this->container->get('twig');
        $request = $this->container->get('request');

        if ($this->user->getByApi()
            && !$this->user->getApiQuestionLog()
            && !$cntxt->isGranted('ROLE_USER_PAID2')
            && $request->attributes->get('_controller') != 'AppBundle:My:apiQuestions'
        ) {
            throw new AppResponseException($this->forward('AppBundle:My:apiQuestions', array('request' => $request)));
        }

        $now = new \DateTime();

        if ($this->user->isLocked()) {
            throw new LockedException();
        }

        if (!$request->isXmlHttpRequest()
            && !$this->user->getDrivingPaidAt()
            && (!is_null($this->user->getOweStageEnd()) && $this->user->getOweStageEnd() > $now)
            && !$this->user->getPaidPrimaryBoostingNotify()
        ) {
            $title          = $this->settingsNotifies['primary_boosting_not_paid_driving_popup_title'];
            $body           = $this->settingsNotifies['primary_boosting_not_paid_driving_popup_text'];
            $countdown      = $now->diff($this->user->getOweStageEnd(), true);
            $officePaidCost = $this->settingsNotifies['cost_driving_payment_in_office'];
            $lastOweStage   = $this->em->getRepository('AppBundle:OweStage')
                ->findOneBy(['user' => $this->user->getId()], ['end' => 'DESC']);

            $placeholders['{{ countdown }}']  = '<span class="primary_boosting_countdown"'
                .'data-seconds-left="'.Time::getAllSeconds($countdown).'"></span>';
            $placeholders['{{ paid_to }}']    = $this->user->getOweStageEnd()->format('d-m-Y');
            $placeholders['{{ sum_office }}'] = $officePaidCost;
            $placeholders['{{ sum_online }}'] = $lastOweStage->getSum();
            $placeholders['{{ last_name }}']  = $this->user->getLastName();
            $placeholders['{{ first_name }}'] = $this->user->getFirstName();
            $placeholders['{{ patronymic }}'] = $this->user->getPatronymic();

            $body = str_replace(array_keys($placeholders), array_values($placeholders), $body);

            $twig->addGlobal('primary_boosting', true);
            $twig->addGlobal('primary_boosting_title', $title);
            $twig->addGlobal('primary_boosting_body', $body);

            $this->user->setPaidPrimaryBoostingNotify(true);
            $this->em->persist($this->user);
            $this->em->flush();
        }

        if (!$request->isXmlHttpRequest()
            && !$this->user->getDrivingPaidAt()
            && (!is_null($this->user->getOweStageEnd()) && $this->user->getOweStageEnd() < new \DateTime())
            && !$this->user->getNotPaidPrimaryBoostingNotify()
        ) {
            $title = $this->settingsNotifies['user_not_paid_primary_boosting_not_paid_driving_popup_title'];
            $body = $this->settingsNotifies['user_not_paid_primary_boosting_not_paid_driving_popup_text'];
            $officePaidCost = $this->settingsNotifies['cost_driving_payment_in_office'];
            $lastOweStage = $this->em->getRepository('AppBundle:OweStage')
                ->findOneBy(['user' => $this->user->getId()], ['end' => 'DESC']);

            $placeholders['{{ sum_office }}'] = $officePaidCost;
            $placeholders['{{ sum_online }}'] = $lastOweStage->getSum();
            $placeholders['{{ last_name }}'] = $this->user->getLastName();
            $placeholders['{{ first_name }}'] = $this->user->getFirstName();
            $placeholders['{{ patronymic }}'] = $this->user->getPatronymic();

            $body = str_replace(array_keys($placeholders), array_values($placeholders), $body);

            $twig->addGlobal('user_not_paid_primary_boosting', true);
            $twig->addGlobal('user_not_paid_primary_boosting_title', $title);
            $twig->addGlobal('user_not_paid_primary_boosting_body', $body);

            $this->user->setNotPaidPrimaryBoostingNotify(true);
            $this->em->persist($this->user);
            $this->em->flush();
        }

        if (!$cntxt->isGranted('ROLE_MOD')) {
            if ($cntxt->isGranted('ROLE_USER_PAID2')) {
                if ($this->user->getPayment2Paid()) {
                    $limit = clone $this->user->getPayment2Paid();
                } else {
                    $limit = new \DateTime();
                }

                $limit->add(new \DateInterval('P'.$this->settings['access_time_after_2_payment'].'D'));

                if ($limit < $now) {
                    $this->user->setExpired(true);
                    $this->em->persist($this->user);
                    $this->em->flush();
                    throw new AccountExpiredException();
                }
            } else {
                $date = $this->user->getPayment1Paid();
                if ($date) {
                    $dueDate = clone $date;
                    $dueDate->add(new \DateInterval(
                        'P'.$this->settings['access_time_after_1_payment'].'D'
                    ));
                    $countdown = $date->diff($dueDate, true);

                    if ($now->format('Y-m-d') > $this->user->getPaidNotifiedAt()->format('Y-m-d') && $dueDate > $now) {
                        $this->user->setPaidNotifiedAt($now);
                        $this->em->persist($this->user);
                        $this->em->flush();

                        $twig->addGlobal('paid_notify', true);
                        $twig->addGlobal('paid_notify_title', $this->settingsNotifies['pay_now_title']);
                        $twig->addGlobal('paid_notify_body', str_replace(
                            '{{ timeleft }}',
                            '<span class="timeleft"></span>',
                            $this->settingsNotifies['pay_now_text']
                        ));
                        $twig->addGlobal('paid_notify_end_time_seconds', Time::getAllSeconds($countdown));
                    }
                }
            }
        }

        $notify = $this->user->getRequiredNotify();
        if ($notify) {
            $this->user->setRequiredNotify(null);
            $this->em->persist($this->user);
            $this->em->flush();

            if ($cntxt->isGranted('ROLE_USER_PAID2')) {
                throw new AppResponseException($this->redirect($this->generateUrl('my_profile_edit')));
            }

            throw new AppResponseException($this->redirect($this->generateUrl('my_notify_read', array(
                'id' => $notify->getId(),
            ))));
        }

        if ($this->user->isDiscount2FirstEnabled()) {
            $region   = $this->user->getRegion();
            $discount = $region->getDiscount2FirstAmount();
            $price    = 0;

            $date    = $this->user->getPayment1Paid();
            $dueDate = null;
            if ($date) {
                $dueDate = clone $date;
                $dueDate->add(new \DateInterval('P'.($region->getDiscount2FirstDays() + 1).'D'));
            }

            $countdown = $dueDate ? $date->diff($dueDate, true) : null;

            foreach ($region->getServicesPrices() as $service_price) { /** @var $service_price ServicePrice */
                if ($service_price->getActive() && $service_price->getService()->getType() == 'training') {
                    if ($this->user->getByApi()) {
                        $price += $service_price->getPriceForApi(
                            $this->user->getByApiComb(),
                            $this->user->getByApiExpr()
                        );
                    } else {
                        $price += $service_price->getPrice();
                    }
                }
            }
            $new_price = max($price - $discount, 0);

            $twig->addGlobal('discount_2_counter', 'first');
            $twig->addGlobal('discount_2_counter_discount', $discount);
            $twig->addGlobal('discount_2_counter_price', $price);
            $twig->addGlobal('discount_2_counter_new_price', $new_price);
            $twig->addGlobal('discount_2_counter_seconds_left', Time::getAllSeconds($countdown));

            if (!$this->user->getDiscount2NotifyFirst()) {
                $message = $this->settingsNotifies['discount_2_notify_first'];
                $message = str_replace(
                    '{{ countdown }}',
                    '<span class="discount_2_countdown" '
                        .'data-seconds-left="'.Time::getAllSeconds($countdown).'"></span>',
                    $message
                );
                $message = str_replace('{{ discount }}', $discount, $message);
                $message = str_replace('{{ price }}', $price, $message);
                $message = str_replace('{{ new_price }}', $new_price, $message);
                $twig->addGlobal('discount_2_notify_message', $message);
                $twig->addGlobal('discount_2_notify', 'first');
            }
        } elseif ($this->user->isDiscount2SecondEnabled()) {
            $region   = $this->user->getRegion();
            $discount = $region->getDiscount2SecondAmount();
            $price    = 0;

            $date    = $this->user->getPayment1Paid();
            $dueDate = null;
            if ($date) {
                $dueDate = clone $date;
                $dueDate->add(new \DateInterval(
                    'P'.($region->getDiscount2FirstDays()
                        + $region->getDiscount2BetweenPeriodDays()
                        + $region->getDiscount2SecondDays() + 1).'D'
                ));
            }

            $countdown = $dueDate ? $date->diff($dueDate, true) : null;

            foreach ($region->getServicesPrices() as $service_price) { /** @var $service_price ServicePrice */
                if ($service_price->getActive() && $service_price->getService()->getType() == 'training') {
                    $price += $service_price->getPrice();
                }
            }
            $new_price = max($price - $discount, 0);

            $twig->addGlobal('discount_2_counter', 'second');
            $twig->addGlobal('discount_2_counter_discount', $discount);
            $twig->addGlobal('discount_2_counter_price', $price);
            $twig->addGlobal('discount_2_counter_new_price', $new_price);
            $twig->addGlobal('discount_2_counter_seconds_left', Time::getAllSeconds($countdown));

            if (!$this->user->getDiscount2NotifySecond()) {
                $message = $this->settingsNotifies['discount_2_notify_second'];
                $message = str_replace(
                    '{{ countdown }}',
                    '<span class="discount_2_countdown" '
                    .'data-seconds-left="'.Time::getAllSeconds($countdown).'"></span>',
                    $message
                );
                $message = str_replace('{{ discount }}', $discount, $message);
                $message = str_replace('{{ price }}', $price, $message);
                $message = str_replace('{{ new_price }}', $new_price, $message);
                $twig->addGlobal('discount_2_notify_message', $message);
                $twig->addGlobal('discount_2_notify', 'second');
            }
        }

        if ($this->user->getOffline()
            && !$cntxt->isGranted('ROLE_USER_FULL_PROFILE')
            && $this->getRequest()->get('_route') != 'my_profile_edit'
            && !$this->getRequest()->isXmlHttpRequest()
        ) {
            throw new AppResponseException($this->redirect($this->generateUrl('my_profile_edit')));
        }

        if ($this->user->getPayment2Paid() && !$this->user->getPayment2PaidGoal()) {
            $this->user->setPayment2PaidGoal(true);
            $this->em->persist($this->user);
            $this->em->flush();
            $twig->addGlobal('payment2_goal', true);
        }

        $apiMedForm      = $this->user->getApiMedForm();
        $apiContractSign = $this->user->getApiContractSign();
        $lastNotify      = $this->user->getApiMedConNotifyDate();
        $today           = new \DateTime('today');

        $med_con_enabled = $this->settingsNotifies['medical_certificate_is_not_issued_and_the_agreement_is_not_signed'];

        if ($this->user->getByApi()
            && $this->user->hasRole('ROLE_USER_PAID2')
            && (!$apiMedForm || !$apiContractSign)
            && $today > $lastNotify
            && $med_con_enabled
        ) {
            $this->user->setApiMedConNotifyDate($today);
            $this->em->persist($this->user);
            $this->em->flush();

            $message = '';
            if (!$apiMedForm && !$apiContractSign) {
                $message = $this->settings['api_med_con'];
            } elseif (!$apiMedForm) {
                $message = $this->settings['api_med_form'];
            } elseif (!$apiContractSign) {
                $message = $this->settings['api_contract_sign'];
            }

            $twig->addGlobal('api_med_con_notify', true);
            $twig->addGlobal('api_med_con_notify_message', $message);
        }
    }

    protected function getErrorMessages(Form $form)
    {
        $errors = array();

        foreach ($form->getErrors() as $error) {
            $errors['errors'][] = $error->getMessage();
        }

        foreach ($form->all() as $child) {
            /** @var $child \Symfony\Component\Form\Form */

            if (!$child->isValid()) {
                $errors['children'][$child->getName()] = $this->getErrorMessages($child);
            }
        }

        return $errors;
    }

    protected function getUserStat()
    {
        $version = $this->em->getRepository('AppBundle:TrainingVersion')->createQueryBuilder('v')
            ->andWhere('v.category = :category')->setParameter(':category', $this->user->getCategory())
            ->andWhere('v.start_date <= :start_date')
            ->setParameter(':start_date', date_format($this->user->getCreatedAt(), 'Y-m-d'))
            ->addOrderBy('v.start_date', 'DESC')
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();
        if (!$version) {
            throw $this->createNotFoundException('Training version not found.');
        }

        $questions = $this->em->getRepository('AppBundle:Question')->createQueryBuilder('q')
            ->leftJoin('q.theme', 't')->addSelect('t')
            ->leftJoin('t.subject', 's')
            ->andWhere('q.is_pdd = :is_pdd')->setParameter(':is_pdd', true)
            ->leftJoin('q.versions', 'v')
            ->andWhere('v = :version')->setParameter(':version', $version)
            ->addOrderBy('t.position')
            ->getQuery()->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)->execute();

        $qids = array();
        $themes_nums = array();
        $num = 0;
        foreach ($questions as $question) { /** @var $question \My\AppBundle\Entity\Question */
            $theme_id = $question->getTheme()->getId();
            $qids[$question->getId()] = $theme_id;
            if (!isset($themes_nums[$theme_id])) {
                $themes_nums[$theme_id] = ++$num;
            }
        }

        $themes_stat = array();
        $all_stat = array();

        $logs = $this->em->getRepository('AppBundle:TestLog')->createQueryBuilder('tl')
            ->andWhere('tl.user = :user')->setParameter(':user', $this->user)
            ->addOrderBy('tl.started_at', 'DESC')
            ->getQuery()->getArrayResult();
        $this->processingLogs($logs, $qids, $themes_nums, $themes_stat, $all_stat);

        $logs = $this->em->getRepository('AppBundle:TestKnowledgeLog')->createQueryBuilder('tkl')
            ->andWhere('tkl.user = :user')->setParameter(':user', $this->user)
            ->addOrderBy('tkl.started_at', 'DESC')
            ->getQuery()->getArrayResult();
        $this->processingLogs($logs, $qids, $themes_nums, $themes_stat, $all_stat);

        foreach ($themes_stat as $key => $value) {
            $themes_stat[$key]['proc'] = round($value['correct'] / $value['all'] * 100);
        }

        uasort($themes_stat, function ($a, $b) {
            if ($a['proc'] == $b['proc']) {
                if ($a['all'] == $b['all']) {
                    return $a['num'] < $b['num'] ? -1 : 1;
                } else {
                    return $a['all'] > $b['all'] ? -1 : 1;
                }
            } else {
                return $a['proc'] > $b['proc'] ? -1 : 1;
            }
        });

        krsort($all_stat);

        return array(
            'themes' => $themes_stat,
            'all'    => $all_stat,
        );
    }

    private function processingLogs($logs, $qids, $themes_nums, &$themes_stat, &$all_stat)
    {
        foreach ($logs as $log) {
            /** @var $started_at \DateTime */
            $started_at = $log['started_at'];
            $started_at_key = $started_at->format('YmdHis');

            if (!isset($all_stat[$started_at_key])) {
                $all_stat[$started_at_key] = array(
                    'started_at' => $log['started_at'],
                    'ended_at'   => $log['ended_at'],
                    'passed'     => $log['passed'],
                    'themes'     => array(),
                );
            }

            foreach ($log['answers'] as $key => $answer) {
                if (is_array($answer) && isset($qids[$log['questions'][$key]])) {
                    $theme_id = $qids[$log['questions'][$key]];

                    if (!isset($themes_stat[$theme_id])) {
                        $themes_stat[$theme_id] = array(
                            'num'     => $themes_nums[$theme_id],
                            'correct' => 0,
                            'all'     => 0,
                        );
                    }

                    if (!isset($all_stat[$started_at_key]['themes'][$theme_id])) {
                        $all_stat[$started_at_key]['themes'][$theme_id] = array(
                            'correct' => 0,
                            'all'     => 0,
                        );
                    }

                    if ($answer['correct']) {
                        $themes_stat[$theme_id]['correct']++;
                        $all_stat[$started_at_key]['themes'][$theme_id]['correct']++;
                    }
                    $themes_stat[$theme_id]['all']++;
                    $all_stat[$started_at_key]['themes'][$theme_id]['all']++;
                }
            }

            foreach ($all_stat[$started_at_key]['themes'] as $key => $value) {
                $all_stat[$started_at_key]['themes'][$key]['proc'] = round($value['correct'] / $value['all'] * 100);
            }
        }
    }
}
