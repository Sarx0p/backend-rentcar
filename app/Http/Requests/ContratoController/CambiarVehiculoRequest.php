<?php

namespace App\Http\Requests\ContratoController;

use App\Enums\RolEnum;
use App\Enums\IncidenciaTipoResponsableEnum;
use App\Models\Contrato;
use App\Models\Reserva;
use App\Models\Seguro;
use App\Models\Vehiculo;
use App\Enums\EstadoReservaEnum;
use App\Enums\EstadoContratoEnum;
use App\Enums\VehiculoEstadoEnum;
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
            'motivo'                    => 'required|string|min:5|max:500',
            'nivel_combustible_entrega' => 'required|string|in:E,1/4,1/2,3/4,F',
            'nivel_combustible_recepcion' => 'nullable|string|in:E,1/4,1/2,3/4,F',
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
            'motivo.min'                         => 'El motivo debe tener al menos 5 caracteres.',
            'motivo.max'                         => 'El motivo no puede exceder los 500 caracteres.',
            'nivel_combustible_entrega.required' => 'El nivel de combustible de entrega es obligatorio.',
            'nivel_combustible_entrega.in'       => 'El nivel de combustible de entrega debe ser E, 1/4, 1/2, 3/4 o F.',
            'nivel_combustible_recepcion.in'     => 'El nivel de combustible con que se recibe el vehículo anterior debe ser E, 1/4, 1/2, 3/4 o F.',
            'costo.numeric'                      => 'El costo debe ser un número válido.',
            'costo.min'                          => 'El costo no puede ser negativo.',
            'costo.max'                          => 'El costo no puede ser mayor a 999,999.99.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $mensaje = $this->reglaIncumplida();

            if ($mensaje) {
                $validator->errors()->add('regla', $mensaje);
            }
        });
    }

    private function reglaIncumplida(): ?string
    {
        $contrato = Contrato::with('vehiculo')->find($this->route('id'));

        if (!$contrato) {
            return null;
        }

        if ($contrato->estado_contrato !== EstadoContratoEnum::ACTIVO->value) {
            return 'Solo se puede cambiar el vehículo de un contrato ACTIVO';
        }

        $vehiculoAnterior = $contrato->vehiculo;
        $vehiculoNuevo    = Vehiculo::find($this->vehiculo_nuevo_id);

        if (!$vehiculoNuevo) {
            return null;
        }

        if ((int) $vehiculoNuevo->id === (int) $vehiculoAnterior->id) {
            return 'El vehículo de reemplazo debe ser distinto al actual';
        }

        if ($vehiculoNuevo->estado !== VehiculoEstadoEnum::DISPONIBLE->value) {
            return 'El vehículo de reemplazo no está disponible, estado actual: ' . $vehiculoNuevo->estado;
        }

        $ahora = now();

        $reservaTraslapada = Reserva::where('vehiculo_id', $vehiculoNuevo->id)
            ->whereIn('estado', [EstadoReservaEnum::PENDIENTE->value, EstadoReservaEnum::CONFIRMADA->value])
            ->where(function ($query) use ($ahora, $contrato) {
                $query->whereDate('fecha_inicio', '<', $contrato->fecha_hora_devolucion->toDateString())
                    ->whereDate('fecha_fin', '>', $ahora->toDateString());
            })
            ->exists();

        if ($reservaTraslapada) {
            return 'El vehículo de reemplazo tiene una reserva que se traslapa con el resto del contrato';
        }

        if (Seguro::faltaCobertura($vehiculoNuevo->id, $ahora->toDateString(), $contrato->fecha_hora_devolucion->toDateString())) {
            return 'El vehículo de reemplazo no tiene un seguro vigente que cubra hasta el ' . $contrato->fecha_hora_devolucion->format('d/m/Y') . '. Renueva el seguro antes de entregarlo.';
        }

        return null;
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
        if ($validator->errors()->has('regla')) {
            throw new HttpResponseException(response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first('regla'),
            ], 422));
        }

        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'Error de validación',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
