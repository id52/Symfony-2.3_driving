<?php

namespace My\AppBundle\Form\Type;

use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Innocead\CaptchaBundle\Validator\Constraints\Captcha;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('last_name')
            ->add('first_name')
            ->add('patronymic')
            ->add('phone_mobile', 'text', array(
                'required'    => false,
                'constraints' => array(),
            ))
            ->add('email')
            ->add('plainPassword', 'repeated', array(
                'type'            => 'password',
                'invalid_message' => 'passwords_not_match',
            ))
            ->add('region', null, array('empty_value' => 'choose_option'))
            ->add('category', 'entity', array(
                'empty_value' => 'choose_option',
                'class'       => 'AppBundle:Category',
            ))
        ;
    }

    public function getName()
    {
        return 'app_registration';
    }
}
