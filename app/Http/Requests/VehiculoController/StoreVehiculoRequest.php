<?php

namespace App\Http\Requests\VehiculoController;

use App\Enums\RolEnum;
use App\Enums\VehiculoEstadoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreVehiculoRequest extends FormRequest
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
        return [
            'anio'                => 'required|integer|min:1980|max:2050',
            'color'               => 'required|string|max:30',
            'placa'               => 'required|string|max:20|unique:vehiculos,placa',
            'capacidad_pasajeros' => 'required|integer|min:1|max:255',
            'estado'              => 'required|in:' . implode(',', array_column(VehiculoEstadoEnum::cases(), 'value')),
            'observaciones'       => 'sometimes|nullable|string|max:400',
            'propietario_id'      => 'required|integer|exists:propietarios,id',
            'categoria_id'        => 'required|integer|exists:categorias,id',
            'modelo_id'           => 'required|integer|exists:modelos,id',
        ];
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'anio.required'                => 'El año es obligatorio.',
            'anio.integer'                 => 'El año debe ser un número entero.',
            'anio.min'                     => 'El año no puede ser menor a 1980.',
            'anio.max'                     => 'El año no puede ser mayor a 2050.',
            'color.required'               => 'El color es obligatorio.',
            'placa.required'               => 'La placa es obligatoria.',
            'placa.unique'                 => 'Ya existe un vehículo registrado con esa placa.',
            'capacidad_pasajeros.required' => 'La capacidad de pasajeros es obligatoria.',
            'estado.required'              => 'El estado es obligatorio.',
            'estado.in'                    => 'El estado no es válido.',
            'propietario_id.required'      => 'Debe seleccionar un propietario.',
            'propietario_id.exists'        => 'El propietario seleccionado no existe.',
            'categoria_id.required'        => 'Debe seleccionar una categoría.',
            'categoria_id.exists'          => 'La categoría seleccionada no existe.',
            'modelo_id.required'           => 'Debe seleccionar un modelo.',
            'modelo_id.exists'             => 'El modelo seleccionado no existe.',
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
