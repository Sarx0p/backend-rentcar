<?php

namespace App\Enums;

enum EstadoContratoEnum: string
{
    case PENDIENTE = 'PENDIENTE';
    case ACTIVO = 'ACTIVO';
    case VENCIDO = 'VENCIDO';
    case FINALIZADO = 'FINALIZADO';
    case ANULADO = 'ANULADO';
}
