<?php

namespace App\Form;

use App\Entity\Habitacion;
use App\Enum\TipoHabitacion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;

class HabitacionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numero', IntegerType::class, [
                'label' => 'Número de habitación',
                'constraints' => [
                    new NotBlank(message: 'Debe ingresar un número.'),
                    new Positive(message: 'El número debe ser mayor que cero.'),
                ],
            ])

            ->add('tipo', EnumType::class, [
                'label' => 'Tipo de habitación',
                'class' => TipoHabitacion::class,
                'choice_label' => function (TipoHabitacion $tipo) {
                    return $tipo->value;
                },
                'placeholder' => 'Seleccione un tipo',
                'constraints' => [
                    new NotNull(message: 'Debe seleccionar un tipo.'),
                ],
            ])

            ->add('capacidadMaxima', IntegerType::class, [
                'label' => 'Capacidad máxima',
                'constraints' => [
                    new NotBlank(message: 'Debe ingresar la capacidad.'),
                    new Positive(message: 'La capacidad debe ser mayor que cero.'),
                ],
            ])

            ->add('precioPorNoche', NumberType::class, [
                'label' => 'Precio por noche',
                'input' => 'string',
                'scale' => 2,
                'constraints' => [
                    new NotBlank(message: 'Debe ingresar el precio.'),
                    new Positive(message: 'El precio debe ser mayor que cero.'),
                ],
            ])

            ->add('descripcion', TextareaType::class, [
                'label' => 'Descripción',
                'constraints' => [
                    new NotBlank(message: 'Debe ingresar una descripción.'),
                ],
            ])

            ->add('estrellas', IntegerType::class, [
                'label' => 'Estrellas',
                'constraints' => [
                    new NotBlank(message: 'Debe ingresar las estrellas.'),
                    new Range(
                        min: 1,
                        max: 5,
                        notInRangeMessage: 'Las estrellas deben estar entre 1 y 5.'
                    ),
                ],
            ])

            ->add('activa', CheckboxType::class, [
                'label' => 'Habitación activa',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Habitacion::class,
        ]);
    }
}