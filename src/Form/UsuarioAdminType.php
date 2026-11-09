<?php

namespace App\Form;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UsuarioAdminType extends AbstractType
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $obligatorio = new Assert\NotBlank(message: 'Este campo es obligatorio.', groups: ['usuario_admin']);
        $longitud = new Assert\Length(max: 100, maxMessage: 'No ingreses más de {{ limit }} caracteres.', groups: ['usuario_admin']);
        $usuario = $options['data'];
        $builder
            ->add('nombre', TextType::class, ['empty_data' => '', 'constraints' => [$obligatorio, $longitud]])
            ->add('apellido', TextType::class, ['empty_data' => '', 'constraints' => [$obligatorio, $longitud]])
            ->add('email', EmailType::class, [
                'empty_data' => '',
                'constraints' => [
                    $obligatorio,
                    new Assert\Email(message: 'Ingresá un email válido.', groups: ['usuario_admin']),
                    new Assert\Length(max: 180, maxMessage: 'No ingreses más de {{ limit }} caracteres.', groups: ['usuario_admin']),
                    new Assert\Callback(function ($email, ExecutionContextInterface $context): void {
                        if (!is_string($email) || $email === '') {
                            return;
                        }
                        $usuario = $context->getRoot()->getData();
                        $existente = $this->userRepository->findOneBy(['email' => $email]);
                        if ($existente !== null && $existente->getId() !== $usuario->getId()) {
                            $context->buildViolation('Ya existe otra cuenta con ese email.')->addViolation();
                        }
                    }, groups: ['usuario_admin']),
                ],
            ])
            ->add('telefono', TelType::class, ['empty_data' => '', 'constraints' => [$obligatorio, $longitud]])
            ->add('rol', ChoiceType::class, [
                'mapped' => false,
                'choices' => ['CLIENTE' => 'CLIENTE', 'ADMINISTRADOR' => 'ADMINISTRADOR'],
                'data' => $usuario instanceof User && in_array('ROLE_ADMIN', $usuario->getRoles(), true) ? 'ADMINISTRADOR' : 'CLIENTE',
                'constraints' => [$obligatorio],
                'invalid_message' => 'Seleccioná un rol válido.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            // Evitamos aplicar al perfil de administración el mensaje de registro de User.
            'validation_groups' => ['usuario_admin'],
        ]);
    }
}
