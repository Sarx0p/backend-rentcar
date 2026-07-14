<?php

namespace App\Http\Requests\ContratoController;

use App\Enums\RolEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreContratoDirectoRequest extends FormRequest
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
            'cliente_id'                => 'required|exists:clientes,id',
            'vehiculo_id'               => 'required|exists:vehiculos,id',
            'dias_acordados'            => 'required|integer|min:1',
            'precio_por_dia'            => 'required|numeric|min:0',
            'nivel_combustible_entrega' => 'required|string|max:50',
            'monto_descuento'           => 'sometimes|numeric|min:0',
            'observaciones_entrega'     => 'sometimes|nullable|string|max:500',
            'observaciones'             => 'sometimes|nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required'                => 'Debe seleccionar un cliente.',
            'cliente_id.exists'                  => 'El cliente seleccionado no existe.',
            'vehiculo_id.required'               => 'Debe seleccionar un vehículo.',
            'vehiculo_id.exists'                 => 'El vehículo seleccionado no existe.',
            'dias_acordados.required'            => 'Debe indicar los días acordados.',
            'dias_acordados.min'                 => 'Los días acordados deben ser al menos 1.',
            'precio_por_dia.required'            => 'El precio por día es obligatorio.',
            'nivel_combustible_entrega.required' => 'El nivel de combustible de entrega es obligatorio.',
        ];
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para crear contratos',
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
