<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NombrePersonaValido implements ValidationRule
{
    private const LETRA = 'A-Za-zÁÉÍÓÚáéíóúÑñÜü';

    private const PARTICULAS = ['de', 'del', 'la', 'las', 'los', 'y', 'e', 'van', 'von'];

 
    public function __construct(
        private int $palabrasMinimas = 2,
        private string $campo = 'nombre',
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $texto = trim(preg_replace('/\s+/u', ' ', (string) $value));
        $letra = self::LETRA;
        $campo = $this->campo;

        if (!preg_match("/^[{$letra}]+(?:[' -][{$letra}]+)*$/u", $texto)) {
            $fail("El {$campo} solo puede llevar letras. El guion y el apóstrofo van entre letras (ej. María-José, D'Angelo).");
            return;
        }

        if (preg_match("/([{$letra}])\\1\\1/iu", $texto)) {
            $fail("El {$campo} tiene una letra repetida demasiadas veces.");
            return;
        }

        $palabras = array_filter(
            preg_split('/[\s-]+/u', str_replace("'", '', $texto)),
            fn ($palabra) => mb_strlen($palabra) >= 2 && !in_array(mb_strtolower($palabra), self::PARTICULAS, true)
        );

        if (count($palabras) < $this->palabrasMinimas) {
            $fail($this->palabrasMinimas >= 2
                ? 'Escribe al menos un nombre y un apellido.'
                : "Escribe un {$campo} válido de al menos 2 letras.");
            return;
        }

        foreach ($palabras as $palabra) {
            if (!preg_match('/[aeiouyáéíóúü]/iu', $palabra)) {
                $fail("Revisa el {$campo}: hay una palabra sin vocales.");
                return;
            }
        }
    }
}
