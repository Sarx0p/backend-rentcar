<?php

namespace App\Enums;

enum CierreRentaEstadoEnum: string
{
    case EN_REVISION = 'EN REVISION';
    case FINALIZADO = 'FINALIZADO';
    case ANULADO = 'ANULADO';
}
