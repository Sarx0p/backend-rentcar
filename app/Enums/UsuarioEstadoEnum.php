<?php

namespace App\Enums;

enum UsuarioEstadoEnum: string
{
    case ACTIVO = 'ACTIVO';
    case INACTIVO = 'INACTIVO';
    case BLOQUEADO = 'BLOQUEADO';
}
