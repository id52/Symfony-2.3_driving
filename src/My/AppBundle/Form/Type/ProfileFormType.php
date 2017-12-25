<?php

namespace My\AppBundle\Form\Type;

use My\AppBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var $user \My\AppBundle\Entity\User */
        $user = $options['data'];

        $builder
            ->add('last_name')
            ->add('first_name')
            ->add('patronymic')
            ->add('sex', 'choice', array(
                'expanded' => true,
                'choices' => array(
                    'male'    => 'male',
                    'female'  => 'female',
                )
            ))
            ->add('birthday', 'birthday', array(
                'years' => range(1930, date('Y')),
                'data'  => ($user && $user->getBirthday()) ? $user->getBirthday() : new \DateTime('01-01-1995'),
            ))
            ->add('birth_country', null, array('required' => true))
            ->add('birth_region', null, array('required' => true))
            ->add('birth_city', null, array('required' => true))
            ->add('foreign_passport', null, array('required' => false))
            ->add('foreign_passport_number')
            ->add('passport_number', 'passport_number', array('help' => 'profile_passport_number_help'))
            ->add('passport_rovd')
            ->add('passport_rovd_number')
            ->add('passport_rovd_date', null, array(
                'years' => range(1990, date('Y')),
                'data'  => ($user && $user->getPassportRovdDate())
                    ? $user->getPassportRovdDate() : new \DateTime('01-01-2010'),
            ))
            ->add('not_registration', null, array('required' => false))
            ->add('registration_country')
            ->add('registration_region')
            ->add('registration_city')
            ->add('registration_street')
            ->add('registration_house')
            ->add('registration_stroenie')
            ->add('registration_korpus')
            ->add('registration_apartament')
            ->add('place_country')
            ->add('place_region')
            ->add('place_city')
            ->add('place_street')
            ->add('place_house')
            ->add('place_stroenie')
            ->add('place_korpus')
            ->add('place_apartament')
            ->add('work_place')
            ->add('work_position')
            ->add('phone_mobile', null, array('help' => 'profile_phone_mobile_help'))
        ;

        if (in_array('user_edit', $options['validation_groups'])) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($user) {
                /** @var  $entity \My\AppBundle\Entity\User */
                $form = $event->getForm();

                $notFilial = false;
                $region = $user->getRegion();
                if ($region) {
                    $notFilial = $region->getFilialNotExisting();
                }

                if ($user->hasRole('ROLE_USER_PAID') && !$user->hasRole('ROLE_USER_PAID2')) {
                    if (!$user->getAgreement()) {
                        $form->add('agreement', 'checkbox', array(
                            'constraints' => new NotBlank(),
                        ));
                    }
                    if (!$user->getPrivacy()) {
                        $form->add('privacy', 'checkbox', array(
                            'constraints' => new NotBlank(),
                        ));
                    }
                } elseif ($user->hasRole('ROLE_USER_PAID') && $user->hasRole('ROLE_USER_PAID2')) {
                    if ($notFilial) {
                        $form->add('treaty_on_non_disclosure', 'checkbox', array(
                            'constraints' => new NotBlank(),
                        ));
                    }
                    if (!$user->getAgreement()) {
                        $form->add('agreement', 'checkbox', array(
                            'constraints' => new NotBlank(),
                        ));
                    }
                    if (!$user->getPrivacy()) {
                        $form->add('privacy', 'checkbox', array(
                            'constraints' => new NotBlank(),
                        ));
                    }
                    if (!$user->getTermsAndConditions()) {
                        $form->add('terms_and_conditions', 'checkbox', array(
                            'constraints' => new NotBlank(),
                        ));
                    }
                }
            });
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            /** @var  $user User*/
            $userData = $event->getData();

            if (isset($userData['phone_mobile'])) {
                $mobile = $userData['phone_mobile'];
                $mobile = str_replace(['(', ')', ' ', '-'], '', $mobile);

                $userData['phone_mobile'] = $mobile;
                $event->setData($userData);
            }
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('validation_groups' => array('profile')));
    }

    public function getName()
    {
        return 'profile';
    }
}
