<?php

namespace App\Enums;

enum CargoAdicionalEstadoEnum: string
{
    case PENDIENTE = 'PENDIENTE';
    case APLICADO = 'APLICADO';
    case ANULADO = 'ANULADO';
    case PAGADO = 'PAGADO';
}
//sew quito el estado condonado por que no me paresio logico
