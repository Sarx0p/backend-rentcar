<?php

namespace App\Enums;

enum EstadoContratoEnum: string
{
    case PENDIENTE = 'PENDIENTE';
    case ACTIVO = 'ACTIVO';
    case FINALIZADO = 'FINALIZADO';
    case ANULADO = 'ANULADO';
}
