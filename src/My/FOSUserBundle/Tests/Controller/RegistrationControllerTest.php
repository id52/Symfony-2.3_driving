<?php

namespace My\FOSUserBundle\Bundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    /** @var \Symfony\Bundle\FrameworkBundle\Client */
    private $client = null;
    /** @var \Symfony\Bundle\FrameworkBundle\Routing\Router */
    private $router = null;

    public function setUp()
    {
        $this->client = static::createClient();
        $this->router = $this->client->getContainer()->get('router');
    }

    public function testRegisterAction()
    {
        $uri = $this->router->generate('fos_user_registration_register');
        $crawler = $this->client->request('get', $uri);

        $sblock = $crawler->filter('.content');
        $form = $sblock->filter('[type=submit]')->form();

        // Форма отправляется
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $sblock = $crawler->filter('.content');

        // Форма выдаёт ошибки
        $this->assertTrue($sblock->filter('span.help-block.text-error')->count() > 0);

        // В поле «Фамилия» значение не должно быть пустым
        $field = $sblock->filter('#fos_user_registration_form_last_name');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() > 0);

        // В поле «Имя» значение не должно быть пустым
        $field = $sblock->filter('#fos_user_registration_form_first_name');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() > 0);

        // В поле «Отчество» значение не должно быть пустым
        $field = $sblock->filter('#fos_user_registration_form_patronymic');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() > 0);

        // В поле «E-mail» значение не должно быть пустым
        $field = $sblock->filter('#fos_user_registration_form_email');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() > 0);

        // В поле «Пароль» значение не должно быть пустым
        $field = $sblock->filter('#fos_user_registration_form_plainPassword_first');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() > 0);

        // В поле «Регион» значение не должно быть пустым
        $field = $sblock->filter('#fos_user_registration_form_region');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() > 0);

        // Форма с данными отправляется
        $form['fos_user_registration_form[last_name]'] = 'A';
        $form['fos_user_registration_form[first_name]'] = 'B';
        $form['fos_user_registration_form[patronymic]'] = 'C';
        $form['fos_user_registration_form[email]'] = 'D';
        $form['fos_user_registration_form[plainPassword][first]'] = 'E';
        $form['fos_user_registration_form[region]'] = 1;
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $sblock = $crawler->filter('.content');

        // Форма выдаёт ошибки
        $this->assertTrue($sblock->filter('span.help-block.text-error')->count() > 0);

        // В поле «Фамилия» ошибок нет
        $field = $sblock->filter('#fos_user_registration_form_last_name');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() == 0);

        // В поле «Имя» ошибок нет
        $field = $sblock->filter('#fos_user_registration_form_first_name');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() == 0);

        // В поле «Отчество» ошибок нет
        $field = $sblock->filter('#fos_user_registration_form_patronymic');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() == 0);

        // В поле «E-mail» значение адреса электронной почты недопустимо
        $field = $sblock->filter('#fos_user_registration_form_email');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() > 0);

        // В полях «Пароль» и «Повторите пароль» пароли не совпадают
        $field = $sblock->filter('#fos_user_registration_form_plainPassword_first');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() > 0);

        // В поле «Регион» ошибок нет
        $field = $sblock->filter('#fos_user_registration_form_region');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() == 0);

        // Форма с данными отправляется
        $form['fos_user_registration_form[email]'] = 'a@bc.de';
        $form['fos_user_registration_form[plainPassword][first]'] = 'F';
        $form['fos_user_registration_form[plainPassword][second]'] = 'F';
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $sblock = $crawler->filter('.content');

        // Форма выдаёт ошибки
        $this->assertTrue($sblock->filter('span.help-block.text-error')->count() > 0);

        // В поле «E-mail» ошибок нет
        $field = $sblock->filter('#fos_user_registration_form_email');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() == 0);

        // В поле «Пароль» значение слишком короткое
        $field = $sblock->filter('#fos_user_registration_form_plainPassword_first');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() > 0);

        $form['fos_user_registration_form[plainPassword][first]'] = 'FGHIJK';
        $form['fos_user_registration_form[plainPassword][second]'] = 'FGHIJK';
        $this->client->submit($form);

        // Письмо получено
        $this->client->enableProfiler();
        /** @var $mailCollector \Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector */
        $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');
        $this->assertEquals(1, $mailCollector->getMessageCount());

        // Переход на страницу уведомления об успешной регистрации
        $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->back();

        // Форма с данными отправляется
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $sblock = $crawler->filter('.content');

        // Форма выдаёт ошибки
        $this->assertTrue($sblock->filter('span.help-block.text-error')->count() > 0);

        // В поле «E-mail» значение уже используется
        $field = $sblock->filter('#fos_user_registration_form_email');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() > 0);

        // Подтверждение регистрации переходом по ссылке
        $collectedMessages = $mailCollector->getMessages();
        /** @var $message \Swift_Message */
        $message = $collectedMessages[0];
        $uri = substr($this->router->generate('fos_user_registration_confirm', array('token' => '_'), true), 0, -1);
        preg_match('#'.$uri.'[^/]+#', $message->getBody(), $matches);
        $this->client->request('get', $matches[0]);
        $crawler = $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // Переход по ссылке «Продолжить»
        $sblock = $crawler->filter('.content');
        $link = $sblock->filter('.go_nav a')->link();
        $this->client->click($link);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // Выход
        $uri = $this->router->generate('fos_user_security_logout');
        $this->client->request('get', $uri);
        $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testResendAction()
    {
        $uri = $this->router->generate('fos_user_registration_register');
        $crawler = $this->client->request('get', $uri);

        $sblock = $crawler->filter('.content');
        $form = $sblock->filter('[type=submit]')->form();

        $form['fos_user_registration_form[last_name]'] = 'A2';
        $form['fos_user_registration_form[first_name]'] = 'B';
        $form['fos_user_registration_form[patronymic]'] = 'C';
        $form['fos_user_registration_form[email]'] = 'a2@bc.de';
        $form['fos_user_registration_form[plainPassword][first]'] = 'FGHIJK';
        $form['fos_user_registration_form[plainPassword][second]'] = 'FGHIJK';
        $form['fos_user_registration_form[region]'] = 1;
        $this->client->submit($form);

        // Переход на страницу уведомления об успешной регистрации
        $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $uri = $this->router->generate('fos_user_security_login');
        $crawler = $this->client->request('get', $uri);

        $sblock = $crawler->filter('.content');
        $form = $sblock->filter('[type=submit]')->form();

        // Форма авторизации отправляется
        $form['_username'] = 'a2@bc.de';
        $form['_password'] = 'FGHIJK';
        $this->client->submit($form);
        $crawler = $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $sblock = $crawler->filter('.content');

        // Форма выдаёт ошибку, что аккаунт не активирован
        $alert = $sblock->filter('.alert-error');
        $this->assertTrue($alert->count() > 0);

        // Переход по ссылке повторной отправки письма
        $link = $alert->filter('a')->link();
        $this->client->click($link);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // Письмо получено
        $this->client->enableProfiler();
        /** @var $mailCollector \Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector */
        $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');
        $this->assertEquals(1, $mailCollector->getMessageCount());

        // Подтверждение регистрации переходом по ссылке
        $collectedMessages = $mailCollector->getMessages();
        /** @var $message \Swift_Message */
        $message = $collectedMessages[0];
        $uri = substr($this->router->generate('fos_user_registration_confirm', array('token' => '_'), true), 0, -1);
        preg_match('#'.$uri.'[^/]+#', $message->getBody(), $matches);
        $this->client->request('get', $matches[0]);
        $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }
}
