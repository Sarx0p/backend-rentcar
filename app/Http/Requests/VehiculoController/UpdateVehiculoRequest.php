<?php

namespace App\Http\Requests\VehiculoController;

use App\Enums\RolEnum;
use App\Models\Modelo;
use App\Models\Vehiculo;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth('api')->user();

        return $user->hasRole(RolEnum::ADMINISTRADOR->value) || $user->hasRole(RolEnum::EMPLEADO->value);
    }

    public function rules(): array
    {
        $id = $this->route('vehiculo');

        return [
            'anio'                => 'sometimes|integer|min:1980|max:2050',
            'color'               => 'sometimes|string|max:30',
            'placa'               => ['sometimes', 'string', 'max:20', Rule::unique('vehiculos', 'placa')->ignore($id)],
            'capacidad_pasajeros' => 'sometimes|integer|min:1|max:255',
            'observaciones'       => 'sometimes|nullable|string|max:400',
            'propietario_id'      => 'sometimes|integer|exists:propietarios,id',
            'categoria_id'        => 'sometimes|integer|exists:categorias,id',
            'modelo_id'           => 'sometimes|integer|exists:modelos,id',
        ];
    }

    public function messages(): array
    {
        return [
            'anio.integer'           => 'El año debe ser un número entero.',
            'anio.min'               => 'El año no puede ser menor a 1980.',
            'anio.max'               => 'El año no puede ser mayor a 2050.',
            'placa.unique'           => 'Ya existe un vehículo registrado con esa placa.',
            'propietario_id.exists'  => 'El propietario seleccionado no existe.',
            'categoria_id.exists'    => 'La categoría seleccionada no existe.',
            'modelo_id.exists'       => 'El modelo seleccionado no existe.',
        ];
    }

    /**
     * La capacidad del vehículo no puede pasar la capacidad máxima de su modelo.
     * Si no se envía el modelo o la capacidad, se usan los que ya tiene el vehículo.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->hasAny(['capacidad_pasajeros', 'modelo_id'])) {
                return;
            }

            if ($validator->errors()->hasAny(['capacidad_pasajeros', 'modelo_id'])) {
                return;
            }

            $vehiculo = Vehiculo::find($this->route('vehiculo'));
            $modelo = Modelo::find($this->input('modelo_id', $vehiculo?->modelo_id));
            $capacidad = $this->input('capacidad_pasajeros', $vehiculo?->capacidad_pasajeros);

            if ($modelo && $capacidad > $modelo->capacidad_maxima) {
                $validator->errors()->add(
                    'capacidad_pasajeros',
                    "El modelo {$modelo->nombre} acepta máximo {$modelo->capacidad_maxima} pasajeros."
                );
            }
        });
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para realizar esta acción.',
        ], 403));
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'Faltan campos requeridos.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
