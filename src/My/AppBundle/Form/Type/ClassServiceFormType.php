<?php

namespace My\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ClassServiceFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

    public function getName()
    {
        return 'class_service';
    }
}
