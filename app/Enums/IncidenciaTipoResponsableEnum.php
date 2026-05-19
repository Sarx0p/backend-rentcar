<?php

namespace App\Enums;

enum IncidenciaTipoResponsableEnum: string
{
    case CLIENTE = 'CLIENTE';
    case NEGOCIO = 'NEGOCIO';
    case TERCERO = 'TERCERO';
    case NO_DETERMINADO = 'NO DETERMINADO';

}
