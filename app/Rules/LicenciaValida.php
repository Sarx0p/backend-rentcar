<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LicenciaValida implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $licencia = strtoupper(trim((string) $value));

        if (!preg_match('/^[A-Z0-9]+$/', $licencia)) {
            $fail('El número de licencia solo puede llevar letras y números.');
            return;
        }

        if (strlen($licencia) < 5 || strlen($licencia) > 30) {
            $fail('La licencia debe tener entre 5 y 30 caracteres.');
            return;
        }

        if (preg_match('/^(\w)\1+$/', $licencia)) {
            $fail('Ingresa un número de licencia real, no una secuencia repetida.');
        }
    }
}
