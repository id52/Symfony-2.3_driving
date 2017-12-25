<?php

namespace My\AppBundle\Controller\Admin;

use My\AppBundle\Entity\UserStat;
use My\PaymentBundle\Entity\Log as PaymentLog;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Validator\Constraints as Assert;

class UserController extends Controller
{
    /** @var $em \Doctrine\ORM\EntityManager */
    public $em;
    /** @var $user \My\AppBundle\Entity\User */
    public $user;
    public $settings = array();
    public $settingsNotifies = array();

    public function addOldUserAction(Request $request)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_MOD_ADD_USER')) {
            throw $this->createNotFoundException();
        }

        $userManager = $this->get('fos_user.user_manager');

        $regionTree = [];
        $regionTreeSource = $this->em->getRepository('AppBundle:Region')->createQueryBuilder('r')
            ->leftJoin('r.places', 'rp')
            ->leftJoin('r.categories_prices', 'cp', 'WITH', 'cp.active = 1')
            ->addSelect('cp')
            ->getQuery()->getResult();

        /** @var $region \My\AppBundle\Entity\Region*/
        foreach ($regionTreeSource as $region) {
            $regionId = $region->getId();
            if (!isset($regionTree[$regionId])) {
                $regionTree[$regionId] = ['name' => $region->getName(), 'cats' => []];
            }

            /** @var $categoryPrice \My\AppBundle\Entity\CategoryPrice */
            foreach ($region->getCategoriesPrices() as $categoryPrice) {
                $cat = $categoryPrice->getCategory();
                $regionTree[$regionId]['cats'][$cat->getId()] = ['name' => $cat->getName()];
            }
        }

        /** @var $user \My\AppBundle\Entity\User */
        $user = $userManager->createUser();
        /** @var $form \Symfony\Component\Form\Form */
        $fb = $this->createFormBuilder($user, [
            'constraints'       => new UniqueEntity([
                'message' => 'Такой Код слушателя уже существует. Введите другой Код слушателя.',
                'fields'  => ['paradox_id'],
                'groups'  => ['paradox'],
            ]),
            'validation_groups' => ['Default', 'paradox'],
        ])
            ->add('last_name')
            ->add('first_name')
            ->add('patronymic')
            ->add('plain_password', 'text', [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 6]),
                ],
                'data' => $this->generatePassword(),
            ])
            ->add('email')
            ->add('category', 'entity', [
                'class'       => 'AppBundle:Category',
                'required'    => true,
                'empty_value' => 'choose_option',
                'constraints' => new Assert\NotBlank(),
            ])
            ->add('region', 'entity', [
                'class'       => 'AppBundle:Region',
                'required'    => true,
                'empty_value' => 'choose_option',
                'constraints' => new Assert\NotBlank(),
            ])
            ->add('paradox_id', 'integer', [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'min'          => 6,
                        'max'          => 6,
                        'exactMessage' => 'Код слушателя должнен содержать 6 символов.',
                    ]),
                ],
            ])
            ->add('phone_mobile', 'text', [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => '#^\d{10}$#',
                        'message' => 'Неверный номер телефона'
                    ]),
                ],
            ])
        ;
        $form = $fb->getForm();

        if ($request->isMethod('post')) {
            $form->handleRequest($request);

            $region = $form->get('region')->getData();

            $categoryField = $form->get('category');
            /** @var $category \My\AppBundle\Entity\Category */
            $category = $categoryField->getData();

            if ($region && $category) {
                if (!isset($regionTree[$region->getId()]['cats'][$category->getId()])) {
                    $categoryField->addError(new FormError('Неверная категория.'));
                }
            }

            if ($form->isValid()) {
                $plainPassword = $form->get('plain_password')->getData();
                $encoder       = new MessageDigestPasswordEncoder();
                $user->setPassword($encoder->encodePassword($plainPassword, $user->getSalt()));
                $user->eraseCredentials();

                $user->setConfirmationToken(null);
                $user->setEnabled(true);
                $user->setModerated(true);
                $user->setOffline(true);
                $user->setIsOld(true);
                $user->addRole('ROLE_USER_FULL_PROFILE');
                $user->addRole('ROLE_USER_PAID2');

                $moderator = $this->em->getRepository('AppBundle:User')->find($this->getUser());
                $moderator->addModeratedUser($user);
                $user->setModerator($moderator);

                $log = new PaymentLog();
                $log->setUser($user);
                $log->setSum(0);

                $services = $this->em->getRepository('AppBundle:Service')->createQueryBuilder('s')
                    ->addSelect('rp.price')
                    ->leftJoin('s.regions_prices', 'rp')
                    ->andWhere('rp.active = :active')->setParameter(':active', true)
                    ->andWhere('s.type != :type')->setParameter(':type', 'site_access')
                    ->andWhere('s.type IS NOT NULL')
                    ->andWhere('rp.region = :region')->setParameter(':region', $form->get('region')->getData())
                    ->getQuery()->execute();

                $sids = [];

                $services_prices_sum = 0;
                foreach ($services as $service) {
                    $services_prices_sum += $service['price'];
                    /** @var $s \My\AppBundle\Entity\Service */
                    $s      = $service[0];
                    $sids[] = $s->getId();
                }

                $comments = [
                    'categories'   => (string)$form->get('category')->getData()->getId(),
                    'services'     => implode(',', $sids),
                    'moderator_id' => $this->user->getId(),
                ];

                $log->setComment(json_encode($comments));
                $log->setPaid(true);

                $userStat = new UserStat();
                $userStat->setUser($user);
                $userStat->setRegBy($userStat::REG_BY_OFFLINE_OLD);
                $userStat->setRegType($userStat::REG_TYPE_PAID_2);
                $userStat->setPay1Type($userStat::PAY_1_TYPE_OFFLINE);
                $userStat->setPay2Type($userStat::PAY_2_TYPE_OFFLINE);

                $this->em->persist($userStat);
                $this->em->persist($log);
                $this->em->persist($user);

                $this->em->flush();

                $this->container->get('app.user_helper')->sendMessages($user, $plainPassword, true);

                $this->get('session')->getFlashBag()->add('success', 'success_added');
                return $this->redirect($this->generateUrl('admin_add_old_user'));
            }
        }

        return $this->render('AppBundle:Admin/User:add_old_user_action.html.twig', array(
            'form'        => $form->createView(),
            'region_tree' => $regionTree,
        ));
    }

    private function generatePassword($length = 8)
    {
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= rand(0, 9);
        }
        return $password;
    }
}
