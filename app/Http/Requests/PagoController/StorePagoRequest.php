<?php

namespace App\Http\Requests\PagoController;

use App\Enums\RolEnum;
use App\Enums\MetodoPagoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePagoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth('api')->user();

        return $user->hasRole(RolEnum::ADMINISTRADOR->value)
            || $user->hasRole(RolEnum::EMPLEADO->value);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'contrato_id' => 'required|exists:contratos,id',
            'monto'       => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|in:' . implode(',', array_column(MetodoPagoEnum::cases(), 'value')),
            'fecha_pago'  => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'contrato_id.required' => 'Debe indicar el contrato.',
            'contrato_id.exists'   => 'El contrato indicado no existe.',
            'monto.required'       => 'El monto es obligatorio.',
            'monto.min'            => 'El monto debe ser mayor a 0.',
            'metodo_pago.required' => 'El método de pago es obligatorio.',
            'metodo_pago.in'       => 'El método de pago no es válido.',
            'fecha_pago.required'  => 'La fecha de pago es obligatoria.',
            'fecha_pago.date'      => 'La fecha de pago no tiene un formato válido.',
        ];
    }


    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para registrar pagos',
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
