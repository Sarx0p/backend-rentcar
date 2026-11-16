<?php

namespace App\Http\Controllers;

use App\Models\Pais;

class PaisController extends Controller
{
    /**
     * Listar todos los países.
     */
    public function index()
    {
        try {
            $paises = Pais::orderBy('nombre')->get(['id', 'nombre']);

            return response()->json([
                'status' => 'success',
                'data'   => $paises,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }
}
