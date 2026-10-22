<?php

namespace App\Enums;

enum CierreRentaEstadoEnum: string
{
    case FINALIZADO = 'FINALIZADO';
    case FINALIZADO_CON_DEUDA = 'FINALIZADO_CON_DEUDA';
    case ANULADO = 'ANULADO';
    case CAMBIO_VEHICULO = 'CAMBIO_VEHICULO';
}
