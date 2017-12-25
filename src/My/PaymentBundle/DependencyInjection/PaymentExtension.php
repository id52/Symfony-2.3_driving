<?php

namespace My\PaymentBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PaymentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $robokassa_url = empty($config['robokassa_url'])
            ? $container->getParameter('robokassa_url') : $config['robokassa_url'];
        $container->setParameter('payment.params', array(
            'robokassa' => array(
                'login' => $container->getParameter('robokassa_login'),
                'pass1' => $container->getParameter('robokassa_pass1'),
                'pass2' => $container->getParameter('robokassa_pass2'),
                'url'   => $robokassa_url,
            ),
            'psb'       => array(
                'key'            => $container->getParameter('psb_key'),
                'terminal_id'    => $container->getParameter('psb_terminal_id'),
                'merchant_id'    => $container->getParameter('psb_merchant_id'),
                'merchant_name'  => $container->getParameter('psb_merchant_name'),
                'merchant_email' => $container->getParameter('psb_merchant_email'),
                'url'            => $container->getParameter('psb_url'),
            ),
        ));
    }
}
