<?php

namespace App\Enums;

enum EstadoPagoEnum: string
{
    //este es el estado de pago de contratos
    //olvide espesificar
    case PENDIENTE = 'PENDIENTE';
    case PARCIAL = 'PARCIAL';
    case PAGADO = 'PAGADO';

}
