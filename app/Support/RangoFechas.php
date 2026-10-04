<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Rango de fechas permitido para reservas y contratos.
 * Va desde hoy hasta el 31 de diciembre de dentro de dos años, y se recorre solo cada año
 * (en 2026 el límite es 31/12/2028; en 2027 será 31/12/2029).
 */
class RangoFechas
{
    public const ANIOS_ADELANTE = 2;

    public static function fechaMaxima(): Carbon
    {
        return now()->addYears(self::ANIOS_ADELANTE)->endOfYear();
    }

    /** Fecha máxima como texto Y-m-d, para usarla en reglas de validación. */
    public static function fechaMaximaTexto(): string
    {
        return self::fechaMaxima()->toDateString();
    }

    /** Mensaje para cuando una fecha se pasa del límite. */
    public static function mensajeFechaMaxima(): string
    {
        return 'La fecha no puede ser posterior al ' . self::fechaMaxima()->format('d/m/Y') . '.';
    }
}
