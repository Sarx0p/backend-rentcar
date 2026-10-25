<?php

namespace App\Http\Requests\ContratoController;

use App\Enums\RolEnum;
use App\Support\RangoFechas;
use App\Models\Contrato;
use App\Models\Reserva;
use App\Models\Seguro;
use App\Models\Vehiculo;
use App\Models\Cliente;
use App\Enums\EstadoReservaEnum;
use App\Enums\EstadoContratoEnum;
use App\Enums\EstadoPagoEnum;
use App\Enums\VehiculoEstadoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreContratoDirectoRequest extends FormRequest
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
            'cliente_id'                => 'required|exists:clientes,id',
            'vehiculo_id'               => 'required|exists:vehiculos,id',
            'dias_acordados'            => 'required|integer|min:1|max:' . $this->diasMaximos(),
            'precio_por_dia'            => 'sometimes|numeric|min:0.1',
            'nivel_combustible_entrega' => 'required|string|in:E,1/4,1/2,3/4,F',
            // Cambiado para recibir porcentaje
            'descuento_porcentaje'      => 'sometimes|numeric|min:0|max:100',
            'observaciones_entrega'     => 'sometimes|nullable|string|max:500',
            'observaciones'             => 'sometimes|nullable|string|max:500',
        ];
    }

    private function diasMaximos(): int
    {
        return (int) now()->startOfDay()->diffInDays(RangoFechas::fechaMaxima()->startOfDay());
    }

    public function messages(): array
    {
        return [
            'cliente_id.required'                => 'Debe seleccionar un cliente.',
            'cliente_id.exists'                  => 'El cliente seleccionado no existe.',
            'vehiculo_id.required'               => 'Debe seleccionar un vehículo.',
            'vehiculo_id.exists'                 => 'El vehículo seleccionado no existe.',
            'dias_acordados.required'            => 'Debe indicar los días acordados.',
            'dias_acordados.integer'             => 'Los días acordados deben ser un número entero.',
            'dias_acordados.min'                 => 'Los días acordados deben ser al menos 1.',
            'dias_acordados.max'                 => 'La devolución no puede ser posterior al ' . RangoFechas::fechaMaxima()->format('d/m/Y') . ' (máximo ' . $this->diasMaximos() . ' días).',
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
        $cliente = Cliente::find($this->cliente_id);

        if (!$cliente) {
            return null;
        }

        if ($cliente->vencimiento_licencia->isPast()) {
            return 'El cliente tiene la licencia vencida, no puede rentar un vehículo';
        }

        $tieneContratoActivo = Contrato::where('cliente_id', $cliente->id)
            ->where('estado_contrato', EstadoContratoEnum::ACTIVO->value)
            ->exists();

        if ($tieneContratoActivo) {
            return 'Este cliente ya tiene un contrato activo. No se puede generar otro hasta cerrarlo.';
        }

        $contratoConDeuda = Contrato::where('cliente_id', $cliente->id)
            ->where('estado_contrato', EstadoContratoEnum::FINALIZADO->value)
            ->where('estado_pago', '!=', EstadoPagoEnum::PAGADO->value)
            ->first();

        if ($contratoConDeuda) {
            return 'El cliente tiene un saldo pendiente en el contrato ' . $contratoConDeuda->numero_contrato . '. Debe pagarlo antes de rentar otro vehículo.';
        }

        $vehiculo = Vehiculo::with('categoria')->find($this->vehiculo_id);

        if (!$vehiculo) {
            return null;
        }

        if ($vehiculo->estado !== VehiculoEstadoEnum::DISPONIBLE->value) {
            return 'El vehículo no está disponible, estado actual: ' . $vehiculo->estado;
        }
        $fechaEntrega    = now();
        $fechaDevolucion = $fechaEntrega->copy()->addDays($this->dias_acordados);

        if ($cliente->vencimiento_dui->isPast()) {
            return 'El cliente tiene el DUI vencido, no puede rentar un vehículo';
        }

        if ($cliente->vencimiento_licencia->lte($fechaDevolucion->copy()->startOfDay())) {
            return 'La licencia del cliente vence el ' . $cliente->vencimiento_licencia->format('d/m/Y') . ', antes de la devolución del vehículo';
        }

        if ($cliente->vencimiento_dui->lte($fechaDevolucion->copy()->startOfDay())) {
            return 'El DUI del cliente vence el ' . $cliente->vencimiento_dui->format('d/m/Y') . ', antes de la devolución del vehículo';
        }

        if (Seguro::faltaCobertura($vehiculo->id, $fechaEntrega->toDateString(), $fechaDevolucion->toDateString())) {
            return 'El vehículo no tiene un seguro vigente que cubra hasta el ' . $fechaDevolucion->format('d/m/Y') . '. Renueva el seguro antes de rentarlo.';
        }
        $contratoTraslapado = Contrato::where('vehiculo_id', $vehiculo->id)
            ->where('estado_contrato', EstadoContratoEnum::ACTIVO->value)
            ->where(function ($query) use ($fechaEntrega, $fechaDevolucion) {
                $query->where('fecha_hora_entrega', '<', $fechaDevolucion)
                    ->where('fecha_hora_devolucion', '>', $fechaEntrega);
            })
            ->exists();

        if ($contratoTraslapado) {
            return 'El vehículo ya tiene un contrato activo que se traslapa con esas fechas';
        }

        $reservaTraslapada = Reserva::where('vehiculo_id', $vehiculo->id)
            ->whereIn('estado', [EstadoReservaEnum::PENDIENTE->value, EstadoReservaEnum::CONFIRMADA->value])
            ->where(function ($query) use ($fechaEntrega, $fechaDevolucion) {
                $query->whereDate('fecha_inicio', '<', $fechaDevolucion->toDateString())
                    ->whereDate('fecha_fin', '>', $fechaEntrega->toDateString());
            })
            ->exists();

        if ($reservaTraslapada) {
            return 'El vehículo tiene una reserva que se traslapa con estas fechas. No se puede generar un contrato directo.';
        }

        $reservaClienteTraslapada = Reserva::where('cliente_id', $this->cliente_id)
            ->whereIn('estado', [EstadoReservaEnum::PENDIENTE->value, EstadoReservaEnum::CONFIRMADA->value])
            ->where(function ($query) use ($fechaEntrega, $fechaDevolucion) {
                $query->whereDate('fecha_inicio', '<', $fechaDevolucion->toDateString())
                    ->whereDate('fecha_fin', '>', $fechaEntrega->toDateString());
            })
            ->exists();

        if ($reservaClienteTraslapada) {
            return 'Este cliente ya tiene una reserva activa que se traslapa con estas fechas. Genera el contrato desde esa reserva o cancélala primero.';
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
