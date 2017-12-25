<?php

namespace My\FOSUserBundle\Bundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResettingControllerTest extends WebTestCase
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

    public function testResetAction()
    {
        $uri = $this->router->generate('fos_user_resetting_request');
        $crawler = $this->client->request('get', $uri);

        $sblock = $crawler->filter('.content');
        $form = $sblock->filter('[type=submit]')->form();

        // Форма отправляется
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $sblock = $crawler->filter('.content');

        // Форма выдаёт ошибку, что пользователь не сущестует
        $alert = $sblock->filter('.alert-error');
        $this->assertTrue($alert->count() > 0);

        $form['username'] = 'a2@bc.de';
        $this->client->submit($form);

        // Письмо получено
        $this->client->enableProfiler();
        /** @var $mailCollector \Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector */
        $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');
        $this->assertEquals(1, $mailCollector->getMessageCount());

        // Переход на страницу уведомления о том, что письмо отправлено
        $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // Подтверждение смены пароля
        $collectedMessages = $mailCollector->getMessages();
        /** @var $message \Swift_Message */
        $message = $collectedMessages[0];
        $uri = substr($this->router->generate('fos_user_resetting_reset', array('token' => '_'), true), 0, -1);
        preg_match('#'.$uri.'[^/]+#', $message->getBody(), $matches);
        $crawler = $this->client->request('get', $matches[0]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $sblock = $crawler->filter('.content');
        $form = $sblock->filter('[type=submit]')->form();

        // Форма отправляется
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $sblock = $crawler->filter('.content');

        // Форма выдаёт ошибки
        $this->assertTrue($sblock->filter('span.help-block.text-error')->count() > 0);

        // В поле «Новый пароль» значение не должно быть пустым
        $field = $sblock->filter('#fos_user_resetting_form_new_first');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() > 0);

        // Форма с данными отправляется
        $form['fos_user_resetting_form[new][first]'] = 'E';
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $sblock = $crawler->filter('.content');

        // Форма выдаёт ошибки
        $this->assertTrue($sblock->filter('span.help-block.text-error')->count() > 0);

        // В полях «Новый пароль» и «Повторите пароль» пароли не совпадают
        $field = $sblock->filter('#fos_user_resetting_form_new_first');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() > 0);

        // Форма с данными отправляется
        $form['fos_user_resetting_form[new][first]'] = 'F';
        $form['fos_user_resetting_form[new][second]'] = 'F';
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $sblock = $crawler->filter('.content');

        // Форма выдаёт ошибки
        $this->assertTrue($sblock->filter('span.help-block.text-error')->count() > 0);

        // В поле «Новый пароль» значение слишком короткое
        $field = $sblock->filter('#fos_user_resetting_form_new_first');
        $errors = $field->siblings()->filter('span.help-block.text-error');
        $this->assertTrue($errors->count() > 0);

        $form['fos_user_resetting_form[new][first]'] = 'FGHIJK';
        $form['fos_user_resetting_form[new][second]'] = 'FGHIJK';
        $this->client->submit($form);

        // Успешная смена пароля
        $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }
}
