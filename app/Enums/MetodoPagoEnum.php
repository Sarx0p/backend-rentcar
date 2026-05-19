<?php

namespace App\Enums;

enum MetodoPagoEnum: string
{
    case EFECTIVO = 'EFECTIVO';
    case TRASFERENCIA = 'TRANSFERENCIA';
    case DEPOSITO = 'DEPOSITO';

}
