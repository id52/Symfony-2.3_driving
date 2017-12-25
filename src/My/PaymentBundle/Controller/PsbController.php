<?php

namespace My\PaymentBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PsbController extends AbstractController
{
    protected function getRedirectUrl()
    {
        $user = $this->getUser();
        /** @var $logRepo \My\PaymentBundle\Repository\LogRepository */
        $logRepo = $this->getDoctrine()->getManager()->getRepository('PaymentBundle:Log');
        if ($user) {
            /** @var $payment \My\PaymentBundle\Entity\Log */
            $payment = $logRepo->findLastPayment($user, 'psb');
        }

        $comment = (array)json_decode($payment->getComment());

        if (!empty($comment['final_exam'])) {
            $redirect_url = $this->generateUrl('my_training_final_exam', [], true);
        } elseif (!empty($comment) and !empty($comment['subject_id'])) {
            $redirect_url = $this->generateUrl('my_training_exam', ['id' => $comment['subject_id']], true);
        } else {
            $redirect_url = $this->generateUrl('homepage', array(), true);
        }

        return $redirect_url;
    }

//    public function queryAction($id)
//    {
//        /** @var $em \Doctrine\ORM\EntityManager */
//        $em = $this->getDoctrine()->getManager();
//
//        /** @var $log \My\PaymentBundle\Entity\Log */
//        $log = $em->getRepository('PaymentBundle:Log')->createQueryBuilder('l')
//            ->andWhere('l.id = :id')->setParameter('id', $id)
//            ->andWhere('l.user = :user')->setParameter('user', $this->getUser())
//            ->andWhere('l.s_type = :s_type')->setParameter('s_type', 'psb')
//            ->setMaxResults(1)
//            ->getQuery()->getOneOrNullResult();
//        if (!$log) {
//            throw $this->createNotFoundException();
//        }
//
//        $params = $this->container->getParameter('payment.params');
//        $params = $params['psb'];
//
//        $comments = json_decode($log->getComment(), true);
//
//        $date = new \DateTime('now', new \DateTimeZone('UTC'));
//        $timestamp = $date->format('YmdHis');
//
//        $hmac_params = array(
//            'AMOUNT'     => $log->getSum(),
//            'CURRENCY'   => 'RUB',
//            'ORDER'      => sprintf('%06s', $log->getId()),
//            'DESC'       => isset($comments['desc']) ? $comments['desc'] : '-',
//            'MERCH_NAME' => $params['merchant_name'],
//            'MERCHANT'   => $params['merchant_id'],
//            'TERMINAL'   => $params['terminal_id'],
//            'EMAIL'      => $params['merchant_email'],
//            'TRTYPE'     => 1,
//            'TIMESTAMP'  => $timestamp,
//            'NONCE'      => md5(rand(100000, 9000000)),
//            'BACKREF'    => $this->getRedirectUrl(),
//        );
//
//        $psign = $this->generateHmac($hmac_params, $params['key']);
//
//        return $this->render('PaymentBundle:Psb:query.html.twig', array(
//            'url'         => $params['url'],
//            'hmac_params' => $hmac_params,
//            'psign'       => $psign,
//        ));
//    }


