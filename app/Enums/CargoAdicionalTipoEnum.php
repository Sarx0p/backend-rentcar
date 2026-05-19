<?php

namespace App\Enums;

enum CargoAdicionalTipoEnum: string
{
    case COMBUSTIBLE = 'COMBUSTIBLE';
    case RETRASO = 'RETRASO';
    case DIA_EXTRA = 'DIA EXTRA';
    case DANIO = 'DANIO';
    case OTRO = 'OTRO';
}
