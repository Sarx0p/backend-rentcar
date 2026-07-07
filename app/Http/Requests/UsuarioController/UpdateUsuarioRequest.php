<?php

namespace App\Http\Requests\UsuarioController;

use App\Enums\RolEnum;
use App\Enums\UsuarioEstadoEnum;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUsuarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth('api')->user();

        return $user->hasRole(RolEnum::ADMINISTRADOR->value);
    }

    /**
     * Normaliza 'rol' y 'estado' a mayúsculas antes de validar.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('rol')) {
            $this->merge(['rol' => strtoupper($this->rol)]);
        }

        if ($this->filled('estado')) {
            $this->merge(['estado' => strtoupper($this->estado)]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('usuario');

        return [
            'nombre'   => 'sometimes|string|max:100',
            'apellido' => 'sometimes|string|max:100',
            'correo'   => ['sometimes', 'email', 'max:150', Rule::unique('users', 'correo')->ignore($id)],
            'password' => 'sometimes|string|min:8|max:255',
            'estado'   => ['sometimes', Rule::enum(UsuarioEstadoEnum::class)],
            'rol'      => ['sometimes', Rule::enum(RolEnum::class)],
        ];
    }

   
    public function messages(): array
    {
        return [
            'correo.email'  => 'El correo no tiene un formato válido.',
            'correo.unique' => 'Ese correo ya está en uso por otro usuario.',
            'password.min'  => 'La contraseña debe tener al menos 8 caracteres.',
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
