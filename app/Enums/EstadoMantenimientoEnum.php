<?php

namespace App\Enums;

enum EstadoMantenimientoEnum: string
{
    case ACTIVO = 'ACTIVO';
    case CANCELADO = 'CANCELADO';
    case FINALIZADO = 'FINALIZADO';
}

    