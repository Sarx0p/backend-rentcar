<?php

namespace App\Enums;

enum CargoAdicionalEstadoEnum: string
{
    case PENDIENTE = 'PENDIENTE';
    case APLICADO = 'APLICADO';
    case CONDONADO = 'CONDONADO';
    case ANULADO = 'ANULADO';
}
