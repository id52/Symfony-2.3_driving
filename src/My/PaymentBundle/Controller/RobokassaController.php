<?php

namespace My\PaymentBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RobokassaController extends AbstractController
{
    public function queryAction(Request $request)
    {
        $params = $this->container->getParameter('payment.params');
        $params = $params['robokassa'];

        $id = $request->get('id');
        $uid = $request->get('uid');
        $sum = $request->get('sum');

        $url = $params['url'].'?'.http_build_query(array(
                'MrchLogin'      => $params['login'],
                'OutSum'         => $sum,
                'Desc'           => '',
                'SignatureValue' => md5(implode(':', array(
                    $params['login'], $sum, '',
                    $params['pass1'],
                    'shp_id='.$id, 'shp_uid='.$uid,
                ))),
                'shp_id' => $id,
                'shp_uid' => $uid,
            ));
        return $this->redirect($url);
    }

    public function resultAction(Request $request)
    {
        $params = $this->container->getParameter('payment.params');
        $params = $params['robokassa'];

        $response = '';
        $sid = $request->get('InvId');
        $id = $request->get('shp_id');
        $uid = $request->get('shp_uid');
        $sum = $request->get('OutSum');
        $hash = md5(implode(':', array(
            $sum, $sid,
            $params['pass2'],
            'shp_id='.$id, 'shp_uid='.$uid,
        )));
        if ($hash == strtolower($request->get('SignatureValue'))) {
            /** @var $em \Doctrine\ORM\EntityManager */
            $em = $this->getDoctrine()->getManager();
            /** @var $log \My\PaymentBundle\Entity\Log */
            $log = $em->getRepository('PaymentBundle:Log')->find($id);
            if ($log
                && $sum == $log->getSum()
                && $uid == $log->getUser()->getId()
                && !$log->getPaid()
            ) {
                $log->setPaid(true);
                $log->setSId($sid);
                $em->persist($log);
                $em->flush();

                $comments = json_decode($log->getComment(), true);
                if (!isset($comments['owe_stage'])) {
                    $em->getRepository('AppBundle:User')->removeTriedsAndReservists($this->getUser());
                }

                $this->afterSuccessPayment($log);

                $response = 'OK'.$sid;
            }
        }
        return new Response($response);
    }

    public function successAction()
    {
        return $this->render('PaymentBundle:Robokassa:success.html.twig');
    }

    public function failAction()
    {
        return $this->render('PaymentBundle:Robokassa:fail.html.twig');
    }

    public function isPaidAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new HttpException(403);
        }

        $user = $this->getUser();
        /** @var $logRepo \My\PaymentBundle\Repository\LogRepository */
        $logRepo = $this->getDoctrine()->getManager()->getRepository('PaymentBundle:Log');
        if ($user) {
            /** @var $payment \My\PaymentBundle\Entity\Log */
            $payment = $logRepo->findLastPayment($user);
            $isPaid = $payment->getPaid();
        } else {
            $isPaid = false;
        }

        $comment = (array)json_decode($payment->getComment());

        if (!empty($comment['final_exam'])) {
            $redirect_url = $this->generateUrl('my_training_final_exam');
        } elseif (!empty($comment['subject_id'])) {
            $redirect_url = $this->generateUrl('my_training_exam', ['id' => $comment['subject_id']]);
        } else {
            $redirect_url = $this->generateUrl('my_payments');
        }

        return new JsonResponse(array(
            'is_paid'       => $isPaid,
            'redirect_url'  => $redirect_url,
        ));
    }
}
