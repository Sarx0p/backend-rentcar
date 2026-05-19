<?php

namespace App\Enums;

enum EstadoMantenimientoEnum: string
{
    case PROGRAMADO = 'PROGRAMADO';
    case EN_PROCESO = 'EN PROCESO';
    case FINALIZADO = 'FINALIZADO';
    case CANCELADO = 'CANCELADO';
}
