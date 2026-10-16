<?php

namespace App\Http\Requests\ClienteController;

use App\Enums\RolEnum;
use App\Rules\DuiValido;
use App\Rules\LicenciaValida;
use App\Rules\NombrePersonaValido;
use App\Rules\TelefonoValido;
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
        $fechaMaxima = now()->addYears(10)->toDateString();

        return [
            'nombre'               => ['required', 'string', 'max:100', new NombrePersonaValido()],
            'dui'                  => ['required', 'string', 'max:20', 'unique:clientes,dui', new DuiValido()],
            'vencimiento_dui'      => ['required', 'date_format:Y-m-d', 'after:today', 'before_or_equal:' . $fechaMaxima],
            'numero_licencia'      => ['required', 'string', 'max:30', 'unique:clientes,numero_licencia', new LicenciaValida()],
            'vencimiento_licencia' => ['required', 'date_format:Y-m-d', 'after:today', 'before_or_equal:' . $fechaMaxima],
            'telefono'             => ['required', 'string', 'max:25', 'unique:clientes,telefono', new TelefonoValido()],
            'municipio_id'         => ['required', 'integer', 'exists:municipios,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'vencimiento_dui.date_format'          => 'La fecha de vencimiento del DUI no es válida.',
            'vencimiento_dui.before_or_equal'      => 'Revisa la fecha del DUI: no puede ser más de 10 años en el futuro.',
            'vencimiento_licencia.date_format'     => 'La fecha de vencimiento de la licencia no es válida.',
            'vencimiento_licencia.before_or_equal' => 'Revisa la fecha de la licencia: no puede ser más de 10 años en el futuro.',
            'nombre.max'                           => 'El nombre no puede tener más de 100 caracteres.',
            'nombre.required'                      => 'El nombre es obligatorio.',
            'telefono.unique'                      => 'Ya existe un cliente registrado con ese teléfono.',
            'dui.required'                         => 'El DUI es obligatorio.',
            'dui.unique'                           => 'Ya existe un cliente registrado con ese DUI.',
            'vencimiento_dui.required'             => 'La fecha de vencimiento del DUI es obligatoria.',
            'vencimiento_dui.after'                => 'El DUI debe estar vigente (posterior a hoy).',
            'numero_licencia.required'             => 'El número de licencia es obligatorio.',
            'numero_licencia.unique'               => 'Ya existe un cliente registrado con ese número de licencia.',
            'vencimiento_licencia.required'        => 'La fecha de vencimiento de la licencia es obligatoria.',
            'vencimiento_licencia.after'           => 'La licencia debe estar vigente (posterior a hoy).',
            'telefono.required'                    => 'El teléfono es obligatorio.',
            'municipio_id.required'                => 'Debe seleccionar un municipio.',
            'municipio_id.exists'                  => 'El municipio seleccionado no existe.',
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
