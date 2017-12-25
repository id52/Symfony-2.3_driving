<?php

namespace My\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SimpleProfileFormType extends AbstractType
{
    protected $password = '';

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $paids = [
            'nopaid' => 'paids.nopaid',
            'paid_1' => 'paids.paid_1',
            'paid_2' => 'paids.paid_2',
        ];

        $builder
            ->add('last_name')
            ->add('first_name')
            ->add('patronymic')
            ->add('email', 'email')
            ->add('plain_password', 'text', [
                'data' => $this->password,
                'constraints' => [
                    new NotBlank(['groups' => 'profile']),
                    new Length(['min' => 6, 'groups' => 'simple_profile']),
                ],
            ])
            ->add('phone_mobile', null, ['help' => 'profile_phone_mobile_help'])
            ->add('region', 'entity', [
                'class'       => 'AppBundle:Region',
                'required'    => true,
                'empty_value' => 'choose_option',
            ])
            ->add('category', 'entity', [
                'class'       => 'AppBundle:Category',
                'required'    => true,
                'empty_value' => 'choose_option',
                'constraints' => new NotBlank(['groups' => 'simple_profile'])
            ])
            ->add('paids', 'choice', [
                'mapped'      => false,
                'required'    => false,
                'empty_value' => 'choose_option',
                'choices'     => $paids,
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('validation_groups' => array('simple_profile', 'Registration')));
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'simple_profile';
    }
}
