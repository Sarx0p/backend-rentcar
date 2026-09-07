<?php

namespace App\Enums;

enum VehiculoEstadoEnum: string
{
    case DISPONIBLE = 'DISPONIBLE';
    case RESERVADO = 'RESERVADO';
    case RENTADO = 'RENTADO';
    case MANTENIMIENTO = 'MANTENIMIENTO';
    case FUERA_SERVICIO = 'FUERA DE SERVICIO';
    case ENPROCESO = 'EN PROCESO';
   //se quito el inactivo por la redundancia que generaba el campo se opto
   //solo por el campo FUERA_SERVICIO donde se abarca ianctivo y fuera de servicio


}
