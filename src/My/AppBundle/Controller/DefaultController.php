<?php

namespace My\AppBundle\Controller;

use My\AppBundle\Entity\TriedEnters;
//use My\AppBundle\Entity\UserStat;
use My\AppBundle\Util\Time;
use My\PaymentBundle\Entity\Log as PaymentLog;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Validator\Constraints\NotBlank;

class DefaultController extends Controller
{
    /** @var $em \Doctrine\ORM\EntityManager */
    public $em;
    /** @var $user \My\AppBundle\Entity\User */
    public $user;

    public $settings = array();
    public $settingsNotifies = array();

    public function entranceByLinkAction($token)
    {
        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);

        if (null === $user) {
            return $this->redirect($this->generateUrl('homepage'));
        }

        $user->setConfirmationToken(null);
        $user->setLastLogin(new \DateTime());

        $this->container->get('fos_user.user_manager')->updateUser($user);
        $response = new RedirectResponse($this->container->get('router')->generate('fos_user_profile_show'));
        $this->container->get('fos_user.security.login_manager')->loginUser(
            $this->container->getParameter('fos_user.firewall_name'),
            $user,
            $response
        );

        return $response;
    }

    public function articleAction($id)
    {
        $article = $this->em->getRepository('AppBundle:Article')->findOneBy(array('url' => $id));

        if (!$article) {
            throw $this->createNotFoundException('Статья не найдена');
        }

        if ($article->getPrivate() && !$this->get('security.context')->isGranted('ROLE_USER')) {
            throw new AccessDeniedHttpException;
        }

        return $this->render('AppBundle:Default:article.html.twig', array(
            'article' => $article,
        ));
    }

    public function termsAndConditionsAction()
    {
        return $this->render('AppBundle:My:terms_and_conditions_agreement.html.twig');
    }

    public function termsAndConditionsEducationServiceAction()
    {
        $user         = $this->getUser();
        $userCategory = strtolower($user->getCategory()->getName());
        $view         = null;

        if ($user->getRegion()->getFilialNotExisting()) {
            if (in_array($userCategory, ['a', 'b'])) {
                $view = '@App/My/terms_and_conditions_education_service_blank_vir_sc_cat_'.$userCategory.'.html.twig';
            }
        } elseif (in_array($userCategory, ['a', 'b'])) {
            $view = '@App/My/terms_and_conditions_education_service_blank_cat_'.$userCategory.'.html.twig';
        }

        return $this->render($view, [
            'user' => $user,
        ]);
    }

    public function treatyOnNonDisclosureAction()
    {
        $notFilial = false;
        $region = $this->user->getRegion();
        if ($region) {
            $notFilial = $region->getFilialNotExisting();
        }

        if (!$notFilial) {
            throw $this->createNotFoundException();
        }

        return $this->render('@App/My/treaty_on_non_disclosure.html.twig', array());
    }

    public function printAction()
    {
        $region = $this->user->getRegion();
        if ($region && !$region->getFilialNotExisting()) {
            throw $this->createNotFoundException('Not found page');
        }

        return $this->render('@App/My/agreement.html.twig', array());
    }

    public function checkPromoKeyAction(Request $request)
    {
        $response = array();
        $cntxt = $this->get('security.context');
        if ($cntxt->isGranted('ROLE_USER')) {
            $response = array(
                'success' => false,
                'discount' => 0,
                'message'=>'Такой промокод не существует или уже был использован.',
            );
            $promo_key = $request->get('key');
            $promo_type = $request->get('type');

            if ($promo_key && $promo_type) {
                $data = $this->em->getRepository('AppBundle:PromoKey')->createQueryBuilder('pk')
                    ->leftJoin('pk.logs', 'log', 'WITH', 'log.paid = :lp')->setParameter('lp', true)
                    ->addSelect('COUNT(log) logsCount')
                    ->leftJoin('pk.promo', 'pr')
                    ->andWhere('pk.hash = :pkh')->setParameter('pkh', $promo_key)
                    ->andWhere('pk.active = :pka')->setParameter('pka', true)
                    ->getQuery()->getResult();

                if ($data[0][0]) {
                    /** @var $key \My\AppBundle\Entity\PromoKey */
                    $key = $data[0][0];
                    $promo = $key->getPromo();
                    $logsCount = $data[0]['logsCount'];

                    /** @var  $triedEnters \My\AppBundle\Entity\TriedEnters */
                    $triedEnters = $this->em->getRepository('AppBundle:TriedEnters')->findOneBy(array(
                        'user' => $this->user,
                        'promo_key' => $key,

                    ));
                    if ($promo) {
                        if ($key->getType() == $promo_type) {
                            $promoRestricted = $promo->getRestricted();
                            if ($promoRestricted == 'keys') {
                                if ($logsCount == 0 && $promo->getActive()) {
                                    $message = 'К общей сумме была применена скидка:';
//                                    $message .= ' <strong>'.$key->getDiscount().' руб.</strong>';
                                    $response = array(
                                        'success'  => true,
                                        'discount' => $key->getDiscount(),
                                        'message'  => $message,
                                    );
                                    if (!$triedEnters) {
                                        $this->newTriedEnters($key);
                                    }
                                    $this->em->flush();
                                } else {
                                    $response = array(
                                        'success'  => false,
                                        'discount' => 0,
                                        'message'  => 'Такой промокод не существует или уже был использован',
                                    );
                                }
                            } elseif ($promoRestricted == 'users') {
                                if ($logsCount < $promo->getMaxUsers() && $promo->getActive()) {
                                    $message = 'К общей сумме была применена скидка:';
//                                    $message .= ' <strong>'.$key->getDiscount().' руб.</strong>';
                                    $response = array(
                                        'success'  => true,
                                        'discount' => $key->getDiscount(),
                                        'message'  => $message,
                                    );

                                    if (!$triedEnters) {
                                        $this->newTriedEnters($key);
                                    }
                                    $this->em->persist($key);
                                    $this->em->flush();
                                } else {
                                    $message = 'Такой промокод не существует или уже был использован ';
                                    $response = array(
                                        'success'  => false,
                                        'discount' => 0,
                                        'message'  => $message,
                                    );
                                }
                            }
                        }
                    } else {
                        if ($logsCount == 0 && $key->getValidTo() > new \DateTime()) {
                            $message = 'К общей сумме была применена скидка:';
//                            $message .= ' <strong>'.$key->getDiscount().' руб.</strong>';
                            $response = array(
                                'success'  => true,
                                'discount' => $key->getDiscount(),
                                'message'  => $message,
                            );

                            if (!$triedEnters) {
                                $this->newTriedEnters($key);
                            }
                            $this->em->persist($key);
                            $this->em->flush();
                        } else {
                            $response = array(
                                'success'  => false,
                                'discount' => 0,
                                'message'  => 'Такой промокод не существует или уже был использован',
                            );
                        }
                    }
                }
            }
        }

        return new JsonResponse($response);
    }

    public function indexAction(Request $request)
    {
        $cntxt = $this->get('security.context');
        if ($cntxt->isGranted('ROLE_USER_PAID')) {
            return $this->redirect($this->generateUrl('my_profile'));
        } elseif (!$cntxt->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        $promoData = $this->em->getRepository('AppBundle:SettingAutoCreatePromo')->getAllData();
        $keyAutoCreatePromo = null;
        $activeAutoCreatePromo = null;
        $endAutoCreatePromo = new \DateTime();

        if (count($promoData)) {
            /** @var  $promo \My\AppBundle\Entity\Promo */
            $promo = $this->em->getRepository('AppBundle:Promo')->find($promoData['promoId']);
            if (isset($promo)) {
                /** @var  $promoKey \My\AppBundle\Entity\PromoKey */
                $promoKey = $promo->getKeys()[0];

                $endAutoCreatePromo = $promo->getUsedTo();
                $endAutoCreatePromo->setTime(23, 59, 59);
                $keyAutoCreatePromo = $promoKey->getHash();
                $activeAutoCreatePromo = date('Y-m-d H:i:s') <= $endAutoCreatePromo->format('Y-m-d H:i:s');
            }
        }

        if ($cntxt->isGranted('ROLE_USER')) {
            $session = $this->get('session');

            if ($session->get('payment')) {
                return $this->render('AppBundle:My:choose_payment.html.twig');
            }

            $discount_data = array();
            $regions = $this->em->getRepository('AppBundle:Region')->createQueryBuilder('r')
                ->leftJoin('r.categories_prices', 'cp', 'WITH', 'cp.active = :active')
                ->setParameter(':active', true)
                ->addSelect('cp')
                ->leftJoin('cp.category', 'c')->addSelect('c')
                ->leftJoin('c.image', 'i')->addSelect('i')
                ->getQuery()->execute();

            $start_date = new \DateTime('2014-01-01');
            $today = new \DateTime('today');
            foreach ($regions as $region) { /** @var $region \My\AppBundle\Entity\Region */
                $region_data = array(
                    'name'       => $region->getName(),
                    'categories' => array(),
                );
                foreach ($region->getCategoriesPrices() as $price) {
                    /** @var $price \My\AppBundle\Entity\CategoryPrice */

                    $category = $price->getCategory();
                    $category_data = array(
                        'name'  => $category->getName(),
                        'image' => $category->getImage() ? $category->getImage()->getWebPath() : '',
                        'price' => $price->getPrice(),
                    );
                    if ($region->getDiscount1Amount() > 0) {
                        if ($region->getDiscount1DateFrom() <= $today && $today <= $region->getDiscount1DateTo()) {
                            /** @var $final_date \DateTime */
                            $final_date = $region->getDiscount1DateTo();
                            $dInterval  = $final_date->diff(new \DateTime(), true);

                            $category_data['seconds_left'] = Time::getAllSeconds($dInterval);
                        } elseif ($region->getDiscount1TimerPeriod() > 0) {
                            $final_date        = clone $today;
                            $days              = $start_date->diff($final_date)->format('%a');
                            $days_before_final = $region->getDiscount1TimerPeriod();
                            $days_before_final -= $days % $region->getDiscount1TimerPeriod();
                            $dInterval = new \DateInterval('P'.$days_before_final.'D');

                            $category_data['seconds_left'] = Time::getAllSeconds($dInterval);
                        }
                        if (isset($category_data['seconds_left'])) {
                            $category_data['discount'] = $region->getDiscount1Amount();
                        }
                    }
                    $region_data['categories'][$price->getCategory()->getId()] = $category_data;
                }
                if (!empty($region_data['categories'])) {
                    $discount_data[$region->getId()] = $region_data;
                }
            }

            $region = $this->user->getRegion();
            $category = $this->user->getCategory();
            $categories_prices = $this->em->getRepository('AppBundle:CategoryPrice')->createQueryBuilder('cp')
                ->andWhere('cp.active = :active')->setParameter(':active', true)
                ->andWhere('cp.region = :region')->setParameter(':region', $region)
                ->andWhere('cp.category = :category')->setParameter(':category', $category)
                ->getQuery()->execute();
            $categories_prices_sum = 0;
            foreach ($categories_prices as $price) { /** @var $price \My\AppBundle\Entity\CategoryPrice */
                $categories_prices_sum += $price->getPrice();
            }

            //discount?
            $discount = 0;
            if (isset($discount_data[$region->getId()]['categories'][$category->getId()]['discount'])) {
                $discount = $discount_data[$region->getId()]['categories'][$category->getId()]['discount'];
            }

            $form_factory = $this->container->get('form.factory');
            $form = $form_factory->createNamedBuilder('access')
                ->add('access', 'checkbox', array(
                    'required'    => true,
                    'constraints' => array(new NotBlank()),
                ))
                ->add('privacy', 'checkbox', array(
                    'required'    => true,
                    'constraints' => array(new NotBlank()),
                ))
                ->getForm();
            $form->handleRequest($request);
            if ($request->isMethod('post') && $form->isValid() && !$cntxt->isGranted('ROLE_USER_PAID')) {

                $this->user->setAgreement(true);
                $this->user->setPrivacy(true);
                $this->em->persist($this->user);
                $this->em->flush();

                $sum = max($categories_prices_sum - $discount, 0);
                $categories = array();
                foreach ($categories_prices as $price) { /** @var $price \My\AppBundle\Entity\CategoryPrice */
                    $categories[] = $price->getCategory()->getId();
                }

                //promo?
                $key = null;
                $sumForPaid = 0;
                $promo_key = $request->get('promo_key');
                if ($promo_key) {
                    $data = $this->em->getRepository('AppBundle:PromoKey')->createQueryBuilder('pk')
                        ->leftJoin('pk.logs', 'log', 'WITH', 'log.paid = :lp')->setParameter('lp', true)
                        ->addSelect('COUNT(log) logsCount')
                        ->leftJoin('pk.promo', 'pr')
                        ->andWhere('pk.hash = :pkh')->setParameter('pkh', $promo_key)
                        ->andWhere('pk.active = :pka')->setParameter('pka', true)
                        ->getQuery()->getResult();
                    if ($data[0][0]) {
                        /** @var $key \My\AppBundle\Entity\PromoKey */
                        $key       = $data[0][0];
                        $promo     = $key->getPromo();
                        $logsCount = $data[0]['logsCount'];

                        if ($promo) {
                            if ($promo->getActive() && $key->getType() == 'site_access') {
                                $promoRestricted = $promo->getRestricted();
                                if (($promoRestricted == 'keys' && $logsCount == 0)
                                    || ($promoRestricted == 'users' && $logsCount < $promo->getMaxUsers())
                                ) {
                                    if ($promo->getActive()) {
                                        $sumForPaid = $sum;
                                    }

                                    $sum -= $key->getDiscount();
                                    $sum = max($sum, 0);
                                    $key->setActivated(new \DateTime());
                                }
                            }
                        } elseif ($logsCount == 0 && $key->getValidTo() > new \DateTime()) {
                            $sum -= $key->getDiscount();
                            $sum = max($sum, 0);
                            $key->setActivated(new \DateTime());
                        }
                    }
                }

                $comments = array('categories' => implode(',', $categories));
                if ($key) {
                    $comments['key'] = $key->getHash();
                    if ($keyAutoCreatePromo !== null && $keyAutoCreatePromo == $comments['key']) {
                        $comments['auto_promo'] = $sumForPaid;
                    }
                }

                if ($sum == 0) {
                    $this->em->getRepository('AppBundle:User')->removeTriedsAndReservists($this->getUser());

                    $log = new PaymentLog();
                    $log->setUser($this->user);
                    $log->setSum($sum);
                    $log->setPromoKey($key);
                    $log->setComment(json_encode($comments));
                    $log->setPaid(true);
                    $this->em->persist($log);

                    $this->user->addRole('ROLE_USER_PAID');
                    $this->user->setPayment1Paid(new \DateTime());
                    $this->em->persist($this->user);

                    $userStat = $this->user->getUserStat();
                    if ($userStat) {
                        $pay1Type      = $key->getActivated() ? 'promo' : 'regular';
                        $discount1Type = $discount ? 'first' : null;

                        $userStat->setPay1Type($pay1Type);
                        $userStat->setDiscount1Type($discount1Type);
                        $this->em->persist($userStat);
                    }

                    $this->em->flush();

                    $authManager = $this->get('security.authentication.manager');
                    $token = $cntxt->getToken();
                    $token->setUser($this->user);
                    $token = $authManager->authenticate($token);
                    $cntxt->setToken($token);

                    $this->get('app.notify')->sendAfterFirstPayment($this->user, ($key ? 'promo' : ''));
                    return $this->redirect($this->generateUrl('homepage'));
                }

                $payment = array(
                    'sum'     => $sum,
                    'comment' => $comments,
                );
                if ($key) {
                    $payment['key_id'] = $key->getId();
                }
                $session->set('payment', $payment);
                $session->save();

                return $this->redirect($this->generateUrl('homepage'));
            }

            $sum_discount = max($categories_prices_sum - $discount, 0);
            $text = $this->settingsNotifies['first_payment_text'];
            $text = str_replace('{{sum}}', $categories_prices_sum, $text);
            $text = str_replace('{{sum_discount}}', $sum_discount, $text);
            $discount_text = $this->settingsNotifies['first_payment_promo_discount_text'];
            $discount_text = str_replace('{{sum}}', $categories_prices_sum, $discount_text);
            $discount_text = str_replace('{{sum_discount}}', $sum_discount, $discount_text);

            return $this->render('AppBundle:Default:index.html.twig', array(
                'text'              => $text,
                'discount_text'     => $discount_text,
                'categories_prices' => $categories_prices,
                'sum'               => $categories_prices_sum,
                'discount'          => $discount,
                'discount_data'     => $discount_data,
                'form'              => $form->createView(),
                'autoCreatePromo'   => $activeAutoCreatePromo,
                'keyAutoCreatePromo' => $keyAutoCreatePromo,
            ));
        } else {
            $faqs = $this->em->getRepository('AppBundle:Faq')->createQueryBuilder('f')
                ->addOrderBy('f.position', 'ASC')
                ->getQuery()->getResult();

            if ($activeAutoCreatePromo) {
                $tmpl = 'AppBundle:Default:index_guest_ny_2016.html.twig';
            } else {
                $tmpl = 'AppBundle:Default:index_guest.html.twig';
            }

            return $this->render($tmpl, array(
                'faqs'                  => $faqs,
                'settings_notifies'     => $this->settingsNotifies,
                'activeAutoCreatePromo' => $activeAutoCreatePromo,
                'seconds_left'          => Time::getAllSeconds($endAutoCreatePromo->diff(new \DateTime(), true)),
                'keyAutoCreatePromo'    => $keyAutoCreatePromo,
            ));
        }
    }

    public function unsubscribePayment1PreCheckAction($email)
    {
        return $this->render('AppBundle:Default:unsubscribe_pre_check_payment1.html.twig', array(
            'email' => $email,
        ));
    }

    public function unsubscribePayment1Action($email)
    {
        $user = $this->em->getRepository('AppBundle:User')->findOneBy(array('email' => $email));
        if (!$user) {
            throw $this->createNotFoundException('Not found user for email "'.$email.'"');
        }

        $user->setPayment1PaidNotNotify(true);
        $this->em->persist($user);
        $this->em->flush();

        return $this->render('AppBundle:Default:unsubscribe.html.twig', array(
            'email' => $email,
        ));
    }

    public function unsubscribePayment2PreCheckAction($email)
    {
        return $this->render('AppBundle:Default:unsubscribe_pre_check_payment2.html.twig', array(
            'email' => $email,
        ));
    }

    public function unsubscribePayment2Action($email)
    {
        $user = $this->em->getRepository('AppBundle:User')->findOneBy(array('email' => $email));
        if (!$user) {
            throw $this->createNotFoundException('Not found user for email "'.$email.'"');
        }

        $user->setPayment2PaidNotNotify(true);
        $this->em->persist($user);
        $this->em->flush();

        return $this->render('AppBundle:Default:unsubscribe.html.twig', array(
            'email' => $email,
        ));
    }

    public function unsubscribeMailingPreCheckAction($email)
    {
        $user = $this->em->getRepository('AppBundle:User')->findOneBy(array('email' => $email));
        if (!$user) {
            throw $this->createNotFoundException('Not found user for email "'.$email.'"');
        }

        if (!$user->getMailing()) {
            return $this->redirect($this->generateUrl('homepage'));
        }

        return $this->render('AppBundle:Default:unsubscribe_pre_check_mailing.html.twig', array(
            'email' => $email,
        ));
    }

    public function unsubscribeMailingAction($email)
    {
        $user = $this->em->getRepository('AppBundle:User')->findOneBy(array('email' => $email));
        if (!$user) {
            throw $this->createNotFoundException('Not found user for email "'.$email.'"');
        }

        $user->setMailing(false);
        $this->em->persist($user);
        $this->em->flush();

        return $this->render('AppBundle:Default:unsubscribe.html.twig', array(
            'email' => $email,
        ));
    }

    public function unsubscribeOverduePreCheckAction($email)
    {
        return $this->render('AppBundle:Default:unsubscribe_pre_check_overdue.html.twig', array(
            'email' => $email,
        ));
    }

    // Auto mailing with special promo codes for users who didn't pay
    public function unsubscribeOverdueAction($email)
    {
        $user = $this->em->getRepository('AppBundle:User')->findOneBy(array('email' => $email));
        if (!$user) {
            throw $this->createNotFoundException('Not found user for email "'.$email.'"');
        }

        $user->setOverdueUnsubscribed(true);
        $this->em->persist($user);
        $this->em->flush();

        return $this->render('AppBundle:Default:unsubscribe.html.twig', array(
            'email' => $email,
        ));
    }

    public function payAction($type)
    {
        $cntxt = $this->get('security.context');
        if (!$cntxt->isGranted('ROLE_USER')) {
            throw $this->createNotFoundException();
        }

        if (!in_array($type, array('psb', 'robokassa'))) {
            throw $this->createNotFoundException();
        }

        $session = $this->get('session');
        $payment = $session->get('payment');
        $paymentOweStage = $session->get('paid_owe_stage');
        $session->remove('paid_owe_error');

        if ((!$payment || !isset($payment['sum']) || !isset($payment['comment'])) && !$paymentOweStage) {
            throw $this->createNotFoundException();
        }
        $session->remove('payment');

        if (!$paymentOweStage) {
            $this->em->getRepository('AppBundle:User')->removeTriedsAndReservists($this->getUser(), true);
        }

        $log = new PaymentLog();
        $log->setUser($this->user);
        $log->setSum($paymentOweStage['sum'] ? $paymentOweStage['sum'] : $payment['sum']);
        $log->setSType($type);
        if ($paymentOweStage) {
            $log->setComment(json_encode(['owe_stage' => true]));
        } else {
            $log->setComment(json_encode($payment['comment']));
            if (isset($payment['key_id'])) {
                $key = $this->em->find('AppBundle:PromoKey', $payment['key_id']);
                if ($key) {
                    $log->setPromoKey($key);
                }
            }

            if (isset($payment['comment']['drive_condition'])) {
                $drvCondition = $payment['comment']['drive_condition'];
                $drvCondition = $this->em->find('AppBundle:DrivingConditions', $drvCondition);

                $packages = $this->em->getRepository('AppBundle:DrivingPackage')
                    ->getNotSaleAndNotRezervPackages($drvCondition, $this->user);

                if ($packages) {
                    $package = $packages[0];
                }
                /** @var $package \My\AppBundle\Entity\DrivingPackage */
                $package->setUser($this->user);
                $this->user->addPackage($package);
                $package->setRezervAt(new \DateTime());

                $log->setPackage($package);

                $this->em->persist($package);
                $this->em->persist($this->user);
            }
        }
        $this->em->persist($log);
        $this->em->flush();

        $session->remove('paid_owe_stage');
        switch ($type) {
            case 'psb':
                return $this->redirect($this->generateUrl('psb_query', array(
                    'id'  => $log->getId(),
                    'uid' => $this->user->getId()
                )));
            case 'robokassa':
                return $this->forward('PaymentBundle:Robokassa:query', array(
                    'id'  => $log->getId(),
                    'uid' => $this->user->getId(),
                    'sum' => $payment['sum'] ? $payment['sum'] : $paymentOweStage['sum'],
                ));
            default:
                throw $this->createNotFoundException();
        }
    }

    protected function newTriedEnters($key)
    {
        $this->em->getRepository('AppBundle:User')->removeTriedsAndReservists($this->getUser());

        $triedEnters = new TriedEnters();
        $triedEnters->setUser($this->user);
        $triedEnters->setPromoKey($key);
        $this->em->persist($triedEnters);
        $this->user->setHurryIsSend(false);
        $this->em->persist($this->user);
    }
}
