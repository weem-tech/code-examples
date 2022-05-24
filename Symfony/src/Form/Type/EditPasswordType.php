<?php

namespace App\Form\Type;

use Rollerworks\Component\PasswordStrength\Validator\Constraints as RollerworksPassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class EditPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('oldPlainPassword', PasswordType::class, [
                'label' => 'Old password',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => ['trim' => true],
                'invalid_message' => 'The password fields must match.',
                'first_options' => [
                    'label' => 'New password',
                    'help' => 'Your password should contain at least 8 characters, one capital letter and a symbol.',
                ],
                'second_options' => ['label' => 'Repeat new password'],
                'constraints' => [
                    new NotBlank(),
                    new Regex([
                        'pattern' => '/^((?!\s).)*$/',
                        'message' => 'Password should not contain any whitespace characters.',
                    ]),
                    new RollerworksPassword\PasswordStrength([
                        'minLength' => 8,
                        'minStrength' => 4,
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'edit_password',
        ]);
    }
}