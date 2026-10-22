<?php

namespace App\Http\Requests\ContratoController;

use App\Enums\RolEnum;
use App\Models\Contrato;
use App\Enums\EstadoContratoEnum;
use App\Enums\CargoAdicionalEstadoEnum;
use App\Enums\IncidenciaEstadoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AnularContratoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth('api')->user();

        return $user && $user->hasRole(RolEnum::ADMINISTRADOR->value);
    }

    public function rules(): array
    {
        return [
            'motivo' => 'required|string|min:5|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Debe indicar el motivo de la anulación.',
            'motivo.min'      => 'El motivo debe tener al menos 5 caracteres.',
            'motivo.max'      => 'El motivo no puede exceder los 500 caracteres.',
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
        $contrato = Contrato::find($this->route('id'));

        if (!$contrato) {
            return null;
        }

        if ($contrato->estado_contrato !== EstadoContratoEnum::ACTIVO->value) {
            return 'Solo se puede anular un contrato en estado ACTIVO';
        }

        if ($contrato->contrato_origen_id) {
            return 'No se puede anular un contrato de continuación por cambio de vehículo';
        }

        if ($contrato->montoPagado() > 0) {
            return 'El contrato tiene pagos confirmados, primero debe cancelarlos';
        }

        $tieneCargos = $contrato->cargosAdicionales()
            ->where('estado_cargo', '!=', CargoAdicionalEstadoEnum::ANULADO->value)
            ->exists();

        $tieneIncidencias = $contrato->incidencias()
            ->where('estado_incidencia', '!=', IncidenciaEstadoEnum::ANULADA->value)
            ->exists();

        if ($tieneCargos || $tieneIncidencias) {
            return 'El contrato tiene cargos o incidencias vigentes, primero debe anularlos';
        }

        return null;
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para anular contratos',
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
