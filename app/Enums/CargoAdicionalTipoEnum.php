<?php

namespace App\Enums;

enum CargoAdicionalTipoEnum: string
{
    case COMBUSTIBLE = 'COMBUSTIBLE';
    case RETRASO = 'RETRASO';
    case DIA_EXTRA = 'DIA EXTRA';
    case OTRO = 'OTRO';
    //se quito el tipo danio por la razon qeu incidencia ya lo hace 
}
