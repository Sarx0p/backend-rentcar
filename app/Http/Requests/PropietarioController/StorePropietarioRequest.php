<?php

namespace App\Http\Requests\PropietarioController;

use App\Enums\RolEnum;
use App\Enums\TipoPropietarioEnum;
use App\Enums\EstadoPropietarioEnum;
use App\Models\Propietario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePropietarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth('api')->user();

        return $user->hasRole(RolEnum::ADMINISTRADOR->value);
    }

    public function rules(): array
    {
        return [
            'nombre'           => 'required|string|max:100',
            'telefono'         => 'required|string|max:25|unique:propietarios,telefono',
            'tipo_propietario' => 'required|in:' . implode(',', array_column(TipoPropietarioEnum::cases(), 'value')),
        ];
    }

    /**
     * Validación adicional: solo puede existir un propietario ACTIVO tipo PROPIO.
     * Si el anterior está INACTIVO, sí se permite crear uno nuevo.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('tipo_propietario') !== TipoPropietarioEnum::PROPIO->value) {
                return;
            }

            $existeDueno = Propietario::where('tipo_propietario', TipoPropietarioEnum::PROPIO->value)
                ->where('estado', EstadoPropietarioEnum::ACTIVO->value)
                ->exists();

            if ($existeDueno) {
                $validator->errors()->add(
                    'tipo_propietario',
                    'Ya existe un propietario ACTIVO registrado como PROPIO (dueño del negocio). No se puede duplicar.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'nombre.required'           => 'El nombre es obligatorio.',
            'telefono.required'         => 'El teléfono es obligatorio.',
            'telefono.unique'           => 'Ya existe un propietario registrado con ese número de teléfono.',
            'tipo_propietario.required' => 'El tipo de propietario es obligatorio.',
            'tipo_propietario.in'       => 'El tipo de propietario no es válido.',
        ];
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para registrar propietarios',
        ], 403));
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'Error de validación',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
