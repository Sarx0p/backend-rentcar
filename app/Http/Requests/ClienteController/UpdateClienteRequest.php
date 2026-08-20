<?php

namespace App\Http\Requests\ClienteController;

use App\Enums\RolEnum;
use App\Rules\DuiValido;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth('api')->user();

        return $user->hasRole(RolEnum::ADMINISTRADOR->value)
            || $user->hasRole(RolEnum::EMPLEADO->value);
    }

    public function rules(): array
    {
        $id = $this->route('cliente');

        return [
            'nombre'               => 'sometimes|string|max:100',
            'dui'                  => ['sometimes', 'string', 'max:20', Rule::unique('clientes', 'dui')->ignore($id), new DuiValido()],
            'vencimiento_dui'      => 'sometimes|date|after:today',
            'numero_licencia'      => ['sometimes', 'string', 'max:30', Rule::unique('clientes', 'numero_licencia')->ignore($id)],
            'vencimiento_licencia' => 'sometimes|date|after:today',
            'telefono'             => 'sometimes|string|max:25',
            'municipio_id'         => 'sometimes|exists:municipios,id',
        ];
    }

    public function messages(): array
    {
        return [
            'dui.unique'                  => 'Ya existe otro cliente registrado con ese DUI.',
            'numero_licencia.unique'      => 'Ya existe otro cliente registrado con ese número de licencia.',
            'vencimiento_dui.after'       => 'El DUI debe estar vigente (posterior a hoy).',
            'vencimiento_licencia.after'  => 'La licencia debe estar vigente (posterior a hoy).',
            'municipio_id.exists'         => 'El municipio seleccionado no existe.',
        ];
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para actualizar clientes',
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
