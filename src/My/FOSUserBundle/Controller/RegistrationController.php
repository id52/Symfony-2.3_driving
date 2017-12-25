<?php

namespace My\FOSUserBundle\Controller;

use FOS\UserBundle\Controller\RegistrationController as BaseController;
use FOS\UserBundle\Model\UserInterface;
use My\AppBundle\Entity\ServicePrice;
use My\AppBundle\Entity\User;
use My\AppBundle\Service\UserHelper;
use My\AppBundle\Twig\Extension\CommonExtension;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\RememberMe\AbstractRememberMeServices;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationController extends BaseController
{
    /**
     * @param $form \Symfony\Component\Form\Form
     * @param $name string
     *
     * @return array
     */
    protected function getErrorMessages($form, $name = '')
    {
        $errors = array();
        foreach ($form->getErrors() as $key => $error) {
            $template = $error->getMessageTemplate();
            $parameters = $error->getMessageParameters();

            foreach ($parameters as $var => $value) {
                $template = str_replace($var, $value, $template);
            }
            $errors[$name] = $error->getMessage();
        }
        if ($form->count()) {
            foreach ($form as $child) {
                /** @var $child \Symfony\Component\Form\Form */

                if (!$child->isValid()) {
                    //$errors[$child->getName()] = $this->getErrorMessages($child);
                    $cname = ($name ? $name.'_' : '').$child->getName();
                    $errors = array_merge($errors, $this->getErrorMessages($child, $cname));
                }
            }
        }
        return $errors;
    }

    public function registerAction()
    {
        return new RedirectResponse($this->container->get('router')->generate('fos_user_security_login'));

        $em = $this->container->get('doctrine.orm.entity_manager');
        $request = $this->container->get('request');
        $session = $request->getSession();
        $sp_name = 'reg_check_phone';
        $sp_data = $session->get($sp_name);

        //some variables
        $form = $this->container->get('fos_user.registration.form');

        //common registration stuff
        $formHandler = $this->container->get('app.registration.form.handler'); //our handler!
        $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');

        if ($request->isMethod('post') && (!isset($sp_data['status']) || $sp_data['status'] != 'confirmed')) {
            $form->handleRequest($request);
            $form->get('phone_mobile')->addError(new FormError('Необходимо подтвердить номер!'));
            $process = false;
        } else {
            $process = $formHandler->process($confirmationEnabled);
        }

        if ($process) {
            /** @var $user \My\AppBundle\Entity\User */
            $user = $form->getData();
            $user->setPhoneMobile($sp_data['phone']);
            $user->setPhoneMobileStatus('confirmed');
            $em->persist($user);
            $em->flush();

            $session->remove($sp_name);

            $authUser = false;
            if ($confirmationEnabled) {
                $this->container->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());
                $route = 'fos_user_registration_check_email';
            } else {
                $authUser = true;
                $route = 'fos_user_registration_confirmed';
            }

            $this->setFlash('fos_user_success', 'registration.flash.user_created');
            $url = $this->container->get('router')->generate($route);
            $response = new RedirectResponse($url);

            if ($authUser) {
                $this->authenticateUser($user, $response);
            }

            if ($this->container->get('request')->isXmlHttpRequest()) {
                $response = new JsonResponse(array(
                    'success' => true,
                ));
            }

            return $response;
        }

        if ($this->container->get('request')->isXmlHttpRequest()) {
            return new JsonResponse(array(
                'errors' => $this->getErrorMessages($form),
            ));
        }

        return $this->container->get('templating')
            ->renderResponse('FOSUserBundle:Registration:register.html.'.$this->getEngine(), array(
                'form' => $form->createView(),
            ));
    }

    public function confirmedAction()
    {
        /** @var $user \My\AppBundle\Entity\User */
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        /** @var $em \Doctrine\ORM\EntityManager */
        $em = $this->container->get('doctrine.orm.default_entity_manager');

        $region = $user->getRegion();
        $qb = $em->getRepository('AppBundle:CategoryPrice')->createQueryBuilder('cp');
        $qb
            ->andWhere('cp.active = :active')->setParameter(':active', true)
            ->andWhere('cp.region = :region')->setParameter(':region', $region)
            ->andWhere('cp.category = :category')->setParameter(':category', $user->getCategory())
        ;
        $categories_prices = $qb->getQuery()->execute();
        $categories_prices_sum = 0;
        foreach ($categories_prices as $price) {
            /** @var $price \My\AppBundle\Entity\CategoryPrice */

            $categories_prices_sum += $price->getPrice();
        }

        $servicePrices = $em->getRepository('AppBundle:ServicePrice')->getPriceByRegion($region);
        $secondPaidPrice = 0;
        foreach ($servicePrices as $servicePrice) {
            /** @var $servicePrice ServicePrice */

            $secondPaidPrice += $servicePrice->getPrice();
        }

        //discount?
        $discount = 0;
        if ($categories_prices && $region->getDiscount1Amount() > 0) {
            $today = new \DateTime('today');
            $from  = $region->getDiscount1DateFrom();
            $to    = $region->getDiscount1DateTo();
            if (($today <= $to && $today >= $from) || $region->getDiscount1TimerPeriod() > 0) {
                $discount = $region->getDiscount1Amount();
                if ($discount > $categories_prices_sum) {
                    $discount = $categories_prices_sum;
                }
            }
        }

        $secondPaidPrice = max((int)($secondPaidPrice - $user->getSecondPaidDiscount()), 0);

        /** @var $settings_repository \My\AppBundle\Repository\SettingNotifyRepository */
        $settings_notifies_repository = $em->getRepository('AppBundle:Setting');
        $settings_notifies = $settings_notifies_repository->getAllData();

        $settings_notifies['confirmed_registration_text'] = str_replace(
            '{{sum}}',
            $categories_prices_sum,
            $settings_notifies['confirmed_registration_text']
        );
        $settings_notifies['confirmed_registration_text'] = str_replace(
            '{{sum_discount}}',
            (($categories_prices_sum - $discount < 0) ? 0 : ($categories_prices_sum - $discount)),
            $settings_notifies['confirmed_registration_text']
        );
        $settings_notifies['confirmed_registration_text'] = str_replace(
            '{{price}}',
            $secondPaidPrice,
            $settings_notifies['confirmed_registration_text']
        );

        $keyAutoCreatePromo="";
        $promoData = $em->getRepository('AppBundle:SettingAutoCreatePromo')->getAllData();
        if (count($promoData)) {
            /** @var  $promo \My\AppBundle\Entity\Promo */
            $promo = $em->getRepository('AppBundle:Promo')->find($promoData['promoId']);
            if (isset($promo)) {
                /** @var  $promoKey \My\AppBundle\Entity\PromoKey */
                $promoKey = $promo->getKeys()[0];
                $keyAutoCreatePromo = $promoKey->getHash();
            }
        }

        $settings_notifies['confirmed_registration_text'] = str_replace(
            '{{promo_key}}',
            $keyAutoCreatePromo,
            $settings_notifies['confirmed_registration_text']
        );

        return $this->container->get('templating')
            ->renderResponse('FOSUserBundle:Registration:confirmed.html.'.$this->getEngine(), array(
                'title' => $settings_notifies['confirmed_registration_title'],
                'text'  => $settings_notifies['confirmed_registration_text'],
            ));
    }

    public function resendAction($token)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        /** @var User $user */
        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }

        /** @var UserHelper $userHelper */
        $userHelper = $this->container->get('app.user_helper');
        $plainPassword = $userHelper->generateCode();
        $user->setPlainPassword($plainPassword);
        $userManager->updateUser($user, true);

        if ($user->getOffline()) {
            $userHelper->sendMessages($user, $plainPassword, false);
        } else {
            $this->container->get('fos_user.mailer')->sendConfirmationEmailMessage($user);
        }
        $em = $this->container->get('doctrine.orm.entity_manager');
        $settings = $em->getRepository('AppBundle:Setting')->getAllData();

        if (isset($settings['confirmed_registration_again_text'])) {
            $message = $settings['confirmed_registration_again_text'];

            $placeholders = array();
            $placeholders['{{ last_name }}'] = $user->getLastName();
            $placeholders['{{ first_name }}'] = $user->getFirstName();
            $placeholders['{{ patronymic }}'] = $user->getPatronymic();
            $placeholders['{{ email }}'] = $user->getEmail();
            $placeholders['{{ dear }}'] = ($user->getSex() == 'female' ? 'Уважаемая' : 'Уважаемый');
            for ($i = 1; $i <= 5; $i++) {
                $placeholders['{{ sign_' . $i . ' }}'] = $settings['sign_' . $i];
            }

            $message = str_replace(array_keys($placeholders), array_values($placeholders), $message);
        } else {
            $message = '<p>Новый пользователь создан. На Ваш электронный адрес '.$user->getEmail().' отправлено письмо. 
                    Перейдите по ссылке из письма, для подтверждения регистрации.</p><br><p>С уважением,</p>
                    <p>Администрация Автошколы Онлайн</p>';
        }

        return $this->container->get('templating')
            ->renderResponse('FOSUserBundle:Registration:checkEmail.html.'.$this->getEngine(), array(
                'message' => $message,
            ));
    }

    public function changeEmailAction(Request $request, $token)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        /** @var User $user */
        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }

        $form_factory = $this->container->get('form.factory');
        $form = $form_factory->createNamedBuilder('confirmation')
            ->add('email', 'text', array('constraints' => array(new Assert\Email())))
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setEmail($form->getData()['email']);
            $userManager->updateUser($user, true);

            /** @var RouterInterface $router */
            $router = $this->container->get('router');
            $url = $router->generate('fos_user_register_resend', array('token' => $token));
            return new RedirectResponse($url);
        }

        return $this->container->get('templating')
            ->renderResponse('FOSUserBundle:Registration:changeEmail.html.twig', array(
                'form' => $form->createView(),
            ));
    }

    /**
     * @param Request $request
     * @param $hash
     *
     * @return Response
     */
    public function userConfirmationAction(Request $request, $hash)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        /** @var $userConfirmation \My\AppBundle\Entity\UserConfirmation */
        $userConfirmation = $em->getRepository('AppBundle:UserConfirmation')->findOneBy(array(
            'hash'      => $hash,
            'activated' => false,
        ));
        if (!$userConfirmation) {
            throw new NotFoundHttpException();
        }

        // Logout user
        $cntxt = $this->container->get('security.context');
        if ($cntxt->isGranted('ROLE_USER')) {
            $cntxt->setToken(null);
            $cookie = new Cookie(
                $this->container->getParameter('remember_me.name'),
                null,
                1,
                $this->container->getParameter('remember_me.path'),
                $this->container->getParameter('remember_me.domain')
            );
            $request->attributes->set(AbstractRememberMeServices::COOKIE_ATTR_NAME, $cookie);
            $url = $this->container->get('router')->generate('fos_user_confirmation', array('hash' => $hash));
            return new RedirectResponse($url);
        }

        $form_factory = $this->container->get('form.factory');
        $form = $form_factory->createNamedBuilder('confirmation')
            ->add('code', 'text', [
                'constraints' => [
                    new Assert\EqualTo(array(
                        'value'   => $userConfirmation->getSmsCode(),
                        'message' => 'Неверный код',
                    )),
                    new Assert\NotBlank(),
                ],
                'attr'        => ['placeholder' => 'Код подтверждения']
            ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userConfirmation->setActivated(true);
            $em->persist($userConfirmation);
            $em->flush();

            $user = $userConfirmation->getUser();
            $user->setEnabled(true);
            $user->setPhoneMobileStatus('confirmed');
            $em->persist($user);
            $em->flush($user);

            $authManager = $this->container->get('security.authentication.manager');
            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
            $token = $authManager->authenticate($token);
            $cntxt->setToken($token);

            $url = $this->container->get('router')->generate('homepage');
            return new RedirectResponse($url);
        }

        $infoSms = '';
        $infoSmsObject = $em->getRepository('AppBundle:Setting')
            ->findOneBy(array('_key' => 'confirmation_sms_info_text'));

        if ($infoSmsObject) {
            $infoSms = str_replace(
                '{{ phone }}',
                CommonExtension::phoneFormatFilter($userConfirmation->getPhone()),
                $infoSmsObject->getValue()
            );
        }

        $errorSms = '';
        $errorSmsObject = $em->getRepository('AppBundle:Setting')
            ->findOneBy(array('_key' => 'confirmation_error_sms_info_text'));

        if ($errorSmsObject) {
            $errorSms = str_replace(
                '{{ phone }}',
                CommonExtension::phoneFormatFilter($userConfirmation->getPhone()),
                $errorSmsObject->getValue()
            );
        }

        $afterChangingPhoneNumberText = $em->getRepository('AppBundle:Setting')
            ->findOneBy(array('_key' => 'after_changing_phone_number_text'));


        return $this->container->get('templating')
            ->renderResponse('FOSUserBundle:Registration:confirmation.html.twig', array(
                'form'                             => $form->createView(),
                'phone'                            => $userConfirmation->getPhone(),
                'confirm_sms'                      => $infoSms,
                'error_sms'                        => $errorSms,
                'after_changing_phone_number_text' => $afterChangingPhoneNumberText->getValue(),
            ));
    }

    public function userConfirmationChangePhoneAction(Request $request, $hash)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        /** @var $userConfirmation \My\AppBundle\Entity\UserConfirmation */
        $userConfirmation = $em->getRepository('AppBundle:UserConfirmation')->findOneBy(array('hash' => $hash));
        if (!$userConfirmation) {
            throw new HttpException(404);
        }

        $phone = $request->request->get('phone');
        if (!$phone) {
            return new JsonResponse(array('error' => 'Не указан телефон'));
        }

        $matches = [];
        if (preg_match('#^\+7 \((\d{3})\) (\d{3})\-(\d{2})\-(\d{2})$#misu', $phone, $matches)) {
            $phone = $matches[1].$matches[2].$matches[3].$matches[4];
        } else {
            return new JsonResponse(array('error' => 'Неверный формат'));
        }

        $userConfirmation->setPhone($phone);
        $em->persist($userConfirmation);
        $user = $userConfirmation->getUser();
        $user->setPhoneMobile($phone);
        $em->persist($user);
        $em->flush();

        /** @var UserHelper $userHelper */
        $userHelper = $this->container->get('app.user_helper');
        $userHelper->sendConfirmationSms($userConfirmation, true);

        /** @var RouterInterface $router */
        $router = $this->container->get('router');
        $url = $router->generate('fos_user_confirmation_repeat_sms', array('hash' => $hash));
        return new RedirectResponse($url);
    }

    public function userConfirmationRepeatSmsAction(Request $request, $hash)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        /** @var $userConfirmation \My\AppBundle\Entity\UserConfirmation */
        $userConfirmation = $em->getRepository('AppBundle:UserConfirmation')->findOneBy(array('hash' => $hash));
        if (!$userConfirmation) {
            throw new HttpException(404);
        }

        $codeSentAgainText = $em->getRepository('AppBundle:Setting')->get('confirmation_code_was_sent_again_text');

        $current = new \DateTime();
        $diff    = $current->getTimestamp() - $userConfirmation->getLastSent()->getTimestamp();

        if ($diff < 120) {
            return new JsonResponse([
                'message'       => '',
                'seconds_left' => 120 - $diff,
            ]);
        }

        $isClicked = (bool)$request->request->get('isClicked');
        if ($isClicked) {
            $this->container->get('app.user_helper')->sendConfirmationSms($userConfirmation, false);
            return new JsonResponse([
                'message'       => $codeSentAgainText,
                'seconds_left' => 120,
            ]);
        } else {
            return new JsonResponse([
                'message'       => '',
                'seconds_left' => 0,
            ]);
        }
    }

    public function checkPhoneAjaxAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new HttpException(404);
        }

        $response = array();
        $session = $this->container->get('session');
        $s_name = 'reg_check_phone';
        $s_data = $session->get($s_name, array(
            'sent'  => 0,
            'phone' => '',
        ));

        $phone_src = trim($request->get('phone'));
        if (preg_match('#^\((\d{3})\) (\d{3})-(\d{2})-(\d{2})$#misu', $phone_src, $matches)) {
            $phone = $matches[1].$matches[2].$matches[3].$matches[4];
            if (time() - 180 > $s_data['sent'] || $phone != $s_data['phone']) {
                $code = '';
                $symbols = str_split('1234567890');
                for ($i = 0; $i < 4; $i ++) {
                    $code .= $symbols[mt_rand(0, count($symbols) - 1)];
                }
                $this->container->get('sms_uslugi_ru')->query('+7'.$phone, 'Код подтверждения: '.$code);
                $s_data['code'] = $code;
                $s_data['sent'] = time();
                $s_data['phone'] = $phone;
                $s_data['phone_src'] = $phone_src;
                $s_data['status'] = 'sended';
                $session->set($s_name, $s_data);
            }
            $response['seconds_left'] = $s_data['sent'] + 180 - time();
        }

        return new JsonResponse($response);
    }

    public function checkPhoneCodeAjaxAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new HttpException(404);
        }

        $response = array();
        $session = $this->container->get('session');
        $s_name = 'reg_check_phone';
        $s_data = $session->get($s_name, array());

        if (isset($s_data['code'])) {
            $code = trim($request->get('code'));
            if (preg_match('#^\d{4}$#misu', $code)) {
                if ($code == $s_data['code']) {
                    $response['success'] = true;
                    $s_data['status'] = 'confirmed';
                    $session->set($s_name, $s_data);
                }
            }
        }

        return new JsonResponse($response);
    }

    public function generateCode($length = 8)
    {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= rand(0, 9);
        }
        return $code;
    }
}
