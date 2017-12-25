<?php

namespace My\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;

class AttemptsPackageFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'constraints' => new NotBlank(),
            ))
            ->add('number_of_attempts', 'number', array(
                'constraints' => new Range(array('min' => 0)),
                'attr'        => array('class' => 'span1'),
            ))
            ->add('cost', 'money', array(
                'constraints' => new Range(array('min' => 0)),
                'currency'    => 'RUB',
                'attr'        => array('class' => 'span1'),
            ))
        ;
    }

    public function getName()
    {
        return 'attempts_package';
    }
}
