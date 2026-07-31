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

    public function rules(): array
    {
        return [
            'tipo_mantenimiento' => 'sometimes|in:' . implode(',', array_column(TipoMantenimientoEnum::cases(), 'value')),
            'descripcion'        => 'sometimes|nullable|string|max:250',
            'costo'              => 'sometimes|numeric|min:0',
            'fecha'              => 'sometimes|date',
            'lugar'              => 'sometimes|string|max:150',
            'estado'             => 'sometimes|in:' . implode(',', array_column(EstadoMantenimientoEnum::cases(), 'value')),
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_mantenimiento.in' => 'El tipo de mantenimiento no es válido.',
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
