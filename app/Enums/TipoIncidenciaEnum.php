<?php

namespace App\Enums;

enum TipoIncidenciaEnum: string
{
    case DANIO = 'DANIO';
    case ACCIDENTE = 'ACCIDENTE';
    case FALLA_MECANICA = 'FALLA MECANICA';
    case OTRO = 'OTRO';
}
