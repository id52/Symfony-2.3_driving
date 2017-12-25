<?php

namespace My\SmsUslugiRuBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class SmsUslugiRuExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $url = empty($config['url']) ? $container->getParameter('sms_uslugi_url') : $config['url'];
        $container->setParameter('sms_uslugi_ru.params', array(
            'login' => $container->getParameter('sms_uslugi_login'),
            'pass'  => $container->getParameter('sms_uslugi_pass'),
            'url'   => $url,
        ));

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
