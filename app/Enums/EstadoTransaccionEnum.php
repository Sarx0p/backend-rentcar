<?php

namespace App\Enums;

enum EstadoTransaccionEnum: string
{
    case PENDIENTE = 'PENDIENTE';
    case CONFIRMADO = 'CONFIRMADO';
    case FALLIDO = 'FALLIDO';
    case ANULADO = 'ANULADO';

}
