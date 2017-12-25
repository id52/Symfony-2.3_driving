<?php

namespace My\AppBundle\Controller;

use My\AppBundle\Entity\User;
use My\AppBundle\Entity\UserStat;
use My\PaymentBundle\Entity\Log as PaymentLog;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class ApiController extends Controller
{
    public static $salt = '$6$rounds=5000$dHEmd29FPWEjdqoiweu0241KDkwro$';

    public function addUserAction(Request $request)
    {
        if (!$request->isMethod('post')) {
            return new JsonResponse(['error_num' => 0, 'error' => 'Not POST']);
        }

        $category_name = trim($request->get('category'));
        $region_name   = trim($request->get('region'));
        $last_name     = trim($request->get('lastname'));
        $first_name    = trim($request->get('firstname'));
        $patronymic    = trim($request->get('middlename'));
        $email         = trim($request->get('email'));
        $phone         = trim($request->get('phone'));
        $sign          = trim($request->get('sign'));

        if (!$category_name
            || !$region_name
            || !$last_name
            || !$first_name
            || !$patronymic
            || !$email
            || !preg_match('#^\d{10}$#', $phone)
            || !$sign
        ) {
            return new JsonResponse(['error_num' => 1, 'error' => 'Incorrect parameters']);
        }

        if ($sign != crypt($category_name.$region_name.$last_name.$first_name.$patronymic.$email.$phone, self::$salt)) {
            return new JsonResponse(['error_num' => 2, 'error' => 'Invalid signature']);
        }

        $em = $this->get('doctrine.orm.entity_manager');

        $category = $em->getRepository('AppBundle:Category')->findOneBy(['name' => $category_name]);
        if (!$category) {
            return new JsonResponse(['error_num' => 3, 'error' => 'Category not found']);
        }

        $region = $em->getRepository('AppBundle:Region')->findOneBy(['name' => $region_name]);
        if (!$region) {
            return new JsonResponse(['error_num' => 4, 'error' => 'Region not found']);
        }

        $category_is_active = false;
        $prices             = $region->getCategoriesPrices();
        foreach ($prices as $price) {
            /** @var $price \My\AppBundle\Entity\CategoryPrice */
            if ($price->getActive() && $price->getCategory()->getId() == $category->getId()) {
                $category_is_active = true;
            }
        }
        if (!$category_is_active) {
            return new JsonResponse(['error_num' => 5, 'error' => 'Category is not active in the region']);
        }

        $oEmail = $em->getRepository('AppBundle:User')->findOneBy(['email' => $email]);
        if ($oEmail) {
            return new JsonResponse(['error_num' => 6, 'error' => 'User with this email already exists']);
        }

        $errors = $this->get('validator')->validateValue($email, new Assert\Email());
        if (count($errors) > 0) {
            return new JsonResponse(['error_num' => 7, 'error' => 'Email is not valid']);
        }

        $chars    = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < 8; $i++) {
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        $payments = trim($request->get('payments'));
        if (!in_array($payments, ['1', '1,2'])) {
            return new JsonResponse(['error_num' => 8, 'error' => 'Bad parameter of payments']);
        }

        $moder = trim($request->get('moder'));
        if (!in_array($moder, ['1', '0'])) {
            return new JsonResponse(['error_num' => 9, 'error' => 'Bad parameter moder']);
        }

        if ($moder == 1) {
            return new JsonResponse(['success' => true]);
        }

        $date = date_create(trim($request->get('date')));
        if (!$date) {
            $date = new \DateTime();
        }

        $transactionId = trim($request->get('transaction_id'));

        $transactionTime = new \DateTime(trim($request->get('transaction_time')));
        if (!$transactionTime) {
            $transactionTime = new \DateTime();
        }

        $isProfit = intval($request->get('profit')) > 0;

        $user = new User();
        $user->setEnabled(!$isProfit);
        $user->setCategory($category);
        $user->setRegion($region);
        $user->setLastName($last_name);
        $user->setFirstName($first_name);
        $user->setPatronymic($patronymic);
        $user->setEmail($email);
        $user->setPhoneMobile($phone);
        $user->setPhoneMobileStatus('confirmed');
        $user->setPlainPassword($password);
        $user->setByApi(true);
        $user->setByApiComb(intval($request->get('comb')) > 0);
        $user->setByApiExpr(intval($request->get('expr')) > 0);
        $user->setApiProfit($isProfit);
        $user->setConfirmationToken(sha1(uniqid('krealab', true)));
        $user->setCreatedAt($date);

        if ($isProfit) {
            $user->setOverdueUnsubscribed(true);
            $user->setUnsubscribedX(true);
        }

        $em->persist($user);

        $regType = 'unpaid';
        $pay1Type = null;
        $pay2Type = null;

        if ($payments == '1' || $payments == '1,2') {
            $pay1Type = 'by_api';
            $regType = 'paid_1';
        }

        if ($payments == '1,2') {
            $pay2Type = 'by_api';
            $regType = 'paid_2';
        }

        $userStat = new UserStat();
        $userStat->setUser($user);
        $userStat->setRegBy($userStat::REG_BY_API);
        $userStat->setRegType($regType);
        $userStat->setPay1Type($pay1Type);
        $userStat->setPay2Type($pay2Type);
        $em->persist($userStat);

        $em->flush();

        $comment = ['categories' => $category->getId()];
        $sum     = max(intval($request->get('sum')), 0);

        if ($payments == '1,2') {
            $all_services = [];
            $services     = $em->getRepository('AppBundle:Service')->createQueryBuilder('s')
                ->andWhere('s.type IS NOT NULL')
                ->andWhere('s.type != :type')->setParameter(':type', 'site_access')
                ->getQuery()->getArrayResult();
            foreach ($services as $service) {
                $all_services[] = $service['id'];
            }

            $comment['services'] = implode(',', $all_services);

            $user->addRole('ROLE_USER_PAID2');
            $user->setPayment2Paid(new \DateTime());
            $em->persist($user);
        }

        if ($payments == '1' || $payments == '1,2') {
            $user->addRole('ROLE_USER_PAID');
            $user->setPayment1Paid(new \DateTime());
            $em->persist($user);

            $log = new PaymentLog();
            $log->setUser($user);
            $log->setSum($sum);
            $log->setComment(json_encode($comment));
            $log->setPaid(true);
            $log->setSType('api');

            if ($payments == '1,2' && $transactionId) {
                $log->setSId($transactionId);
                $log->setUpdatedAt($transactionTime);
            }

            $em->persist($log);
            $em->flush();
        }

        if (!$isProfit) {
            $settings_notifies = $em->getRepository('AppBundle:Setting')->getAllData();

            $subject = isset($settings_notifies['api_add_user_email_title'])
                ? $settings_notifies['api_add_user_email_title'] : '';
            $message = isset($settings_notifies['api_add_user_email_text'])
                ? $settings_notifies['api_add_user_email_text'] : '';

            $pass = $this->generateUrl('entrance_by_link', ['token' => $user->getConfirmationToken()], true);

            $placeholders = [
                '{{ email }}'    => $email,
                '{{ url }}'      => $this->generateUrl('homepage', [], true),
                '{{ password }}' => $password,
                '{{ pass }}'     => $pass,
            ];

            $message = str_replace(array_keys($placeholders), array_values($placeholders), $message);
            $this->get('app.notify')->sendEmail($user, $subject, $message);

            if ($payments == '1') {
                $this->get('app.notify')->sendAfterFirstPayment($user);
            }
        }

        return new JsonResponse(['user_id' => $user->getId()]);
    }

    public function payment2Action(Request $request)
    {
        if (!$request->isMethod('post')) {
            return new JsonResponse(['error_num' => 0, 'error' => 'Not POST']);
        }

        $user_id = trim($request->get('user_id'));
        $sign    = trim($request->get('sign'));

        if (!$user_id || !$sign) {
            return new JsonResponse(['error_num' => 1, 'error' => 'Incorrect parameters']);
        }

        if ($sign != crypt($user_id, self::$salt)) {
            return new JsonResponse(['error_num' => 2, 'error' => 'Invalid signature']);
        }

        $em = $this->get('doctrine.orm.entity_manager');

        $user = $em->find('AppBundle:User', $user_id);
        if (!$user) {
            return new JsonResponse(['error_num' => 3, 'error' => 'User not found']);
        }

        if (!$user->getByApi()) {
            return new JsonResponse(['error_num' => 4, 'error' => 'User was not created through API']);
        }

        if (!$user->hasRole('ROLE_USER_PAID')) {
            return new JsonResponse(['error_num' => 5, 'error' => 'User has not first payment']);
        }

        if ($user->hasRole('ROLE_USER_PAID2')) {
            return new JsonResponse(['error_num' => 6, 'error' => 'User already has second payment']);
        }

        $all_services = [];
        $services     = $em->getRepository('AppBundle:Service')->createQueryBuilder('s')
            ->andWhere('s.type IS NOT NULL')
            ->andWhere('s.type != :type')->setParameter(':type', 'site_access')
            ->getQuery()->getArrayResult();
        foreach ($services as $service) {
            $all_services[] = $service['id'];
        }
        $comment['services'] = implode(',', $all_services);

        $user->addRole('ROLE_USER_PAID2');
        $user->setPayment2Paid(new \DateTime());
        $em->persist($user);

        $sum = intval(trim($request->get('sum')));
        $sum = max($sum, 0);

        $log = new PaymentLog();
        $log->setUser($user);
        $log->setSum($sum);
        $log->setComment(json_encode($comment));
        $log->setPaid(true);
        $log->setSType('api');
        $em->persist($log);

        /** @var $userStat UserStat*/
        $userStat = $user->getUserStat();
        if ($userStat) {
            $userStat->setPay2Type($userStat::PAY_2_TYPE_BY_API);
            $em->persist($userStat);
        }

        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    public function blockUserAction(Request $request)
    {
        if (!$request->isMethod('post')) {
            return new JsonResponse(['error_num' => 0, 'error' => 'Not POST']);
        }

        $user_id = trim($request->get('user_id'));
        $sign    = trim($request->get('sign'));

        if (!$user_id || !$sign) {
            return new JsonResponse(['error_num' => 1, 'error' => 'Incorrect parameters']);
        }

        if ($sign != crypt($user_id, self::$salt)) {
            return new JsonResponse(['error_num' => 2, 'error' => 'Invalid signature']);
        }

        $em = $this->get('doctrine.orm.entity_manager');

        $user = $em->find('AppBundle:User', $user_id);
        if (!$user) {
            return new JsonResponse(['error_num' => 3, 'error' => 'User not found']);
        }

        if (!$user->getByApi()) {
            return new JsonResponse(['error_num' => 4, 'error' => 'User was not created through API']);
        }

        $user->setLocked(true);
        $em->persist($user);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    public function medConAction(Request $request)
    {
        if (!$request->isMethod('post')) {
            return new JsonResponse(['error_num' => 0, 'error' => 'Not POST']);
        }

        $userId = trim($request->get('user_id'));
        $sign   = trim($request->get('sign'));

        if (!$userId || !$sign) {
            return new JsonResponse(['error_num' => 1, 'error' => 'Incorrect parameters']);
        }

        if ($sign != crypt($userId, self::$salt)) {
            return new JsonResponse(['error_num' => 2, 'error' => 'Invalid signature']);
        }

        $em   = $this->get('doctrine.orm.entity_manager');
        $user = $em->find('AppBundle:User', $userId);

        if (!$user) {
            return new JsonResponse(['error_num' => 3, 'error' => 'User not found']);
        }
        if (!$user->getByApi()) {
            return new JsonResponse(['error_num' => 4, 'error' => 'User was not created through API']);
        }
        if (!$user->hasRole('ROLE_USER_PAID2')) {
            return new JsonResponse(['error_num' => 5, 'error' => 'User doesn`t have second payment']);
        }

        $isSuccess = false;

        $medForm = $request->get('med_form');
        if ($medForm !== null) {
            if (in_array($medForm, ['0', '1'])) {
                $medForm = boolval($medForm);
                $user->setApiMedForm($medForm);
                $isSuccess = true;
            } else {
                return new JsonResponse(['error_num' => 6, 'error' => 'Bad parameter med_form']);
            }
        }

        $contractSign = $request->get('contract_sign');
        if ($contractSign !== null) {
            if (in_array($contractSign, ['0', '1'])) {
                $contractSign = boolval($contractSign);
                $user->setApicontractSign($contractSign);
                $isSuccess = true;
            } else {
                return new JsonResponse(['error_num' => 7, 'error' => 'Bad parameter contract_sign']);
            }
        }

        if (!$isSuccess) {
            return new JsonResponse(['error_num' => 8, 'error' => 'No optional parameters was not passed']);
        }

        $em->persist($user);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
