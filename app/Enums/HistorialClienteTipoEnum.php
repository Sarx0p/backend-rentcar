<?php

namespace App\Enums;

enum HistorialClienteTipoEnum: string
{
    case DEUDA_PENDIENTE = 'DEUDA PENDIENTE';
    case DANIO_VEHICULO = 'DANIO VEHICULO';
    case OTRO = 'OTRO';
}
