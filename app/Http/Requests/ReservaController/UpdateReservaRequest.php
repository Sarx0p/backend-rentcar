<?php

namespace App\Http\Requests\ReservaController;

use App\Enums\RolEnum;
use App\Enums\TipoReservaEnum;
use App\Models\Reserva;
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

        return $user->hasRole(RolEnum::ADMINISTRADOR->value)|| $user->hasRole(RolEnum::EMPLEADO->value);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'tipo_reserva' => 'sometimes|in:' . implode(',', array_column(TipoReservaEnum::cases(), 'value')),
            'fecha_inicio' => 'sometimes|date',
            'fecha_fin'    => 'sometimes|date|after:fecha_inicio',
        ];

        if ($this->has('fecha_inicio')) {
            $tipoEvaluar = $this->input('tipo_reserva')
                ?? Reserva::find($this->route('id'))?->tipo_reserva;

            if ($tipoEvaluar === TipoReservaEnum::ANTISIPADA->value) {
                $rules['fecha_inicio'] .= '|after_or_equal:tomorrow';
            }
        }

        return $rules;
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'tipo_reserva.in'             => 'El tipo de reserva no es válido.',
            'fecha_inicio.date'           => 'La fecha de inicio no tiene un formato válido.',
            'fecha_inicio.after_or_equal' => 'Para una reserva ANTISIPADA, la fecha de inicio debe ser al menos desde el día de mañana.',
            'fecha_fin.date'              => 'La fecha de fin no tiene un formato válido.',
            'fecha_fin.after'             => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ];
    }


    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para actualizar reservas',
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
