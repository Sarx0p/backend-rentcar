<?php

namespace App\Enums;

enum HistorialClienteEstadoEnum: string
{
    case VIGENTE = 'VIGENTE';
    case RESUELTO = 'RESUELTO';
    case ARCHIVADO = 'ARCHIVADO';
}
