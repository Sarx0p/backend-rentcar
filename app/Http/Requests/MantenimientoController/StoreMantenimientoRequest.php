<?php

namespace App\Http\Requests\MantenimientoController;

use App\Enums\RolEnum;
use App\Enums\TipoMantenimientoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreMantenimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth('api')->user();

        return $user->hasRole(RolEnum::ADMINISTRADOR->value)
            || $user->hasRole(RolEnum::EMPLEADO->value);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'descripcion' => $this->descripcion ? trim(strip_tags($this->descripcion)) : null,
            'lugar'       => $this->lugar ? trim(strip_tags($this->lugar)) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'vehiculo_id'        => 'required|exists:vehiculos,id',
            'tipo_mantenimiento' => 'required|in:' . implode(',', array_column(TipoMantenimientoEnum::cases(), 'value')),
            'descripcion'        => 'nullable|string|max:250',
            'costo'              => 'required|numeric|min:0|max:999999.99|regex:/^\d+(\.\d{1,2})?$/',
            'lugar'              => 'required|string|max:150',
        ];
    }

    public function messages(): array
    {
        return [
            'vehiculo_id.required'        => 'Debe seleccionar un vehículo.',
            'vehiculo_id.exists'          => 'El vehículo seleccionado no existe.',
            'tipo_mantenimiento.required' => 'El tipo de mantenimiento es obligatorio.',
            'tipo_mantenimiento.in'       => 'El tipo de mantenimiento no es válido.',
            'costo.required'              => 'El costo es obligatorio.',
            'costo.numeric'               => 'El costo debe ser un valor numérico.',
            'costo.min'                   => 'El costo no puede ser negativo.',
            'costo.max'                   => 'El costo excede el límite permitido.',
            'costo.regex'                 => 'El costo debe tener como máximo 2 decimales.',
            'lugar.required'              => 'El lugar es obligatorio.',
            'lugar.max'                   => 'El lugar no puede exceder los 150 caracteres.',
            'descripcion.max'             => 'La descripción no puede exceder los 250 caracteres.',
        ];
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para registrar mantenimientos',
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
