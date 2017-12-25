<?php

namespace My\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ImageFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('uploadFile', 'file', array(
                'attr'           => array('accept' => 'image/*'),
                'error_bubbling' => true,
            ))
        ;
    }

    public function getName()
    {
        return 'image';
    }
}
