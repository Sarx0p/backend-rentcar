<?php

namespace App\Enums;

enum EstadoReservaEnum: string
{
    case PENDIENTE = 'PENDIENTE';
    case CONFIRMADA = 'CONFIRMADA';
    case CANCELADA = 'CANCELADA';
}
