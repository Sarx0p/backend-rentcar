<?php

namespace App\Http\Requests\ReporteController;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $inicioReportes = config('app.reportes_fecha_inicio');
        $hoy = now()->toDateString();

        return [
            'fecha_inicio' => ['required_with:fecha_fin', 'date_format:Y-m-d', 'after_or_equal:'.$inicioReportes, 'before_or_equal:'.$hoy],
            'fecha_fin' => ['required_with:fecha_inicio', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio', 'before_or_equal:'.$hoy],
            'dias' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'porcentaje_administracion' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'propietario_id' => ['sometimes', 'nullable', 'integer', 'exists:propietarios,id'],
        ];
    }

    public function messages(): array
    {
        $inicio = Carbon::parse(config('app.reportes_fecha_inicio'))->format('d/m/Y');

        return [
            'fecha_inicio.required_with' => 'Selecciona también la fecha de inicio.',
            'fecha_fin.required_with' => 'Selecciona también la fecha fin.',
            'fecha_inicio.date_format' => 'La fecha de inicio no es válida.',
            'fecha_fin.date_format' => 'La fecha fin no es válida.',
            'fecha_inicio.after_or_equal' => "La fecha de inicio no puede ser anterior al {$inicio}, desde cuando hay registros en el sistema.",
            'fecha_inicio.before_or_equal' => 'La fecha de inicio no puede ser posterior a hoy.',
            'fecha_fin.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha de inicio.',
            'fecha_fin.before_or_equal' => 'La fecha fin no puede ser posterior a hoy.',
            'dias.integer' => 'Los días deben ser un número entero.',
            'dias.min' => 'Los días deben ser al menos 1.',
            'dias.max' => 'Los días no pueden ser más de 365.',
            'porcentaje_administracion.min' => 'El porcentaje de administración no puede ser negativo.',
            'porcentaje_administracion.max' => 'El porcentaje de administración no puede ser mayor a 100.',
            'propietario_id.exists' => 'El propietario seleccionado no existe.',
            'porcentaje_administracion.numeric' => 'El porcentaje de administración debe ser un número.',
            'propietario_id.integer'            => 'El propietario seleccionado no es válido.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
