<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditPaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('exp_month', TextType::class, [
                'required' => true,
                'attr' => [
                    'min' => 0,
                    'max' => 12,
                    'minLength' => 2,
                    'maxLength' => 2
                ],
            ])
            ->add('exp_year', NumberType::class, [
                'required' => true,
                'attr' => [
                    'min' => date("Y"),
                    'minLength' => 4,
                    'maxLength' => 4
                ],
            ])
            ->add('default', CheckboxType::class, [
                'required' => false,
                'disabled' => $options['data']['default']
            ])
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'edit_payment',
        ]);
    }
}