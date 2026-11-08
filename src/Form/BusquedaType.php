<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusquedaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $huespedes = [];
        for ($cantidad = 1; $cantidad <= $options['capacidad_maxima']; ++$cantidad) {
            $huespedes[(string) $cantidad] = $cantidad;
        }

        $builder
            ->add('ingreso', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'Fecha de ingreso',
                'required' => false,
            ])
            ->add('egreso', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'Fecha de egreso',
                'required' => false,
            ])
            ->add('huespedes', ChoiceType::class, [
                'label' => 'Huéspedes',
                'choices' => $huespedes,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'method' => 'GET',
            'capacidad_maxima' => 1,
        ]);
        $resolver->setAllowedTypes('capacidad_maxima', 'int');
    }
}
