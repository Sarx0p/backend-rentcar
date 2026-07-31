<?php

namespace App\Http\Requests\CierreRentaController;

use App\Enums\RolEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCierreRentaRequest extends FormRequest
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
            'contrato_id'                 => 'required|exists:contratos,id',
            'fecha_hora_recepcion'        => 'required|date',
            'nivel_combustible_recepcion' => 'required|string|max:30',
            'estado_vehiculo_recepcion'   => 'required|string|max:50',
            'observaciones'               => 'sometimes|nullable|string|max:500',
            'aplicar_cargo_retraso'       => 'sometimes|boolean',
            'monto_retraso'               => 'required_if:aplicar_cargo_retraso,true|numeric|min:0.01',
        ];
    }

    public function messages(): array
    {
        return [
            'contrato_id.required'                 => 'Debe indicar el contrato a cerrar.',
            'contrato_id.exists'                   => 'El contrato indicado no existe.',
            'fecha_hora_recepcion.required'        => 'La fecha y hora de recepción son obligatorias.',
            'nivel_combustible_recepcion.required' => 'El nivel de combustible de recepción es obligatorio.',
            'estado_vehiculo_recepcion.required'   => 'El estado del vehículo al recibirlo es obligatorio.',
            'monto_retraso.required_if'            => 'Debe indicar el monto del cargo por retraso.',
        ];
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para cerrar una renta',
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
