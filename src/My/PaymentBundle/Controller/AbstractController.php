<?php

namespace My\PaymentBundle\Controller;

use My\AppBundle\Controller\ApiController;
use My\AppBundle\Entity\ExamAttemptLog;
use My\AppBundle\Entity\User;
use My\AppBundle\Entity\UserStat;
use My\PaymentBundle\Entity\Log;
use My\PaymentBundle\Entity\RevertLog;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\Constraints\DateTime;

abstract class AbstractController extends Controller
{
    protected function afterSuccessPayment(Log $log)
    {
        /** @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $user = $log->getUser();
        $comments = json_decode($log->getComment(), true);

        $pay1IsPaid = false;
        $pay2IsPaid = false;

        $isDiscount2FirstEnabled  = false;
        $isDiscount2SecondEnabled = false;

        if (isset($comments['attemptsPackage'])) {
            $attemptPackage = $em->getRepository('AppBundle:AttemptsPackage')->find($comments['attemptsPackage']);
            if ($attemptPackage) {
                $user->setExamAttempts($user->getExamAttempts() + $attemptPackage->getNumberOfAttempts());

                $exam_attempt_log = new ExamAttemptLog();
                $exam_attempt_log->setUser($user);

                if (isset($comments['subject_id'])) {
                    $subject = $em->find('AppBundle:Subject', intval($comments['subject_id']));

                    if ($subject) {
                        $exam_attempt_log->setSubject($subject);
                    }
                }

                $exam_attempt_log->setAttemptsPackage($attemptPackage);
                $exam_attempt_log->setAmount($attemptPackage->getNumberOfAttempts());

                $em->persist($exam_attempt_log);
                $em->flush();
            }
        } elseif (isset($comments['owe_stage'])) {
            $lastOweStage = $em->getRepository('AppBundle:OweStage')
                ->findOneBy(['user' => $user->getId()], ['end' => 'DESC']);
            if ($lastOweStage) {
                $log->setOweStage($lastOweStage);
                $lastOweStage->setPaid(true);
                $lastOweStage->setLog($log);
                $user->setDrivingPaidAt(new \DateTime());

                $em->persist($log);
                $em->persist($lastOweStage);
                $em->persist($user);
                $em->flush();
            }
        } else {
            //add info about used discount on promo
            $user->setPromoUsed(true);
            /** @var $notify \My\AppBundle\Service\Notify */
            $notify = $this->get('app.notify');
            $promo = !empty($comments['key']) ? 'promo' : '';

            if (!$user->hasRole('ROLE_USER_PAID')) {
                $user->addRole('ROLE_USER_PAID');
                $user->setPayment1Paid(new \DateTime());
                $notify->sendAfterFirstPayment($user, $promo);
                $pay1IsPaid = true;
            } else {
                $all_services = array();
                $services = $em->getRepository('AppBundle:Service')->createQueryBuilder('s')
                    ->andWhere('s.type IS NOT NULL')
                    ->andWhere('s.type != :type')->setParameter(':type', 'site_access')
                    ->getQuery()->getArrayResult();
                foreach ($services as $service) {
                    $all_services[] = $service['id'];
                }

                $logs = $em->getRepository('PaymentBundle:Log')->createQueryBuilder('l')
                    ->andWhere('l.user = :user')->setParameter(':user', $user)
                    ->andWhere('l.paid = :paid')->setParameter(':paid', true)
                    ->getQuery()->getArrayResult();
                foreach ($logs as $c_log) {
                    $comment = json_decode($c_log['comment'], true);
                    if (!empty($comment['services'])) {
                        $ids = explode(',', $comment['services']);
                        foreach ($ids as $id) {
                            if (in_array($id, $all_services)) {
                                unset($all_services[array_search($id, $all_services)]);
                            }
                        }
                    }
                }

                if (empty($all_services) && !$user->hasRole('ROLE_USER_PAID2')) {
                    $isDiscount2FirstEnabled = $user->isDiscount2FirstEnabled();    // for usage in UserStat
                    $isDiscount2SecondEnabled = $user->isDiscount2SecondEnabled();  // defined below

                    $user->addRole('ROLE_USER_PAID2');
                    $user->setPayment2Paid(new \DateTime());
                    $notify->sendAfterSecondPayment($user, $promo);
                    $pay2IsPaid = true;
                } else {
                    $notify->sendAfterPayment($user, $promo);
                }

                if (key_exists('drive_condition', $comments) && $user->hasRole('ROLE_USER_PAID2')) {
                    $user->setDrivingPaidAt(new \DateTime());

                    $package = $log->getPackage();
                    $package->setSaleAt(new \DateTime());
                    $em->persist($package);

                    if ($comments['paid'] == 'first_drive') {
                        $driveInfo = $user->getDriveInfo();
                        $driveInfo['with_at'] = $comments['with_at'];

                        $user->setDriveInfo($driveInfo);
                        $em->persist($user);
                    } elseif ($comments['paid'] == 'second_drive') {
                        $driveInfo = $user->getDriveInfo();
                        $driveInfo['drive_condition'] = $comments['drive_condition'];
                        $driveInfo['place'] = $comments['place'];

                        $user->setDriveInfo($driveInfo);
                        $em->persist($user);
                    }
                }
            }
        }

