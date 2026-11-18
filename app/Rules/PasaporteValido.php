<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasaporteValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pasaporte = strtoupper(trim((string) $value));

        if (!preg_match('/^[A-Z0-9]+$/', $pasaporte)) {
            $fail('El :attribute solo puede llevar letras y números.');
            return;
        }

        if (strlen($pasaporte) < 5 || strlen($pasaporte) > 30) {
            $fail('El :attribute debe tener entre 5 y 30 caracteres.');
            return;
        }

        if (preg_match('/^(\w)\1+$/', $pasaporte)) {
            $fail('Ingresa un :attribute real, no una secuencia repetida.');
        }
    }
}
