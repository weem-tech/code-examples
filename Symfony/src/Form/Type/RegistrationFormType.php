<?php

namespace App\Form\Type;

use App\Entity\User\User;
use Rollerworks\Component\PasswordStrength\Validator\Constraints as RollerworksPassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class)
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => ['trim' => true],
                'invalid_message' => 'The password fields must match.',
                'first_options' => [
                    'label' => 'Password',
                    'help' => 'Your password should contain at least 8 characters, one capital letter and a symbol.',
                ],
                'second_options' => ['label' => 'Repeat password'],
                'constraints' => [
                    new NotBlank(),
                    new RollerworksPassword\PasswordStrength([
                        'minLength' => 8,
                        'minStrength' => 4,
                    ]),
                ],
            ])
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
            ->add('termsAccepted', CheckboxType::class, [
                'mapped' => false,
                'constraints' => new IsTrue(),
            ])
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_token_id' => 'registration',
        ]);
    }
}