        /** @var $userStat UserStat */
        $userStat = $user->getUserStat();
        if ($userStat) {
            if ($pay1IsPaid) {
                $pay1Type      = $log->getPromoKey() ? 'promo_mixed' : 'regular';
                $discount1Type = $user->getRegion()->isDiscount1Enabled() ? 'first' : null;

                $userStat->setPay1Type($pay1Type);
                $userStat->setDiscount1Type($discount1Type);
            }

            if ($pay2IsPaid) {
                $pay2Type = $log->getPromoKey() ? 'promo_mixed' : 'regular';

                $region        = $user->getRegion();
                $discount2Type = null;

                if ($isDiscount2FirstEnabled) {
                    $discount2Type = 'first';
                } elseif ($isDiscount2SecondEnabled) {
                    $discount2Type = 'second';
                } elseif (($region->getDiscount2FirstAmount() > 0)
                    && ($region->getDiscount2FirstDays() > 0)
                ) {
                    $today = new \DateTime('today');

                    $Payment1DaysAgo = date_diff($user->getPayment1Paid(), $today)->days;

                    $discount2FirstDays         = $region->getDiscount2FirstDays();
                    $discount2SecondDays        = $region->getDiscount2SecondDays();
                    $discount2BetweenPeriodDays = $region->getDiscount2BetweenPeriodDays();

                    if ($Payment1DaysAgo > $discount2FirstDays
                        && $Payment1DaysAgo <= ($discount2FirstDays + $discount2BetweenPeriodDays)
                    ) {
                        $discount2Type = 'between_first_second';
                    }

                    if ($Payment1DaysAgo > ($discount2FirstDays
                            + $discount2SecondDays
                            + $discount2BetweenPeriodDays)
                    ) {
                        $discount2Type = 'after_second';
                    }
                }

                $userStat->setPay2Type($pay2Type);
                $userStat->setDiscount2Type($discount2Type);
            }

            $em->persist($userStat);
        }

        if ($pay2IsPaid && $user->getByApi()
            && in_array($this->container->getParameter('server_type'), ['prod', 'qa'])) {
            $this->get('app.second_payment_post')->sendPayment($user->getId(), $log->getId());
        }

        $em->persist($user);
        $em->flush();
    }

    protected function afterSuccessRevert(Log $log)
    {
        /** @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /** @var  $user \My\AppBundle\Entity\User */
        $user = $log->getUser();
        $comment = json_decode($log->getComment(), true);

        if ($comment != null && isset($comment['services'])) {
            $ids = explode(',', $comment['services']);
            $services = $em->getRepository('AppBundle:Service')->createQueryBuilder('s')
                ->andWhere('s.type IS NOT NULL')
                ->andWhere('s.type != :type')->setParameter(':type', 'site_access')
                ->andWhere('s.id IN (:ids)')->setParameter('ids', $ids)
                ->getQuery()->getArrayResult();

            if ($services && $user->hasRole('ROLE_USER_PAID2')) {
                $user->removeRole('ROLE_USER_PAID2');
            }
        }

        if ($comment != null && isset($comment['categories'])) {
            if ($user->hasRole('ROLE_USER_PAID') && !$user->hasRole('ROLE_USER_PAID2')) {
                $user->removeRole('ROLE_USER_PAID');
            }
        }
        $em->persist($user);
        $em->flush();
    }

    protected function afterFailRevert(RevertLog $log, $code)
    {
        /** @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $log->setInfo(array_merge($log->getInfo(), array('fail' => $code)));
        $em->persist($log);
        $em->flush();
    }
}
