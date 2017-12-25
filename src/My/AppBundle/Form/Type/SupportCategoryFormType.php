<?php

namespace My\AppBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SupportCategoryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('parent', 'entity', array(
                'class'       => 'AppBundle:SupportCategory',
                'property'    => 'name',
                'empty_value' => 'choose_root',
                'required'    => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('sc')->andWhere('sc.parent IS NULL');
                },
            ))
            ->add('name')
            ->add('type', 'choice', array(
                'choices' => array(
                    'category' => 'support_category_types.category',
                    'teacher'  => 'support_category_types.teacher',
                ),
                'empty_value' => 'choose_option',
            ));
    }

    public function getName()
    {
        return 'support_category';
    }
}