    public function queryAction($id, $uid, $trtype = 1)
    {
        if ($trtype == 14) {
            $cntxt = $this->get('security.context');
            if (!$cntxt->isGranted('ROLE_MOD_ACCOUNTANT')) {
                throw new AccessDeniedHttpException();
            }
        }

        /** @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /** @var $log \My\PaymentBundle\Entity\Log */
        $log = $em->getRepository('PaymentBundle:Log')->createQueryBuilder('l')
            ->andWhere('l.id = :id')->setParameter('id', $id)
            ->andWhere('l.user = :user')->setParameter('user', $uid)
            ->andWhere('l.s_type = :s_type')->setParameter('s_type', 'psb')
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();
        if (!$log) {
            throw $this->createNotFoundException();
        }

        $hmac_params = $this->generateHmacParameters($log, $trtype);

        $psign = $this->generateHmac($hmac_params['parameters'], $hmac_params['key']);

        if (!array_key_exists('MERCHANT', $hmac_params['parameters'])) {
            $hmac_params['parameters']['MERCHANT'] = $hmac_params['merchant'];
        }

        return $this->render('PaymentBundle:Psb:query.html.twig', array(
            'url'         => $hmac_params['url'],
            'hmac_params' => $hmac_params['parameters'],
            'psign'       => $psign,
        ));
    }

//    public function resultAction(Request $request)
//    {
//        $response = '';
//
//        /** @var $em \Doctrine\ORM\EntityManager */
//        $em = $this->getDoctrine()->getManager();
//
//        $log = $em->getRepository('PaymentBundle:Log')->find(intval($request->get('ORDER')));
//        if ($log) {
//            $params = $this->container->getParameter('payment.params');
//            $params = $params['psb'];
//
//            $hmac_params = array(
//                $request->get('AMOUNT'),
//                $request->get('CURRENCY'),
//                $request->get('ORDER'),
//                $request->get('MERCH_NAME'),
//                $request->get('MERCHANT'),
//                $request->get('TERMINAL'),
//                $request->get('EMAIL'),
//                $request->get('TRTYPE'),
//                $request->get('TIMESTAMP'),
//                $request->get('NONCE'),
//                $request->get('BACKREF'),
//                $request->get('RESULT'),
//                $request->get('RC'),
//                $request->get('RCTEXT'),
//                $request->get('AUTHCODE'),
//                $request->get('RRN'),
//                $request->get('INT_REF'),
//            );
//            $psign = $this->generateHmac($hmac_params, $params['key']);
//
//            if ($psign == strtolower($request->get('P_SIGN'))) {
//                if ($request->get('AMOUNT') == $log->getSum()
//                    && $request->get('CURRENCY') == 'RUB'
//                    && $request->get('RESULT') == '0'
//                    && !$log->getPaid()
//                ) {
//                    $log->setPaid(true);
//                    $log->setSId($request->get('RRN'));
//                    $em->persist($log);
//                    $em->flush();
//
//                    $comments = json_decode($log->getComment(), true);
//                    if (!isset($comments['owe_stage'])) {
//                        $em->getRepository('AppBundle:User')->removeTriedsAndReservists($this->getUser());
//                    }
//
//                    $this->afterSuccessPayment($log);
//
//                    $response = 'OK';
//                }
//            }
//        }
//
//        return new Response($response);
//    }

