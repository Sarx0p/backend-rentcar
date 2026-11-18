<?php

namespace App\Http\Controllers;

use App\Models\Pais;
use Illuminate\Http\Request;

class PaisController extends Controller
{
    /**
     * Listar todos los países.
     */
    public function index(Request $request)
    {
        try {
            $paises = Pais::query()
                ->when($request->search, function ($query, $search) {
                    $query->where('nombre', 'like', '%' . $search . '%');
                })
                ->orderBy('nombre')
                ->get(['id', 'nombre']);

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
