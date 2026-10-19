<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;


class TextoValido implements ValidationRule
{
    private const LETRA = 'A-Za-zÁÉÍÓÚáéíóúÑñÜü';

    
    public function __construct(
        private int $minLetras = 2,
        private bool $numeros = false,
        private string $simbolos = '-',
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $texto = trim(preg_replace('/\s+/u', ' ', (string) $value));
        $letra = self::LETRA;
        $alfa = $letra . ($this->numeros ? '0-9' : '');
        $simbolos = preg_quote($this->simbolos, '/');

        $permitidos = $this->numeros ? 'letras, números' : 'letras';
        if ($this->simbolos !== '') {
            $permitidos .= ' (símbolos permitidos: ' . implode(' ', mb_str_split($this->simbolos)) . ')';
        }

        if (!preg_match("/^[{$alfa}{$simbolos} ]+$/u", $texto)) {
            $fail("El campo :attribute solo puede llevar {$permitidos}.");
            return;
        }

        if (!preg_match("/^[{$alfa}]/u", $texto)) {
            $fail('El campo :attribute debe empezar con una letra' . ($this->numeros ? ' o un número.' : '.'));
            return;
        }

        $finPermitido = $alfa . (str_contains($this->simbolos, '.') ? '\.' : '');
        if (!preg_match("/[{$finPermitido}]$/u", $texto)) {
            $fail('El campo :attribute no puede terminar con un símbolo.');
            return;
        }

        if ($simbolos !== '' && preg_match("/[{$simbolos}] ?[{$simbolos}]/u", $texto)) {
            $fail('El campo :attribute tiene símbolos seguidos.');
            return;
        }

        if (preg_match('/^(.)\1+$/iu', str_replace(' ', '', $texto))) {
            $fail('El campo :attribute no puede ser el mismo carácter repetido.');
            return;
        }

        if (preg_match("/([{$letra}])\\1\\1/iu", $texto)) {
            $fail('El campo :attribute tiene una letra repetida demasiadas veces.');
            return;
        }

        $letras = preg_match_all("/[{$letra}]/u", $texto);
        if ($letras < $this->minLetras) {
            $fail("El campo :attribute debe tener al menos {$this->minLetras} letras.");
        }
    }
}