    public function resultAction(Request $request)
    {
        $path = $this->get('kernel')->getRootDir().'/logs/psb.log';
        $data = var_export($_GET, true).PHP_EOL.var_export($_POST, true).PHP_EOL.PHP_EOL;
        file_put_contents($path, $data, FILE_APPEND);

        $response = '';

        /** @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $log = $em->find('PaymentBundle:Log', intval($request->get('ORDER')));
        if ($log) {
            $params = array(
                'key'            => $this->container->getParameter('psb_key'),
                'terminal_id'    => $this->container->getParameter('psb_terminal_id'),
                'merchant_id'    => $this->container->getParameter('psb_merchant_id'),
                'merchant_name'  => $this->container->getParameter('psb_merchant_name'),
                'merchant_email' => $this->container->getParameter('psb_merchant_email'),
                'url'            => $this->container->getParameter('psb_url'),
            );

            $trtype = intval($request->get('TRTYPE'));
            switch ($trtype) {
                case 1: // Оплата
                    $hmac_params = array(
                        $request->get('AMOUNT'),
                        $request->get('CURRENCY'),
                        $request->get('ORDER'),
                        $request->get('MERCH_NAME'),
                        $request->get('MERCHANT'),
                        $request->get('TERMINAL'),
                        $request->get('EMAIL'),
                        $request->get('TRTYPE'),
                        $request->get('TIMESTAMP'),
                        $request->get('NONCE'),
                        $request->get('BACKREF'),
                        $request->get('RESULT'),
                        $request->get('RC'),
                        $request->get('RCTEXT'),
                        $request->get('AUTHCODE'),
                        $request->get('RRN'),
                        $request->get('INT_REF'),
                    );
                    break;
                case 14: // Возврат
                    $hmac_params = array(
                        $request->get('ORDER'),
                        $request->get('AMOUNT'),
                        $request->get('CURRENCY'),
                        $request->get('ORG_AMOUNT'),
                        $request->get('RRN'),
                        $request->get('INT_REF'),
                        $request->get('TRTYPE'),
                        $request->get('TERMINAL'),
                        $request->get('BACKREF'),
                        $request->get('EMAIL'),
                        $request->get('TIMESTAMP'),
                        $request->get('NONCE'),
                        $request->get('RESULT'),
                        $request->get('RC'),
                        $request->get('RCTEXT'),
                    );
                    break;
                default:
                    $hmac_params = array();
            }

            $psign = $this->generateHmac($hmac_params, $params['key']);

            if ($psign == strtolower($request->get('P_SIGN'))
                && $request->get('CURRENCY') == 'RUB'
                && $log && $request->get('AMOUNT') == $log->getSum()
            ) {
                $result = intval($request->get('RESULT'));
                switch ($trtype) {
                    case 1: // Оплата
                        if ($result == 0) {
                            $log->setPaid(true);
                            $log->setSId(trim($request->get('RRN')));
                            $comment = json_decode($log->getComment(), true);
                            $comment['authcode'] = trim($request->get('AUTHCODE'));
                            $comment['name'] = trim($request->get('NAME'));
                            $comment['card'] = trim($request->get('CARD'));
                            $comment['int_ref'] = trim($request->get('INT_REF'));
                            $log->setComment(json_encode($comment));

                            $em->persist($log);
                            $em->flush();

                            $this->afterSuccessPayment($log);

                            $response = 'OK';
                        }
                        break;
                    case 14: // Возврат
                        $revert = $em->getRepository('PaymentBundle:RevertLog')->createQueryBuilder('rl')
                            ->andWhere('rl.paid = 0 AND rl.info = :info')->setParameter('info', 'a:0:{}')
                            ->addOrderBy('rl.created_at', 'DESC')
                            ->setMaxResults(1)->getQuery()->getOneOrNullResult();
                        if ($result == 0) {
                            $revert->setPaid(true);
                            $em->persist($revert);
                            $em->flush();

                            $this->afterSuccessRevert($log);

                            $response = 'OK';
                        } else {
                            $this->afterFailRevert($revert, $result);
                        }
                        break;
                }
            }
        }

        return new Response($response);
    }















    protected function generateHmac(array $params, $key)
    {
        $str = '';
        foreach ($params as $k => $v) {
            if ($k !== 'DESC') {
                $str .= strlen($v).$v;
            }
        }
        return hash_hmac('sha1', $str, pack('H*', $key));
    }

    /**
     * @param $log \My\PaymentBundle\Entity\Log
     * @param $trtype integer
     * @return array
     */
    protected function generateHmacParameters($log, $trtype)
    {
        $params = array(
            'key'            => $this->container->getParameter('psb_key'),
            'terminal_id'    => $this->container->getParameter('psb_terminal_id'),
            'merchant_id'    => $this->container->getParameter('psb_merchant_id'),
            'merchant_name'  => $this->container->getParameter('psb_merchant_name'),
            'merchant_email' => $this->container->getParameter('psb_merchant_email'),
            'url'            => $this->container->getParameter('psb_url'),
        );

        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $timestamp = $date->format('YmdHis');
        $comments = json_decode($log->getComment(), true);
        switch ($trtype) {
            case 1:
                $hmac_params = array(
                    'AMOUNT'     => $log->getSum(),
                    'CURRENCY'   => 'RUB',
                    'ORDER'      => sprintf('%06s', $log->getId()),
                    'DESC'       => isset($comments['desc']) ? $comments['desc'] : '-',
                    'MERCH_NAME' => $params['merchant_name'],
                    'MERCHANT'   => $params['merchant_id'],
                    'TERMINAL'   => $params['terminal_id'],
                    'EMAIL'      => $params['merchant_email'],
                    'TRTYPE'     => 1,
                    'TIMESTAMP'  => $timestamp,
                    'NONCE'      => md5(rand(100000, 9000000)),
                    'BACKREF'    => $this->getRedirectUrl(),
                );
                break;
            case 14:
                $backurl = $this->generateUrl('psb_info_revert', array('uid' => $log->getUser()->getId()), true);
                $hmac_params = array(
                    'ORDER'      => sprintf('%06s', $log->getId()),
                    'AMOUNT'     => $log->getSum(),
                    'CURRENCY'   => 'RUB',
                    'ORG_AMOUNT' => $log->getSum(),
                    'RRN'        => $log->getSId(),
                    'INT_REF'    => $comments['int_ref'],
                    'TRTYPE'     => 14,
                    'TERMINAL'   => $params['terminal_id'],
                    'BACKREF'    => $backurl,
                    'EMAIL'      => $params['merchant_email'],
                    'TIMESTAMP'  => $timestamp,
                    'NONCE'      => md5(rand(100000, 9000000)),
                );
                break;
            default:
                throw $this->createNotFoundException();
        }

        return array(
            'parameters' => $hmac_params,
            'key'        => $params['key'],
            'url'        => $params['url'],
            'merchant'   => $params['merchant_id'],
        );
    }

    public function infoRevertAction($uid)
    {
        $this->get('session')->getFlashBag()->add('info', 'success_revert_money');

        $url = $this->generateUrl('admin_revert_money_user_card', array('id' => $uid));
        return $this->redirect($url);
    }
}
