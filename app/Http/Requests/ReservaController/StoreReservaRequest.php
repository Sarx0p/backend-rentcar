<?php

namespace App\Http\Requests\ReservaController;

use App\Enums\RolEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreReservaRequest extends FormRequest
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
            'cliente_id'   => 'required|exists:clientes,id',
            'vehiculo_id'  => 'required|exists:vehiculos,id',
            'fecha_inicio' => 'required|date|after_or_equal:tomorrow',
            'fecha_fin'    => 'required|date|after:fecha_inicio',
        ];
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'cliente_id.required'         => 'Debe seleccionar un cliente.',
            'cliente_id.exists'           => 'El cliente seleccionado no existe.',
            'vehiculo_id.required'        => 'Debe seleccionar un vehículo.',
            'vehiculo_id.exists'          => 'El vehículo seleccionado no existe.',
            'fecha_inicio.required'       => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date'           => 'La fecha de inicio no tiene un formato válido.',
            'fecha_inicio.after_or_equal' => 'La fecha de inicio debe ser al menos desde el día de mañana.',
            'fecha_fin.required'          => 'La fecha de fin es obligatoria.',
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
            'message' => 'No tienes permiso para crear reservas',
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
