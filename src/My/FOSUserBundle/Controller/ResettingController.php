<?php

namespace My\FOSUserBundle\Controller;

use FOS\UserBundle\Controller\ResettingController as BaseController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class ResettingController extends BaseController
{
    const SESSION_EMAIL = 'fos_user_send_resetting_email/email';

    /**
     * Request reset user password: show form
     */
    public function requestAction()
    {
        return $this->container->get('templating')
            ->renderResponse('FOSUserBundle:Resetting:request.html.'.$this->getEngine());
    }

    /**
     * Request reset user password: submit form and send email
     */
    public function sendEmailAction()
    {
        $username = $this->container->get('request')->request->get('username');

        /** @var $user UserInterface */
        $user = $this->container->get('fos_user.user_manager')->findUserByUsernameOrEmail($username);

        /** @var $translator \Symfony\Bundle\FrameworkBundle\Translation\Translator */
        $translator = $this->container->get('translator');

        if (null === $user) {
            if ($this->container->get('request')->isXmlHttpRequest()) {
                $error = $translator->trans(
                    'resetting.request.invalid_username',
                    array('%username%' => $username),
                    'FOSUserBundle'
                );
                return new JsonResponse(array(
                    'errors' => array('username' => $error),
                ));
            }

            return $this->container->get('templating')
                ->renderResponse('FOSUserBundle:Resetting:request.html.'.$this->getEngine(), array(
                    'invalid_username' => $username,
                ));
        }

        if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            if ($this->container->get('request')->isXmlHttpRequest()) {
                $error = $translator->trans('resetting.password_already_requested', array(), 'FOSUserBundle');
                return new JsonResponse(array(
                    'errors' => array('username' => $error),
                ));
            }

            return $this->container->get('templating')
                ->renderResponse('FOSUserBundle:Resetting:passwordAlreadyRequested.html.'.$this->getEngine());
        }

        if (null === $user->getConfirmationToken()) {
            /** @var $tokenGenerator \FOS\UserBundle\Util\TokenGeneratorInterface */
            $tokenGenerator = $this->container->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
        }

        $this->container->get('session')->set(static::SESSION_EMAIL, $this->getObfuscatedEmail($user));
        $this->container->get('fos_user.mailer')->sendResettingEmailMessage($user);
        $user->setPasswordRequestedAt(new \DateTime());
        $this->container->get('fos_user.user_manager')->updateUser($user);

        if ($this->container->get('request')->isXmlHttpRequest()) {
            $em = $this->container->get('doctrine.orm.entity_manager');
            $settings = $em->getRepository('AppBundle:Setting')->getAllData();

            /** @var $user \My\AppBundle\Entity\User */

            $after_password_recovery_title = $settings['after_password_recovery_title'];
            $after_password_recovery_text = $settings['after_password_recovery_text'];
            if ($after_password_recovery_text) {
                $placeholders = array();
                $placeholders['{{ last_name }}'] = $user->getLastName();
                $placeholders['{{ first_name }}'] = $user->getFirstName();
                $placeholders['{{ patronymic }}'] = $user->getPatronymic();
                $placeholders['{{ email }}'] = $user->getEmail();
                $placeholders['{{ dear }}'] = ($user->getSex() == 'female' ? 'Уважаемая' : 'Уважаемый');
                for ($i = 1; $i <= 5; $i++) {
                    $placeholders['{{ sign_' . $i . ' }}'] = $settings['sign_' . $i];
                }

                $after_password_recovery_text = str_replace(
                    array_keys($placeholders),
                    array_values($placeholders),
                    $after_password_recovery_text
                );
            }

            return new JsonResponse(array(
                'success' => true,
                'title' => $after_password_recovery_title,
                'message' => $after_password_recovery_text,
            ));
        }

        return new RedirectResponse($this->container->get('router')->generate('fos_user_resetting_check_email'));
    }

    /**
     * Tell the user to check his email provider
     */
    public function checkEmailAction()
    {
        $session = $this->container->get('session');
        $email = $session->get(static::SESSION_EMAIL);
        $session->remove(static::SESSION_EMAIL);

        if (empty($email)) {
            // the user does not come from the sendEmail action
            return new RedirectResponse($this->container->get('router')->generate('fos_user_resetting_request'));
        }

        return $this->container->get('templating')
            ->renderResponse('FOSUserBundle:Resetting:checkEmail.html.'.$this->getEngine(), array(
                'email' => $email,
            ));
    }

    // Reset user password
    public function resetAction($token)
    {
        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);

        if (null === $user) {
            $message = sprintf('The user with "confirmation token" does not exist for value "%s"', $token);
            throw new NotFoundHttpException($message);
        }

        if (!$user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            return new RedirectResponse($this->container->get('router')->generate('fos_user_resetting_request'));
        }

        $form = $this->container->get('fos_user.resetting.form');
        $formHandler = $this->container->get('fos_user.resetting.form.handler');
        $process = $formHandler->process($user);

        if ($process) {
            $this->setFlash('fos_user_success', 'resetting.flash.success');
            $response = new RedirectResponse($this->getRedirectionUrl($user));
            $this->authenticateUser($user, $response);

            return $response;
        }

        return $this->container->get('templating')
            ->renderResponse('FOSUserBundle:Resetting:reset.html.'.$this->getEngine(), array(
                'token' => $token,
                'form' => $form->createView(),
            ));
    }

    /**
     * Authenticate a user with Symfony Security
     *
     * @param \FOS\UserBundle\Model\UserInterface        $user
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    protected function authenticateUser(UserInterface $user, Response $response)
    {
        try {
            $this->container->get('fos_user.security.login_manager')->loginUser(
                $this->container->getParameter('fos_user.firewall_name'),
                $user,
                $response
            );
        } catch (AccountStatusException $ex) {
            // We simply do not authenticate users which do not pass the user
            // checker (not enabled, expired, etc.).
        }
    }

    /**
     * Generate the redirection url when the resetting is completed.
     *
     * @param \FOS\UserBundle\Model\UserInterface $user
     *
     * @return string
     */
    protected function getRedirectionUrl(UserInterface $user)
    {
        return $this->container->get('router')->generate('fos_user_profile_show');
    }

    /**
     * Get the truncated email displayed when requesting the resetting.
     *
     * The default implementation only keeps the part following @ in the address.
     *
     * @param \FOS\UserBundle\Model\UserInterface $user
     *
     * @return string
     */
    protected function getObfuscatedEmail(UserInterface $user)
    {
        $email = $user->getEmail();
        if (false !== $pos = strpos($email, '@')) {
            $email = '...' . substr($email, $pos);
        }

        return $email;
    }

    /**
     * @param string $action
     * @param string $value
     */
    protected function setFlash($action, $value)
    {
        $this->container->get('session')->getFlashBag()->set($action, $value);
    }

    protected function getEngine()
    {
        return $this->container->getParameter('fos_user.template.engine');
    }
}
