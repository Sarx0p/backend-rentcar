<?php

namespace App\Http\Requests\IncidenciaController;

use App\Enums\RolEnum;
use App\Enums\TipoIncidenciaEnum;
use App\Enums\IncidenciaTipoResponsableEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreIncidenciaRequest extends FormRequest
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
            'vehiculo_id'      => 'required|exists:vehiculos,id',
            'contrato_id'      => 'nullable|exists:contratos,id',
            'tipo_incidencia'  => 'required|in:' . implode(',', array_column(TipoIncidenciaEnum::cases(), 'value')),
            'responsable_tipo' => 'required|in:' . implode(',', array_column(IncidenciaTipoResponsableEnum::cases(), 'value')),
            'descripcion'      => 'nullable|string|max:500',
            'fecha'            => 'required|date',
            'costo'            => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Regla de negocio: si el responsable es CLIENTE, el contrato es obligatorio
     * (para poder identificar a cuál cliente específico corresponde el cobro).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $responsable = $this->input('responsable_tipo');

            if ($responsable === IncidenciaTipoResponsableEnum::CLIENTE->value && !$this->filled('contrato_id')) {
                $validator->errors()->add(
                    'contrato_id',
                    'Debe indicar el contrato cuando el responsable es el CLIENTE.'
                );
            }
        });
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'vehiculo_id.required'      => 'Debe seleccionar un vehículo.',
            'vehiculo_id.exists'        => 'El vehículo seleccionado no existe.',
            'contrato_id.exists'        => 'El contrato indicado no existe.',
            'tipo_incidencia.required'  => 'El tipo de incidencia es obligatorio.',
            'tipo_incidencia.in'        => 'El tipo de incidencia no es válido.',
            'responsable_tipo.required' => 'El responsable es obligatorio.',
            'responsable_tipo.in'       => 'El responsable no es válido.',
            'fecha.required'            => 'La fecha es obligatoria.',
            'costo.numeric'             => 'El costo debe ser un valor numérico.',
        ];
    }

    /**
     * Si la autorización falla.
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para registrar incidencias',
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
