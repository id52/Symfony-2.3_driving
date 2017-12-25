<?php

namespace My\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class DrivingTicketFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('drive_date', null, array(
                'label'         => 'Дата и время вождения',
                'required'      => false,
            ))
            ->add('comment', 'textarea', array(
                'label'         => 'Коментарии',
                'required'      => false,
            ))
            ->add('rating', 'integer', array(
                'label'         => 'Оценка',
                'constraints'   => array(new Assert\GreaterThan(0)),
                'required'      => false,
                'attr'          => array(
                    'class' => 'span1',
                ),
            ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

    public function getName()
    {
        return 'driving_ticket';
    }
}
