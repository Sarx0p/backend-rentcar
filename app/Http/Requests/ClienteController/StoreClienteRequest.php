<?php

namespace App\Http\Requests\ClienteController;

use App\Enums\RolEnum;
use App\Rules\DuiValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreClienteRequest extends FormRequest
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
            'nombre'               => 'required|string|max:100',
            'dui'                  => ['required', 'string', 'max:20', 'unique:clientes,dui', new DuiValido()],
            'nacimiento_dui'       => 'required|date',
            'numero_licencia'      => 'required|string|max:30|unique:clientes,numero_licencia',
            'vencimiento_licencia' => 'required|date|after:today',
            'telefono'             => 'required|string|max:25',
            'municipio_id'         => 'required|exists:municipios,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'               => 'El nombre es obligatorio.',
            'dui.required'                   => 'El DUI es obligatorio.',
            'dui.unique'                     => 'Ya existe un cliente registrado con ese DUI.',
            'nacimiento_dui.required'        => 'La fecha del DUI es obligatoria.',
            'numero_licencia.required'       => 'El número de licencia es obligatorio.',
            'numero_licencia.unique'         => 'Ya existe un cliente registrado con ese número de licencia.',
            'vencimiento_licencia.required'  => 'La fecha de vencimiento de la licencia es obligatoria.',
            'vencimiento_licencia.after'     => 'La licencia debe estar vigente (posterior a hoy).',
            'telefono.required'              => 'El teléfono es obligatorio.',
            'municipio_id.required'          => 'Debe seleccionar un municipio.',
            'municipio_id.exists'            => 'El municipio seleccionado no existe.',
        ];
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para registrar clientes',
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
