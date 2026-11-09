<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $required = new NotBlank(message: 'Este campo es obligatorio.');

        $builder
            ->add('nombre', TextType::class, [
                'empty_data' => '',
                'constraints' => [$required],
            ])
            ->add('apellido', TextType::class, [
                'empty_data' => '',
                'constraints' => [$required],
            ])
            ->add('email', EmailType::class, [
                'empty_data' => '',
                'constraints' => [
                    $required,
                    new Email(message: 'Ingresá un email válido.'),
                ],
            ])
            ->add('telefono', TelType::class, [
                'empty_data' => '',
                'constraints' => [$required],
            ])
            ->add('plainPassword', RepeatedType::class, [
                // Read in the controller and stored only as a hash.
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'Las contraseñas no coinciden.',
                'first_options' => [
                    'attr' => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        new NotBlank(message: 'Ingresá una contraseña.'),
                        new Length(
                            min: 6,
                            minMessage: 'La contraseña tiene que tener al menos {{ limit }} caracteres.',
                            // max length allowed by Symfony for security reasons
                            max: 4096,
                        ),
                    ],
                ],
                'second_options' => [
                    'attr' => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        new NotBlank(message: 'Repetí la contraseña.'),
                    ],
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue(message: 'Tenés que aceptar los términos.'),
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
