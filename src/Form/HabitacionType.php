<?php

namespace App\Form;

use App\Entity\Habitacion;
use App\Enum\TipoHabitacion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                    new Positive(
                        message: 'La capacidad máxima debe ser mayor que cero.'
                    ),
                ],
            ])

            ->add('precioPorNoche', NumberType::class, [
                'label' => 'Precio por noche',
                'input' => 'string',
                'scale' => 2,
                'constraints' => [
                    new NotBlank(message: 'Debe ingresar el precio.'),
                    new Positive(
                        message: 'El precio por noche debe ser mayor que cero.'
                    ),
                ],
            ])

            ->add('estrellas', ChoiceType::class, [
                'label' => 'Estrellas',
                'placeholder' => 'Elegí 1 a 5',
                'choices' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                ],
                'invalid_message' => 'La cantidad de estrellas debe ser entre 1 y 5.',
                'constraints' => [
                    new NotNull(
                        message: 'La cantidad de estrellas debe ser entre 1 y 5.'
                    ),
                    new Range(
                        min: 1,
                        max: 5,
                        notInRangeMessage: 'La cantidad de estrellas debe ser entre 1 y 5.'
                    ),
                ],
            ])

            ->add('descripcion', TextareaType::class, [
                'label' => 'Descripción',
                'required' => false,
                'empty_data' => '',
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