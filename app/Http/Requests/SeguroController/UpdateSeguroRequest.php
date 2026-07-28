<?php

namespace App\Http\Requests\SeguroController;

use App\Enums\RolEnum;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;


class UpdateSeguroRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth("api")->user();
        return $user->hasRole(RolEnum::ADMINISTRADOR->value);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Captura el ID del seguro desde la ruta (url) para ignorarlo en la validación unique
        // Si tu parámetro en la ruta se llama diferente (ej: $router->put('seguros/{id}',...)), cambia 'seguro' por 'id'
        $seguroId = $this->route('seguro');

        return [
            'vehiculo_id'       => 'required|integer|exists:vehiculos,id',
            'aseguradora'       => 'required|string|max:150',
            'numero_poliza'     => 'required|string|max:50|unique:seguros,numero_poliza,' . $seguroId,
            'fecha_inicio'      => 'required|date',
            'fecha_vencimiento' => 'required|date|after:fecha_inicio',
            'cobertura'         => 'nullable|string',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'vehiculo_id.required'       => 'El vehículo es obligatorio.',
            'vehiculo_id.integer'        => 'El identificador del vehículo debe ser un número entero.',
            'vehiculo_id.exists'         => 'El vehículo seleccionado no existe en nuestro registro.',

            'aseguradora.required'       => 'El nombre de la aseguradora es obligatorio.',
            'aseguradora.string'         => 'El nombre de la aseguradora debe ser un texto válido.',
            'aseguradora.max'            => 'El nombre de la aseguradora no debe exceder los 150 caracteres.',

            'numero_poliza.required'     => 'El número de póliza es obligatorio.',
            'numero_poliza.string'       => 'El número de póliza debe ser un formato de texto válido.',
            'numero_poliza.max'          => 'El número de póliza no debe exceder los 50 caracteres.',
            'numero_poliza.unique'       => 'Este número de póliza ya se encuentra registrado en otro vehículo.',

            'fecha_inicio.required'      => 'La fecha de inicio de la póliza es obligatoria.',
            'fecha_inicio.date'          => 'La fecha de inicio debe ser una fecha válida.',

            'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria.',
            'fecha_vencimiento.date'     => 'La fecha de vencimiento debe ser una fecha válida.',
            'fecha_vencimiento.after'    => 'La fecha de vencimiento debe ser posterior a la fecha de inicio.',

            'cobertura.string'           => 'La descripción de la cobertura debe ser un texto válido.',
        ];
    }
    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para actualizar usuarios.',
        ], 403));
    }

     protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'Error de validación.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
