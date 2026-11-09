<?php

namespace App\Form;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PerfilType extends AbstractType
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $obligatorio = new Assert\NotBlank(message: 'Este campo es obligatorio.', groups: ['perfil']);
        $longitud = new Assert\Length(max: 100, maxMessage: 'No ingreses más de {{ limit }} caracteres.', groups: ['perfil']);
        $builder
            ->add('nombre', TextType::class, ['empty_data' => '', 'constraints' => [$obligatorio, $longitud]])
            ->add('apellido', TextType::class, ['empty_data' => '', 'constraints' => [$obligatorio, $longitud]])
            ->add('email', EmailType::class, [
                'empty_data' => '',
                'constraints' => [
                    $obligatorio,
                    new Assert\Email(message: 'Ingresá un email válido.', groups: ['perfil']),
                    new Assert\Length(max: 180, maxMessage: 'No ingreses más de {{ limit }} caracteres.', groups: ['perfil']),
                    new Assert\Callback(function ($email, ExecutionContextInterface $context): void {
                        if (!is_string($email) || $email === '') {
                            return;
                        }
                        $usuario = $context->getRoot()->getData();
                        $existente = $this->userRepository->findOneBy(['email' => $email]);
                        if ($existente !== null && $existente->getId() !== $usuario->getId()) {
                            $context->buildViolation('Ya existe otra cuenta con ese email.')->addViolation();
                        }
                    }, groups: ['perfil']),
                ],
            ])
            ->add('telefono', TelType::class, ['empty_data' => '', 'constraints' => [$obligatorio, $longitud]])
            ->add('nuevaContrasena', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'invalid_message' => 'Las contraseñas no coinciden.',
                'first_options' => ['label' => 'Nueva contraseña', 'attr' => ['autocomplete' => 'new-password']],
                'second_options' => ['label' => 'Repetir nueva contraseña', 'attr' => ['autocomplete' => 'new-password']],
                'constraints' => [new Assert\Length(max: 4096, groups: ['perfil'])],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            // Las restricciones del perfil están en el formulario, no en User.
            'validation_groups' => ['perfil'],
        ]);
    }
}
