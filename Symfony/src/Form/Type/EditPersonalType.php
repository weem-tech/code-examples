<?php

namespace App\Form\Type;

use App\Entity\User\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditPersonalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
            ])
            ->add('companyName', TextType::class, [
                'required' => true,
            ])
            ->add('street', TextType::class, [
                'required' => true,
            ])
            ->add('zip', TextType::class, [
                'required' => true,
            ])
            ->add('city', TextType::class, [
                'required' => true,
            ])
            ->add('country', CountryType::class, [
                'placeholder' => 'Choose a country',
                'required' => true,
            ])
            ->add('vatId', TextType::class, [
                'help' => 'The VAT-ID is assigned to companies by the countries of the European Union and is used 
                for the processing of intra-Community trade in goods and services for turnover tax purposes. 
                Under certain conditions, this allows the tax liability to be reversed.',
                'required' => false,
            ])
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_token_id' => 'edit_personal',
        ]);
    }
}