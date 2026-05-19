<?php

namespace App\Enums;

enum IncidenciaEstadoEnum: string
{
    case REPORTADA = 'REPORTADA';
    case EN_REVISION = 'EN REVISION';
    case RESUELTA = 'RESUELTA';
    case CERRADA = 'CERRADA';
    case ANULADA = 'ANULADA';
    
}
