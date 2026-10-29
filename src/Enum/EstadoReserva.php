<?php

namespace App\Enum;

enum EstadoReserva: string
{
    case PENDIENTE = 'PENDIENTE';
    case CONFIRMADA = 'CONFIRMADA';
    case CANCELACION_PENDIENTE = 'CANCELACION_PENDIENTE';
    case CANCELADA = 'CANCELADA';
}