<?php

namespace My\AppBundle\Form\Type;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class DrivingConditionsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var  $entity \My\AppBundle\Entity\DrivingConditions */
            $entity = $event->getData();
            $form = $event->getForm();
            count($entity->getPackages()) > 0 ? $disabled = true : $disabled = false;

            $form
                ->add('active', 'checkbox', array(
                    'required'      => false,
                    ))
                ->add('name', 'text', array(
                ))
                ->add('cond_code', 'text', array(
                    'constraints'   => array(new Assert\NotBlank()),
                ))
                ->add('class_service', 'entity', array(
                    'class'         => 'My\AppBundle\Entity\ClassService',
                    'constraints'   => array(new Assert\NotBlank()),
                    'disabled'      => $disabled,
                    'empty_value'   => '-- Выберите --',
                ))
                ->add('with_at', 'checkbox', array(
                    'required'      => false,
                    'disabled'      => $disabled,
                ))
                ->add('is_primary', 'choice', array(
                    'disabled'      => $disabled,
                    'empty_value'   => '-- Выберите --',
                    'choices'       => array(
                        '1' => 'Основные',
                        '0' => 'Дополнительные',
                    ),
                ))
                ->add('description', 'textarea', array(
                    'constraints'   => array(new Assert\NotBlank()),
                    'attr'          => array('class' => 'ckeditor'),
                ))
                ->add('number_tickets', 'integer', array(
                    'constraints'   => array(new Assert\GreaterThan(0)),
                    'disabled'      => $disabled,
                    'attr'          => array(
                        'class' => 'span1',
                    ),
                ));
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'constraints'   => new UniqueEntity(array(
                'fields'    => 'cond_code',
                'message'   => 'Такой код уже есть в базе данных'
            )),
        ));
    }

    public function getName()
    {
        return 'driving_conditions';
    }
}
