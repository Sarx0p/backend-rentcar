<?php

namespace App\Http\Requests\IncidenciaController;

use App\Enums\RolEnum;
use App\Enums\TipoIncidenciaEnum;
use App\Enums\IncidenciaTipoResponsableEnum;
use App\Enums\IncidenciaEstadoEnum;
use App\Models\Incidencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateIncidenciaRequest extends FormRequest
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
     * No se permite cambiar vehiculo_id ni contrato_id de una incidencia ya registrada.
     */
    public function rules(): array
    {
        return [
            'tipo_incidencia'   => 'sometimes|in:' . implode(',', array_column(TipoIncidenciaEnum::cases(), 'value')),
            'responsable_tipo'  => 'sometimes|in:' . implode(',', array_column(IncidenciaTipoResponsableEnum::cases(), 'value')),
            'estado_incidencia' => 'sometimes|in:' . implode(',', array_column(IncidenciaEstadoEnum::cases(), 'value')),
            'descripcion'       => 'sometimes|nullable|string|max:500',
            'fecha'             => 'sometimes|date',
            'costo'             => 'sometimes|nullable|numeric|min:0',
        ];
    }

    /**
     * Regla de negocio: si el responsable pasa a ser CLIENTE (o ya lo era),
     * la incidencia debe tener un contrato asociado.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->filled('responsable_tipo')) {
                return;
            }

            if ($this->input('responsable_tipo') !== IncidenciaTipoResponsableEnum::CLIENTE->value) {
                return;
            }

            $incidencia = Incidencia::find($this->route('incidencia'));

            if ($incidencia && !$incidencia->contrato_id) {
                $validator->errors()->add(
                    'responsable_tipo',
                    'No se puede asignar responsable CLIENTE a una incidencia sin contrato asociado.'
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
            'tipo_incidencia.in'   => 'El tipo de incidencia no es válido.',
            'responsable_tipo.in'  => 'El responsable no es válido.',
            'estado_incidencia.in' => 'El estado de la incidencia no es válido.',
            'costo.numeric'        => 'El costo debe ser un valor numérico.',
        ];
    }

    /**
     * Si la autorización falla.
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para actualizar incidencias',
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
