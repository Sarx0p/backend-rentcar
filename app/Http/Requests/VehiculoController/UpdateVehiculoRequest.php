<?php

namespace App\Http\Requests\VehiculoController;

use App\Enums\RolEnum;
use App\Enums\VehiculoEstadoEnum;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateVehiculoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth('api')->user();

        return $user->hasRole(RolEnum::ADMINISTRADOR->value) || $user->hasRole(RolEnum::EMPLEADO->value);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $id = $this->route('vehiculo');

        return [
            'anio'                => 'sometimes|integer|min:1980|max:2050',
            'color'               => 'sometimes|string|max:30',
            'placa'               => ['sometimes', 'string', 'max:20', Rule::unique('vehiculos', 'placa')->ignore($id)],
            'capacidad_pasajeros' => 'sometimes|integer|min:1|max:255',
            'estado'              => 'sometimes|in:' . implode(',', array_column(VehiculoEstadoEnum::cases(), 'value')),
            'observaciones'       => 'sometimes|nullable|string|max:400',
            'propietario_id'      => 'sometimes|integer|exists:propietarios,id',
            'categoria_id'        => 'sometimes|integer|exists:categorias,id',
            'modelo_id'           => 'sometimes|integer|exists:modelos,id',
        ];
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'anio.integer'           => 'El año debe ser un número entero.',
            'anio.min'               => 'El año no puede ser menor a 1980.',
            'anio.max'               => 'El año no puede ser mayor a 2050.',
            'placa.unique'           => 'Ya existe un vehículo registrado con esa placa.',
            'estado.in'              => 'El estado no es válido.',
            'propietario_id.exists'  => 'El propietario seleccionado no existe.',
            'categoria_id.exists'    => 'La categoría seleccionada no existe.',
            'modelo_id.exists'       => 'El modelo seleccionado no existe.',
        ];
    }

    /**
     * Si la autorización falla (rol incorrecto).
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para realizar esta acción.',
        ], 403));
    }

    /**
     * Si la validación falla.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'Faltan campos requeridos.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
