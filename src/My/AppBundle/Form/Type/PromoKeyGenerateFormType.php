<?php

namespace My\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\NotBlank;

class PromoKeyGenerateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('count', 'integer', array(
                'required'    => true,
                'help'        => 'promo_keys_generate_count_help',
                'constraints' => array(
                    new NotBlank(),
                    new Range(array('min' => 0, 'max' => 1000)),
                ),
            ))
            ->add('promo', 'entity', array(
                'class'    => 'AppBundle:Promo',
                'property' => 'name',
            ))
            ->add('discount', 'integer', array('data' => 0))
            ->add('type', 'choice', array(
                'required'    => true,
                'empty_value' => 'service_types.empty_not_required',
                'choices'     => array(
                    'site_access' => 'Доступ к теоретическому курсу',
                    'training'    => 'Пакет регистрации в ГИБДД',
                ),
            ))
            ->add('active', 'checkbox', array(
                'data'     => true,
                'required' => false,
            ))
        ;
    }

    public function getName()
    {
        return 'promo_key';
    }
}
