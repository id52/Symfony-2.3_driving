<?php

namespace My\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ServiceFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $display_choices = [
            'main' => 'service_displays.main',
            'expr' => 'service_displays.expr',
            'comb' => 'service_displays.comb',
        ];

        $builder
            ->add('name', null, array('constraints' => new NotBlank()))
            ->add('type', 'choice', array(
                'required'    => false,
                'empty_value' => 'service_types.empty_not_required',
                'choices'     => array('training' => 'service_types.training'),
            ))
            ->add('display', 'choice', array(
                'required'    => false,
                'empty_value' => '- Выберите отображение -',
                'choices'     => $display_choices,
            ));
        ;

    }

    public function getName()
    {
        return 'service';
    }
}
