<?php

namespace App\Enums;

enum SeguroEstadoEnum: string
{
    case VIGENTE = 'VIGENTE';
    case VENCIDO = 'VENCIDO';
    case CANCELADO = 'CANCELADO';
    
}
