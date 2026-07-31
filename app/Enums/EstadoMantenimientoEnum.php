<?php

namespace App\Enums;

enum EstadoMantenimientoEnum: string
{
    case ACTIVO = 'ACTIVO';     // el vehículo está en mantenimiento AHORA esto quita tanto proseso para mi pareser inutiles
    case CANCELADO = 'CANCELADO';
    case FINALIZADO = 'FINALIZADO';
}

