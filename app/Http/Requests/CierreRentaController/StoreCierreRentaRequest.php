<?php

namespace App\Http\Requests\CierreRentaController;

use App\Enums\RolEnum;
use App\Models\Contrato;
use Carbon\Carbon;
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
            'contrato_id'                 => 'required|exists:contratos,id|unique:cierres_renta,contrato_id',
            'fecha_hora_recepcion'        => ['required', 'date_format:Y-m-d H:i:s', 'before_or_equal:' . now()->addMinutes(5)->toDateTimeString()],
            'nivel_combustible_recepcion' => 'required|string|in:E,1/4,1/2,3/4,F',
            'estado_vehiculo_recepcion'   => 'required|in:RECIBIDO,RECIBIDO CON DAÑOS',
            'observaciones'               => 'sometimes|nullable|string|max:500',
            'aplicar_cargo_retraso'       => 'sometimes|boolean',
            'forzar_cierre_con_deuda'     => 'sometimes|boolean',
            'motivo_cierre_deuda'         => 'required_if:forzar_cierre_con_deuda,true|nullable|string|min:5|max:500',
        ];
    }

    /**
     * La recepción no puede ser antes de que se entregara el vehículo.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->hasAny(['contrato_id', 'fecha_hora_recepcion'])) {
                return;
            }

            $contrato = Contrato::find($this->input('contrato_id'));

            if ($contrato && Carbon::parse($this->input('fecha_hora_recepcion'))->lt($contrato->fecha_hora_entrega)) {
                $validator->errors()->add(
                    'fecha_hora_recepcion',
                    'La recepción no puede ser antes de la entrega del vehículo (' . $contrato->fecha_hora_entrega->format('d/m/Y h:i A') . ').'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'contrato_id.required'                 => 'Debe indicar el contrato a cerrar.',
            'contrato_id.exists'                   => 'El contrato indicado no existe.',
            'contrato_id.unique'                   => 'Este contrato ya tiene un cierre registrado.',
            'fecha_hora_recepcion.required'        => 'La fecha y hora de recepción son obligatorias.',
            'fecha_hora_recepcion.date_format'     => 'La fecha y hora de recepción no son válidas.',
            'fecha_hora_recepcion.before_or_equal' => 'La fecha de recepción no puede ser en el futuro.',
            'estado_vehiculo_recepcion.in'         => 'El estado del vehículo al recibirlo no es válido.',
            'nivel_combustible_recepcion.required' => 'El nivel de combustible de recepción es obligatorio.',
            'nivel_combustible_recepcion.in'       => 'El nivel de combustible de recepción debe ser E, 1/4, 1/2, 3/4 o F.',
            'estado_vehiculo_recepcion.required'   => 'El estado del vehículo al recibirlo es obligatorio.',
            'motivo_cierre_deuda.required_if'      => 'Debe indicar el motivo para cerrar el contrato con deuda pendiente.',
            'motivo_cierre_deuda.min'              => 'El motivo del cierre con deuda debe tener al menos 5 caracteres.',
            'motivo_cierre_deuda.max'              => 'El motivo del cierre con deuda no puede exceder los 500 caracteres.',
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
