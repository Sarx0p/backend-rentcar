<?php

namespace App\Http\Requests\ClienteController;

use App\Enums\RolEnum;
use App\Models\Cliente;
use App\Rules\DuiValido;
use App\Rules\LicenciaValida;
use App\Rules\NombrePersonaValido;
use App\Rules\PasaporteValido;
use App\Rules\TelefonoValido;
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
        $id          = $this->route('cliente');
        $fechaMaxima = now()->addYears(10)->toDateString();

        return [
            'nombre'               => ['sometimes', 'required', 'string', 'max:100', new NombrePersonaValido()],
            'dui'                  => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('clientes', 'dui')->ignore($id), new DuiValido()],
            'vencimiento_dui'      => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after:today', 'before_or_equal:' . $fechaMaxima],
            'pasaporte'            => ['sometimes', 'nullable', 'string', 'max:30', Rule::unique('clientes', 'pasaporte')->ignore($id), new PasaporteValido()],
            'numero_licencia'      => ['sometimes', 'required', 'string', 'max:30', Rule::unique('clientes', 'numero_licencia')->ignore($id), new LicenciaValida()],
            'vencimiento_licencia' => ['sometimes', 'required', 'date_format:Y-m-d', 'after:today', 'before_or_equal:' . $fechaMaxima],
            'telefono'             => ['sometimes', 'required', 'string', 'max:25', Rule::unique('clientes', 'telefono')->ignore($id), new TelefonoValido()],
            'municipio_id'         => ['sometimes', 'nullable', 'integer', 'exists:municipios,id'],
            'pais_id'              => ['sometimes', 'nullable', 'integer', 'exists:pais,id'],
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
            'telefono.unique'                      => 'Ya existe otro cliente registrado con ese teléfono.',
            'dui.unique'                           => 'Ya existe otro cliente registrado con ese DUI.',
            'pasaporte.unique'                     => 'Ya existe otro cliente registrado con ese pasaporte.',
            'numero_licencia.unique'               => 'Ya existe otro cliente registrado con ese número de licencia.',
            'vencimiento_dui.after'                => 'El DUI debe estar vigente (posterior a hoy).',
            'vencimiento_licencia.after'           => 'La licencia debe estar vigente (posterior a hoy).',
            'municipio_id.exists'                  => 'El municipio seleccionado no existe.',
            'pais_id.exists'                       => 'El país seleccionado no existe.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $id      = $this->route('cliente');
            $cliente = $id ? Cliente::find($id) : null;

            $tieneDui       = $this->filled('dui')       || ($cliente && !is_null($cliente->dui));
            $tienePasaporte = $this->filled('pasaporte') || ($cliente && !is_null($cliente->pasaporte));

            if (!$tieneDui && !$tienePasaporte) {
                $validator->errors()->add('documento', 'Debe registrar un DUI o un pasaporte.');
                return;
            }

            if ($tienePasaporte) {
                $tienePais = $this->filled('pais_id') || ($cliente && !is_null($cliente->pais_id));
                if (!$tienePais) {
                    $validator->errors()->add('pais_id', 'Debe seleccionar el país del pasaporte.');
                }
            }

            if ($tieneDui) {
                $tieneVencimientoDui = $this->filled('vencimiento_dui') || ($cliente && !is_null($cliente->vencimiento_dui));
                $tieneMunicipio      = $this->filled('municipio_id')    || ($cliente && !is_null($cliente->municipio_id));

                if (!$tieneVencimientoDui) {
                    $validator->errors()->add('vencimiento_dui', 'La fecha de vencimiento del DUI es obligatoria.');
                }
                if (!$tieneMunicipio) {
                    $validator->errors()->add('municipio_id', 'Debe seleccionar un municipio.');
                }
            }
        });
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
