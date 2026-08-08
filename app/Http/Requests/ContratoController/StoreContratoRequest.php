<?php

namespace App\Http\Requests\ContratoController;

use App\Enums\RolEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreContratoRequest extends FormRequest
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
            'reserva_id'                => 'required|exists:reservas,id',
            'fecha_hora_entrega'        => 'required|date',
            'fecha_hora_devolucion'     => 'required|date|after:fecha_hora_entrega',
            'precio_por_dia'            => 'required|numeric|min:0.1',
            'nivel_combustible_entrega' => 'required|string|max:50',
            'monto_descuento'           => 'sometimes|numeric|min:0',
            'observaciones_entrega'     => 'sometimes|nullable|string|max:500',
            'observaciones'             => 'sometimes|nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'reserva_id.required'                => 'Debe indicar la reserva de origen.',
            'reserva_id.exists'                  => 'La reserva indicada no existe.',
            'fecha_hora_entrega.required'        => 'La fecha y hora de entrega son obligatorias.',
            'fecha_hora_devolucion.required'     => 'La fecha y hora de devolución son obligatorias.',
            'fecha_hora_devolucion.after'        => 'La devolución debe ser posterior a la entrega.',
            'precio_por_dia.required'            => 'El precio por día es obligatorio.',
            'precio_por_dia.min'                 => 'El precio por día tiene que ser mayor que 0.',
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
