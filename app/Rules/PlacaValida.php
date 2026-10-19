<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;


class PlacaValida implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $placa = strtoupper(trim((string) $value));

        if (!preg_match('/^[A-Z]{1,3}[ -]?\d{1,3}(?:[ -]?\d{1,4})?$/', $placa)) {
            $fail('La placa debe empezar con letras y seguir con números (ej. P123456 o P 123-456).');
            return;
        }

        $digitos = preg_replace('/\D/', '', $placa);

        if (strlen($digitos) < 3) {
            $fail('La placa debe tener al menos 3 números.');
            return;
        }

        if (preg_match('/^(\d)\1+$/', $digitos)) {
            $fail('Los números de la placa no pueden ser todos iguales.');
        }
    }
}
