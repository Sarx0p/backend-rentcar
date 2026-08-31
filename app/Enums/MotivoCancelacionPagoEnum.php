<?php

namespace App\Enums;

enum MotivoCancelacionPagoEnum: string
{
    case ERROR_REGISTRO = 'Error al registrar el pago';
    case PAGO_DUPLICADO = 'Pago duplicado';
    case MONTO_INCORRECTO = 'Monto incorrecto';
    case METODO_INCORRECTO = 'Método de pago incorrecto';
    case CLIENTE_SOLICITA = 'Cliente solicitó anulación';
    case COMPROBANTE_INVALIDO = 'Comprobante inválido';
}
