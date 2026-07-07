<?php

namespace App\Http\Requests\PropietarioController;

use App\Enums\RolEnum;
use App\Enums\TipoPropietarioEnum;
use App\Models\Propietario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePropietarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth('api')->user();

        return $user->hasRole(RolEnum::ADMINISTRADOR->value)||$user->hasRole(RolEnum::EMPLEADO->value);
    }

    public function rules(): array
    {
        return [
            'nombre'           => 'sometimes|string|max:100',
            'telefono'         => 'sometimes|string|max:25',
            'tipo_propietario' => 'sometimes|in:' . implode(',', array_column(TipoPropietarioEnum::cases(), 'value')),
        ];
    }

   
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('tipo_propietario') !== TipoPropietarioEnum::PROPIO->value) {
                return;
            }

            $idActual = $this->route('propietario');

            $existeOtroDueno = Propietario::where('tipo_propietario', TipoPropietarioEnum::PROPIO->value)
                ->where('id', '!=', $idActual)
                ->exists();

            if ($existeOtroDueno) {
                $validator->errors()->add(
                    'tipo_propietario',
                    'Ya existe otro propietario registrado como PROPIO (dueño del negocio). No se puede duplicar.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'tipo_propietario.in' => 'El tipo de propietario no es válido.',
        ];
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para actualizar propietarios',
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