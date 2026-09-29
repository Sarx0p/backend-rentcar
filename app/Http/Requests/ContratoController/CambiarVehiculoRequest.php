<?php

namespace App\Http\Requests\ContratoController;

use App\Enums\RolEnum;
use App\Enums\IncidenciaTipoResponsableEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CambiarVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth('api')->user();

        if (!$user) {
            return false;
        }

        return $user->hasRole(RolEnum::ADMINISTRADOR->value)
            || $user->hasRole(RolEnum::EMPLEADO->value);
    }

    public function rules(): array
    {
        return [
            'vehiculo_nuevo_id'         => 'required|exists:vehiculos,id',
            'responsable_tipo'          => 'required|in:' . implode(',', [
                IncidenciaTipoResponsableEnum::NEGOCIO->value,
                IncidenciaTipoResponsableEnum::TERCERO->value,
            ]),
            'motivo'                    => 'required|string|max:500',
            'nivel_combustible_entrega' => 'required|string|max:50',
            'costo'                     => 'nullable|numeric|min:0|max:999999.99',
        ];
    }

    public function messages(): array
    {
        return [
            'vehiculo_nuevo_id.required'         => 'Debe seleccionar el vehículo de reemplazo.',
            'vehiculo_nuevo_id.exists'           => 'El vehículo de reemplazo no existe.',
            'responsable_tipo.required'          => 'Debe indicar el responsable del desperfecto.',
            'responsable_tipo.in'                => 'El cambio de vehículo solo aplica cuando el responsable es NEGOCIO o TERCERO.',
            'motivo.required'                    => 'Debe describir el desperfecto del vehículo.',
            'motivo.max'                         => 'El motivo no puede exceder los 500 caracteres.',
            'nivel_combustible_entrega.required' => 'El nivel de combustible de entrega es obligatorio.',
            'nivel_combustible_entrega.max'      => 'El nivel de combustible no puede exceder los 50 caracteres.',
            'costo.numeric'                      => 'El costo debe ser un número válido.',
            'costo.min'                          => 'El costo no puede ser negativo.',
            'costo.max'                          => 'El costo no puede ser mayor a 999,999.99.',
        ];
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para cambiar el vehículo de un contrato',
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
