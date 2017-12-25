<?php

namespace My\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Model\UserManagerInterface;
use My\AppBundle\Entity\User;
use My\AppBundle\Entity\UserConfirmation;
use My\SmsUslugiRuBundle\Service\SmsUslugiRu;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Routing\RouterInterface;

class UserHelper
{
    /** @var \Doctrine\ORM\EntityManager */
    protected $em;
    /** @var \My\SmsUslugiRuBundle\Service\SmsUslugiRu */
    protected $smsUslugi;
    /** @var \Symfony\Component\Routing\RouterInterface */
    protected $router;
    /** @var \My\AppBundle\Service\Notify */
    protected $notify;
    /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator */
    protected $translator;
    /** @var \FOS\UserBundle\Model\UserManagerInterface */
    protected $userManager;

    protected $siteHost;

    public function __construct(
        EntityManager $em,
        SmsUslugiRu $smsUslugiRu,
        RouterInterface $router,
        Notify $notify,
        Translator $translator,
        UserManagerInterface $userManager,
        $siteHost
    ) {
        $this->em = $em;
        $this->smsUslugi = $smsUslugiRu;
        $this->router = $router;
        $this->notify = $notify;
        $this->translator = $translator;
        $this->userManager = $userManager;
        $this->siteHost = $siteHost;
    }

    /**
     * Отправляет sms/email для подтверждения регистрации пользвоателя
     *
     * @param User $user
     * @param null $password
     * @param boolean $forceSend отправлять СМС даже если интервал меньше 3-х минут
     */
    public function sendMessages(User $user, $password, $forceSend)
    {
        $userConfirmation = $this->em->getRepository('AppBundle:UserConfirmation')->findOneBy(array('user' => $user));

        if ($userConfirmation) {
            $userConfirmation->setSmsCode($this->generateCode(4));
        } else {
            $userConfirmation = new UserConfirmation();
            $userConfirmation->setSmsCode($this->generateCode(4));
            $userConfirmation->setUser($user);
            $userConfirmation->setPhone($user->getPhoneMobile());
        }
        $this->em->persist($userConfirmation);
        $this->em->flush();

        $uniqueUrl = $this->router->generate('fos_user_confirmation', array(
            'hash' => $userConfirmation->getHash(),
        ), true);

        $settings_notifies_activation_add_user_title = $this->em->getRepository('AppBundle:Setting')->findOneBy([
            '_key' => 'activation_add_user_title'
        ]);

        $settings_notifies_activation_add_user_text = $this->em->getRepository('AppBundle:Setting')->findOneBy([
            '_key' => 'activation_add_user_text'
        ]);

        $emailSubject = $settings_notifies_activation_add_user_title->getValue();
        $message = $settings_notifies_activation_add_user_text->getValue();
        $message = str_replace('{{ email }}', $user->getEmail(), $message); // TODO: email тот же на который отправляем?
        $message = str_replace('{{ password }}', $password, $message);
        $message = str_replace('{{ link_confirm }}', $uniqueUrl, $message);
        $message = str_replace('{{ last_name }}', $user->getLastName(), $message);
        $message = str_replace('{{ first_name }}', $user->getFirstName(), $message);
        $message = str_replace('{{ patronymic }}', $user->getPatronymic(), $message);

        /** @var $settings_repository \My\AppBundle\Repository\SettingNotifyRepository */
        $settings_notifies = $this->em->getRepository('AppBundle:Setting')->getAllData();

        for ($i = 1; $i <= 5; $i++) {
            if (!empty($settings_notifies['sign_'. $i])) {
                $placeholders['{{ sign_'.$i.' }}'] = $settings_notifies['sign_'. $i];
            }
        }

        $this->notify->sendEmail($user, $emailSubject, $message);

        $this->sendConfirmationSms($userConfirmation, $forceSend, $password);
    }

    public function generateCode($length = 8)
    {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= rand(0, 9);
        }
        return $code;
    }

    public function sendConfirmationSms(UserConfirmation $userConfirmation, $forceSend, $password = null)
    {
        if (!$forceSend) {
            $current = new \DateTime();
            $diff = $current->getTimestamp() - $userConfirmation->getLastSent()->getTimestamp();
            if ($diff < 180) {
                return false;
            }
        }

        $text = 'Код: '.$userConfirmation->getSmsCode().';';
        $text .= ' Сайт: https://'.$this->siteHost.';';
        $text .= ' Логин: '.$userConfirmation->getUser()->getEmail().';';
        if (!$password) {
            $password = $this->generateCode(8);
            $user = $userConfirmation->getUser();
            $user->setPlainPassword($password);
            $this->userManager->updateUser($user);
        }
        $text .= ' Пароль: '.$password;

        $this->smsUslugi->query('+7'.$userConfirmation->getUser()->getPhoneMobile(), $text);

        $userConfirmation->setLastSent(new \DateTime());
        $this->em->persist($userConfirmation);
        $this->em->flush();

        return true;
    }
}
