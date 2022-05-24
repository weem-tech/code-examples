<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GenderType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => [
                'Male' => 'mr',
                'Female' => 'ms',
                'Both' => 'both',
            ],
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    public function getName()
    {
        return 'gender';
    }
}