<?php

namespace App\Enums;

enum CargoAdicionalEstadoEnum: string
{
    case PENDIENTE = 'PENDIENTE';
    case APLICADO = 'APLICADO';
    case ANULADO = 'ANULADO';
}
//sew quito el estado condonado por que no me paresio logico
