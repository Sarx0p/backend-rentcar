<?php

namespace App\Http\Requests\UsuarioController;

use App\Enums\RolEnum;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUsuarioRequest extends FormRequest
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
     * Normaliza el campo 'rol' a mayúsculas antes de validar.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('rol')) {
            $this->merge(['rol' => strtoupper($this->rol)]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre'   => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'correo'   => 'required|email|max:150|unique:users,correo',
            'password' => 'required|string|min:8|max:255',
            'rol'      => ['required', Rule::enum(RolEnum::class)],
        ];
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'nombre.required'   => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'correo.required'   => 'El correo es obligatorio.',
            'correo.email'      => 'El correo no tiene un formato válido.',
            'correo.unique'     => 'Ya existe un usuario con ese correo electrónico.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
            'rol.required'      => 'El rol es obligatorio.',
        ];
    }

    /**
     * Si la autorización falla.
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'No tienes permiso para crear usuarios.',
        ], 403));
    }

    /**
     * Si la validación falla.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'Error de validación.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
