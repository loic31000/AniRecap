<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', null, [
                'label' => 'Nom d’utilisateur',
                'attr' => ['autocomplete' => 'username'],
                'constraints' => [
                    new NotBlank(message: 'Saisissez un nom d’utilisateur.'),
                    new Length(min: 2, max: 50, minMessage: 'Le nom d’utilisateur doit contenir au moins {{ limit }} caractères.'),
                ],
            ])
            ->add('email', null, [
                'label' => 'Adresse e-mail',
                'attr' => ['autocomplete' => 'email', 'inputmode' => 'email'],
                'constraints' => [
                    new NotBlank(message: 'Saisissez votre adresse e-mail.'),
                    new Email(message: 'Saisissez une adresse e-mail valide.'),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(
                        message: 'Saisissez un mot de passe.',
                    ),
                    new Length(
                        min: 6,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                        // max length allowed by Symfony for security reasons
                        max: 4096,
                    ),
                ],
            ])
            ->add('dataPolicyAccepted', CheckboxType::class, [
                'mapped' => false,
                'label' => false,
                'required' => true,
                'constraints' => [
                    new IsTrue(message: 'Vous devez prendre connaissance de la politique de confidentialité pour créer un compte.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
