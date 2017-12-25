<?php

namespace My\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class PromoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var  $entity \My\AppBundle\Entity\Promo */
            $entity = $event->getData();
            $autoCreate = $entity->getAutoCreate();
            $form = $event->getForm();

            $form
                ->add('name', 'text', array(
                    'disabled'  => $autoCreate,
                ))
                ->add('used_from', 'datetime', array(
                    'years'     => range(2014, date('Y')),
                    'disabled'  => $autoCreate,
                ))
                ->add('used_to', 'datetime', array(
                    'years'     => range(date('Y'), date('Y') + 5),
                    'disabled'  => $autoCreate,
                    ))
                ->add('active', 'checkbox', array(
                    'required' => false,
                    'disabled' => $autoCreate,
                ))
                ->add('restricted', 'choice', array('choices' => array(
                    'keys'      => 'promo_restricted_by_keys',
                    'users'     => 'promo_restricted_by_users',
                ),
                    'disabled'  => $autoCreate,
                ))
                ->add('maxUsers', 'text', array(
                    'help'      => 'promo_maxUsersHelp',
                    'disabled'  => $autoCreate,
                ))
                ->add('generateKeysCount', 'integer', array(
                    'mapped'   => false,
                    'required' => false,
                    'help'     => 'promo_generateKeysCountHelp',
                    'disabled' => $autoCreate,
                ))
                ->add('discount', 'integer', array(
                    'mapped' => false,
                    'disabled' => $autoCreate,
                ))
                ->add('type', 'choice', array(
                    'mapped'      => false,
                    'required'    => true,
                    'empty_value' => 'service_types.empty_not_required',
                    'choices'     => array(
                        'site_access' => 'Доступ к теоретическому курсу',
                        'training'    => 'Пакет регистрации в ГИБДД',
                    ),
                    'disabled'    => $autoCreate,
                ))
            ;
        });
    }

    public function getName()
    {
        return 'promo';
    }
}
