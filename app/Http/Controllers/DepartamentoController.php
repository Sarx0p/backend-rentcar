<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DepartamentoController extends Controller
{
    /**
     * Listar todos los departamentos.
     */
    public function index()
    {
        try {
            $departamentos = Departamento::orderBy('nombre')->get(['id', 'nombre']);

            return response()->json([
                'status' => 'success',
                'data'   => $departamentos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Listar los municipios de un departamento específico.
     */
    public function porDepartamento(string $departamentoId)
    {
        try {
            $departamento = Departamento::findOrFail($departamentoId);

            $municipios = $departamento->municipios()
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'departamento_id']);

            return response()->json([
                'status' => 'success',
                'data'   => $municipios,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Departamento no encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }
}
