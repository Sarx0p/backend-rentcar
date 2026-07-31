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

    public function rules(): array
    {
        return [
            'vehiculo_id'        => 'required|exists:vehiculos,id',
            'tipo_mantenimiento' => 'required|in:' . implode(',', array_column(TipoMantenimientoEnum::cases(), 'value')),
            'descripcion'        => 'nullable|string|max:250',
            'costo'              => 'required|numeric|min:0',
            'fecha'              => 'required|date',
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
            'fecha.required'              => 'La fecha es obligatoria.',
            'lugar.required'              => 'El lugar es obligatorio.',
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
