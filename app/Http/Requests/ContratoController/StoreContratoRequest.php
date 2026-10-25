<?php

namespace App\Http\Requests\ContratoController;

use App\Enums\RolEnum;
use App\Models\Contrato;
use App\Models\Reserva;
use App\Models\Seguro;
use Carbon\Carbon;
use App\Enums\EstadoReservaEnum;
use App\Enums\EstadoContratoEnum;
use App\Enums\EstadoPagoEnum;
use App\Enums\VehiculoEstadoEnum;
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
            'reserva_id'                => 'required|exists:reservas,id|unique:contratos,reserva_id,',
            'fecha_hora_entrega'        => 'required|date',
            'precio_por_dia'            => 'sometimes|numeric|min:0.1',
            'nivel_combustible_entrega' => 'required|string|in:E,1/4,1/2,3/4,F',
            'descuento_porcentaje'      => 'sometimes|numeric|min:0|max:100',
            'observaciones_entrega'     => 'sometimes|nullable|string|max:500',
            'observaciones'             => 'sometimes|nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'reserva_id.unique'                  => 'Esta reserva ya tiene un contrato generado',
            'reserva_id.required'                => 'Debe indicar la reserva de origen.',
            'reserva_id.exists'                  => 'La reserva indicada no existe.',
            'fecha_hora_entrega.required'        => 'La fecha y hora de entrega son obligatorias.',
            'precio_por_dia.numeric'             => 'El precio por día debe ser un número válido.',
            'precio_por_dia.min'                 => 'El precio por día tiene que ser mayor que 0.',
            'nivel_combustible_entrega.required' => 'El nivel de combustible de entrega es obligatorio.',
            'nivel_combustible_entrega.in'       => 'El nivel de combustible de entrega debe ser E, 1/4, 1/2, 3/4 o F.',
            'descuento_porcentaje.max'           => 'El descuento no puede ser mayor al 100%.',
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
        $reserva = Reserva::with(['vehiculo.categoria', 'cliente'])->find($this->reserva_id);

        if (!$reserva) {
            return null;
        }

        if ($reserva->estado !== EstadoReservaEnum::PENDIENTE->value) {
            return 'Solo se puede crear un contrato para una reserva en estado PENDIENTE';
        }

        if ($reserva->contrato) {
            return 'Esta reserva ya tiene un contrato generado';
        }

        if ($reserva->cliente->vencimiento_licencia->isPast()) {
            return 'El cliente tiene la licencia vencida, no puede generar el contrato';
        }

        $entrega = Carbon::parse($this->fecha_hora_entrega);

        if (!$entrega->isSameDay($reserva->fecha_inicio)) {
            return 'La entrega debe realizarse el día de inicio de la reserva';
        }

        $dias       = max(1, (int) $reserva->fecha_inicio->diffInDays($reserva->fecha_fin));
        $devolucion = $entrega->copy()->addDays($dias);

        if ($this->filled('vehiculo_id') && (int) $this->vehiculo_id !== (int) $reserva->vehiculo_id) {
            return 'El vehículo enviado no coincide con el de la reserva';
        }

        $vehiculo = $reserva->vehiculo;

        if ($vehiculo->estado !== VehiculoEstadoEnum::DISPONIBLE->value) {
            return 'El vehículo no está disponible, estado actual: ' . $vehiculo->estado;
        }

        if ($reserva->cliente->vencimiento_dui->isPast()) {
            return 'El cliente tiene el DUI vencido, no puede generar el contrato';
        }

        if ($reserva->cliente->vencimiento_licencia->lte($devolucion->copy()->startOfDay())) {
            return 'La licencia del cliente vence el ' . $reserva->cliente->vencimiento_licencia->format('d/m/Y') . ', antes de la devolución del vehículo';
        }

        if ($reserva->cliente->vencimiento_dui->lte($devolucion->copy()->startOfDay())) {
            return 'El DUI del cliente vence el ' . $reserva->cliente->vencimiento_dui->format('d/m/Y') . ', antes de la devolución del vehículo';
        }

        if (Seguro::faltaCobertura($vehiculo->id, $entrega->toDateString(), $devolucion->toDateString())) {
            return 'El vehículo no tiene un seguro vigente que cubra hasta el ' . $devolucion->format('d/m/Y') . '. Renueva el seguro antes de rentarlo.';
        }

        $contratoTraslapado = Contrato::where('vehiculo_id', $vehiculo->id)
            ->where('estado_contrato', EstadoContratoEnum::ACTIVO->value)
            ->where(function ($query) use ($entrega, $devolucion) {
                $query->where('fecha_hora_entrega', '<', $devolucion)
                    ->where('fecha_hora_devolucion', '>', $entrega);
            })
            ->exists();

        if ($contratoTraslapado) {
            return 'El vehículo ya tiene un contrato activo que se traslapa con esas fechas';
        }

        $tieneContratoActivo = Contrato::where('cliente_id', $reserva->cliente_id)
            ->where('estado_contrato', EstadoContratoEnum::ACTIVO->value)
            ->exists();

        if ($tieneContratoActivo) {
            return 'Este cliente ya tiene un contrato activo. No se puede generar otro hasta cerrarlo.';
        }

        $contratoConDeuda = Contrato::where('cliente_id', $reserva->cliente_id)
            ->where('estado_contrato', EstadoContratoEnum::FINALIZADO->value)
            ->where('estado_pago', '!=', EstadoPagoEnum::PAGADO->value)
            ->first();

        if ($contratoConDeuda) {
            return 'El cliente tiene un saldo pendiente en el contrato ' . $contratoConDeuda->numero_contrato . '. Debe pagarlo antes de rentar otro vehículo.';
        }

        $porcentajeMaximo = (float) config('app.descuento_maximo_porcentaje');
        $porcentajeEnviado = (float) ($this->descuento_porcentaje ?? 0);

        if ($porcentajeEnviado > $porcentajeMaximo) {
            return 'El descuento no puede ser mayor al ' . $porcentajeMaximo . '%';
        }

        return null;
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
