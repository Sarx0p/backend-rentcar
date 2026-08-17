<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Closure;

class DuiValido implements ValidationRule
{
    /**
     * Valida un DUI de El Salvador (formato 12345678-9)
     * usando el algoritmo del dígito verificador.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^\d{8}-\d{1}$/', $value)) {
            $fail('El :attribute no tiene un formato válido (debe ser 12345678-9).');
            return;
        }

        $digitos  = str_split(str_replace('-', '', $value));
        $factores = [9, 8, 7, 6, 5, 4, 3, 2];
        $suma     = 0;

        for ($i = 0; $i < 8; $i++) {
            $suma += (int) $digitos[$i] * $factores[$i];
        }

        $residuo            = $suma % 10;
        $digitoVerificador  = (10 - $residuo) % 10;

        if ((int) $digitos[8] !== $digitoVerificador) {
            $fail('El :attribute no es un DUI válido.');
        }
    }
}
