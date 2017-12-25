<?php

namespace My\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, array('constraints' => new NotBlank()))
            ->add('filial_not_existing', 'checkbox', array('required' => false))
            ->add('discount_1_amount', 'integer', array('attr' => array('class' => 'span1')))
            ->add('discount_1_date_from', 'date', array('years' => range(date('Y')-1, date('Y')+1)))
            ->add('discount_1_date_to', 'date', array('years' => range(date('Y')-1, date('Y')+1)))
            ->add('discount_1_timer_period', 'integer', array('attr' => array('class' => 'span1')))
            ->add('discount_2_first_amount', 'integer', array('attr' => array('class' => 'span1')))
            ->add('discount_2_first_days', 'integer', array('attr' => array('class' => 'span1')))
            ->add('discount_2_second_amount', 'integer', array('attr' => array('class' => 'span1')))
            ->add('discount_2_second_days', 'integer', array('attr' => array('class' => 'span1')))
            ->add('discount_2_between_period_days', 'integer', array('attr' => array('class' => 'span1')))
        ;
    }

    public function getName()
    {
        return 'region';
    }
}
