<?php

namespace My\FOSUserBundle\Bundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
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

    public function testLoginAction()
    {
        $uri = $this->router->generate('fos_user_security_login');
        $crawler = $this->client->request('get', $uri);

        $sblock = $crawler->filter('.content');
        $form = $sblock->filter('[type=submit]')->form();

        // Форма отправляется
        $this->client->submit($form);
        $crawler = $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $sblock = $crawler->filter('.content');

        // Форма выдаёт ошибку, что пользователь не сущестует
        $alert = $sblock->filter('.alert-error');
        $this->assertTrue($alert->count() > 0);

        // Форма с данными отправляется
        $form['_username'] = 'a3@bc.de';
        $this->client->submit($form);
        $crawler = $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $sblock = $crawler->filter('.content');

        // Форма выдаёт ошибку, что пользователь не сущестует
        $alert = $sblock->filter('.alert-error');
        $this->assertTrue($alert->count() > 0);

        // Форма с данными отправляется
        $form['_username'] = 'a2@bc.de';
        $this->client->submit($form);
        $crawler = $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $sblock = $crawler->filter('.content');

        // Форма выдаёт ошибку, что пароль не верный
        $alert = $sblock->filter('.alert-error');
        $this->assertTrue($alert->count() > 0);

        $form['_username'] = 'a2@bc.de';
        $form['_password'] = 'FGHIJK';
        $this->client->submit($form);

        // Успешный вход
        $uri = $this->router->generate('homepage', array(), true);
        $this->assertTrue($this->client->getResponse()->isRedirect($uri));
    }
}
