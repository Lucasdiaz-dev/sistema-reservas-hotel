<?php

namespace App\Form;

use App\Enum\EstadoReserva;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FiltroReservasType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('desde', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'Fecha desde',
                'required' => false,
            ])
            ->add('hasta', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'Fecha hasta',
                'required' => false,
            ])
            ->add('estado', EnumType::class, [
                'class' => EstadoReserva::class,
                'label' => 'Estado',
                'required' => false,
                'placeholder' => 'Todos',
                'choice_label' => static fn (EstadoReserva $estado): string => match ($estado) {
                    EstadoReserva::PENDIENTE => 'Pendiente',
                    EstadoReserva::CONFIRMADA => 'Confirmada',
                    EstadoReserva::CANCELACION_PENDIENTE => 'Cancelación pendiente',
                    EstadoReserva::CANCELADA => 'Cancelada',
                },
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event): void {
            $data = $event->getData();
            if (!\is_array($data) || !\array_key_exists('estado', $data)) {
                return;
            }

            $estado = $data['estado'];
            if (!\is_string($estado) || EstadoReserva::tryFrom($estado) === null) {
                $data['estado'] = '';
                $event->setData($data);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}
