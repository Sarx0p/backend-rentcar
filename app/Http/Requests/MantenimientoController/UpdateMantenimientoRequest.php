<?php

namespace App\Http\Requests\MantenimientoController;

use App\Enums\RolEnum;
use App\Enums\TipoMantenimientoEnum;
use App\Enums\EstadoMantenimientoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateMantenimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth('api')->user();

        return $user->hasRole(RolEnum::ADMINISTRADOR->value)
            || $user->hasRole(RolEnum::EMPLEADO->value);
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('descripcion')) {
            $data['descripcion'] = $this->descripcion ? trim(strip_tags($this->descripcion)) : null;
        }

        if ($this->has('lugar')) {
            $data['lugar'] = $this->lugar ? trim(strip_tags($this->lugar)) : null;
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        return [
            'tipo_mantenimiento' => 'sometimes|in:' . implode(',', array_column(TipoMantenimientoEnum::cases(), 'value')),
            'descripcion'        => 'sometimes|nullable|string|max:250',
            'costo'              => 'sometimes|numeric|min:0|max:999999.99|regex:/^\d+(\.\d{1,2})?$/',
            'lugar'              => 'sometimes|string|max:150',
            'estado'             => 'sometimes|in:' . implode(',', array_column(EstadoMantenimientoEnum::cases(), 'value')),
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_mantenimiento.in' => 'El tipo de mantenimiento no es válido.',
            'costo.numeric'         => 'El costo debe ser un valor numérico.',
            'costo.min'             => 'El costo no puede ser negativo.',
            'costo.max'             => 'El costo excede el límite permitido.',
            'costo.regex'           => 'El costo debe tener como máximo 2 decimales.',
            'lugar.max'             => 'El lugar no puede exceder los 150 caracteres.',
            'descripcion.max'       => 'La descripción no puede exceder los 250 caracteres.',
            'estado.in'             => 'El estado no es válido.',
        ];
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para actualizar mantenimientos',
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
