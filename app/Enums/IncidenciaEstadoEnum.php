<?php

namespace App\Enums;

enum IncidenciaEstadoEnum: string
{
    case REPORTADA = 'REPORTADA';
    case EN_REVISION = 'EN REVISION';
    case RESUELTA = 'RESUELTA';
    case ANULADA = 'ANULADA';

}
//avia redundancia en dejar el campo "CERRADA"
