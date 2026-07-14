<?php

namespace App\Http\Requests\ReservaController;

use App\Enums\RolEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateReservaRequest extends FormRequest
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
            'fecha_inicio' => 'sometimes|date|after_or_equal:tomorrow',
            'fecha_fin'    => 'sometimes|date|after:fecha_inicio',
        ];
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'fecha_inicio.date'           => 'La fecha de inicio no tiene un formato válido.',
            'fecha_inicio.after_or_equal' => 'La fecha de inicio debe ser al menos desde el día de mañana.',
            'fecha_fin.date'              => 'La fecha de fin no tiene un formato válido.',
            'fecha_fin.after'             => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ];
    }

    /**
     * Si la autorización falla.
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para actualizar reservas',
        ], 403));
    }

    /**
     * Si la validación falla.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'Error de validación',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
