<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TelefonoValido implements ValidationRule
{
    // Código de país => cantidad de dígitos (los mismos países que el front)
    private const PAISES = [
        '503' => 8,
        '502' => 8,  
        '504' => 8,
        '505' => 8,
        '506' => 8,
        '507' => 8,
        '501' => 7,
        '52'  => 10,
        '1'   => 10,
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $telefono = trim((string) $value);

        if (!preg_match('/^\+(\d{1,3}) ([\d -]+)$/', $telefono, $partes)) {
            $fail('El teléfono debe llevar el código de país, por ejemplo +503 7000-0000.');
            return;
        }

        $prefijo = $partes[1];
        $numero  = preg_replace('/\D/', '', $partes[2]);

        if (!array_key_exists($prefijo, self::PAISES)) {
            $fail('El código de país del teléfono no está permitido.');
            return;
        }

        if (strlen($numero) !== self::PAISES[$prefijo]) {
            $fail('Para +' . $prefijo . ' el número debe tener ' . self::PAISES[$prefijo] . ' dígitos.');
            return;
        }

        if (preg_match('/^(\d)\1+$/', $numero)) {
            $fail('Ingresa un número de teléfono real, no una secuencia repetida.');
            return;
        }

        if ($prefijo === '503' && !preg_match('/^[267]/', $numero)) {
            $fail('En El Salvador el número debe empezar con 2 (fijo), 6 o 7 (celular).');
        }
    }
}
