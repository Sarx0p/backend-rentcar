<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LicenciaValida implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $licencia = (string) $value;

        if (!preg_match('/^\d+$/', $licencia)) {
            $fail('El número de licencia solo puede llevar números.');
            return;
        }

        if (!in_array(strlen($licencia), [9, 14], true)) {
            $fail('La licencia debe tener 9 dígitos (número de DUI) o 14 dígitos (NIT).');
            return;
        }

        if (preg_match('/^(\d)\1+$/', $licencia)) {
            $fail('Ingresa un número de licencia real, no una secuencia repetida.');
        }
    }
}
