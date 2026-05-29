<?php

namespace App\Enums;

enum VehiculoEstadoEnum: string
{
    case DISPONIBLE = 'DISPONIBLE';
    case RESERVADO = 'RESERVADO';
    case RENTADO = 'RENTADO';
    case MANTENIMIENTO = 'MANTENIMIENTO';
    case FUERA_SERVICIO = 'FUERA DE SERVICIO';
    case INACTIVO = 'INACTIVO';
    

}
