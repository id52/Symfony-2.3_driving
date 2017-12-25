<?php

namespace My\AppBundle\EventListener;

use My\AppBundle\Exception\AppResponseException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class InitControllerListener
{
    /** @var $container \Symfony\Component\DependencyInjection\Container */
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        if (!is_array($controller)) {
            return;
        }
        $controller = $controller[0];

        $em = $this->container->get('doctrine.orm.entity_manager');
        $twig = $this->container->get('twig');
        $csrfProvider = $this->container->get('form.csrf_provider');

        $user = null;
        $token = $this->container->get('security.context')->getToken();
        if (!is_null($token) && is_object($token->getUser())) {
            $user = $token->getUser();
        }

//        if (!$user) {
//            //add list of categories for regions and names of categories
            $discount_data = array();
//            $regions = $em->getRepository('AppBundle:Region')->createQueryBuilder('r')
//                ->leftJoin('r.categories_prices', 'cp', 'WITH', 'cp.active = :active')
//                ->setParameter(':active', true)
//                ->addSelect('cp')
//                ->leftJoin('cp.category', 'c')->addSelect('c')
//                ->leftJoin('c.image', 'i')->addSelect('i')
//                ->getQuery()->execute();
//
//            $start_date = new \DateTime('2014-01-01');
//            $today = new \DateTime('today');
//            foreach ($regions as $region) { /** @var $region \My\AppBundle\Entity\Region */
//                $region_data = array(
//                    'name'       => $region->getName(),
//                    'categories' => array(),
//                );
//                foreach ($region->getCategoriesPrices() as $price) {
//                    /** @var $price \My\AppBundle\Entity\CategoryPrice */
//
//                    $category_data = array(
//                        'name'  => $price->getCategory()->getName(),
//                        'image' => $price->getCategory()->getImage()
//                            ? $price->getCategory()->getImage()->getWebPath() : '',
//                        'price' => $price->getPrice(),
//                    );
//                    if ($region->getDiscount1Amount() > 0) {
//                        if ($region->getDiscount1DateFrom() <= $today && $today <= $region->getDiscount1DateTo()) {
//                            /** @var $final_date \DateTime */
//                            $final_date = $region->getDiscount1DateTo();
//                            $category_data['final_date'] = $final_date->format('r');
//                        } elseif ($region->getDiscount1TimerPeriod() > 0) {
//                            $final_date = clone $today;
//                            $days = $start_date->diff($final_date)->format('%a');
//                            $days_before_final = $region->getDiscount1TimerPeriod()
//                                - $days % $region->getDiscount1TimerPeriod();
//                            $category_data['final_date'] = $final_date
//                                ->add(new \DateInterval('P'.$days_before_final.'D'))
//                                ->format('r');
//                        }
//                        if (isset($category_data['final_date'])) {
//                            $category_data['discount'] = $region->getDiscount1Amount();
//                        }
//                    }
//                    $region_data['categories'][$price->getCategory()->getId()] = $category_data;
//                }
//                if (!empty($region_data['categories'])) {
//                    $discount_data[$region->getId()] = $region_data;
//                }
//            }
            $twig->addGlobal('discount_data', $discount_data);
//
//            //auth scrf token
            $twig->addGlobal('csrf_token_auth', $csrfProvider->generateCsrfToken('authenticate'));
//            $twig->addGlobal('csrf_token_reg', $csrfProvider->generateCsrfToken('registration'));
//        }

        //add articles link to menu
        /** @var $articleRepo \Doctrine\ORM\EntityRepository */
        $articleRepo = $em->getRepository('AppBundle:Article');
        $menu_items = $articleRepo->createQueryBuilder('a')
            ->addOrderBy('a.position', 'ASC')
            ->getQuery()->getResult();
        $twig->addGlobal('menu_items', $menu_items);

        /** @var $settings_repository \My\AppBundle\Repository\SettingRepository */
        $settings_repository = $em->getRepository('AppBundle:Setting');
        $settings = $settings_repository->getAllData();
        $twig->addGlobal('settings', $settings);
        $twig->addGlobal('settings_notifies', $settings);

        //add contact and social info
        $twig->addGlobal('contact_email', $settings['contact_email']);
        $twig->addGlobal('contact_phone', $settings['contact_phone']);
        $twig->addGlobal('social_vk', $settings['social_vk']);
        $twig->addGlobal('social_facebook', $settings['social_facebook']);
        $twig->addGlobal('social_twitter', $settings['social_twitter']);

        //get count of unread support dialogs
        if ($user) {
            /** @var $supportDialogRepo \My\AppBundle\Repository\SupportDialogRepository */
            $supportDialogRepo = $em->getRepository('AppBundle:SupportDialog');
            $dialogs_count = $supportDialogRepo->getCountOfUserUnreadDialogs($user);
            $twig->addGlobal('support_unread_dialogs_count', $dialogs_count);
        } else {
            $twig->addGlobal('support_unread_dialogs_count', 0);
        }

        if ($user) {
            $oweStage = $em->getRepository('AppBundle:OweStage')
                ->findOneBy(['user' => $user->getId()], ['end' => 'DESC']);
            $twig->addGlobal('last_stage', $oweStage);
        }

        $controller->em = $em;
        $controller->user = $user;
        $controller->settingsNotifies = $settings;
        $controller->settings = $settings;

        if (method_exists($controller, 'init')) {
            call_user_func(array($controller, 'init'));
        }
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        if ($exception instanceof AppResponseException) {
            $event->setResponse($exception->getResponse());
        }
    }
}
