<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Incidencia;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class HistorialController extends Controller
{
    /**
     * Resumen del cliente: Datos generales y contadores laterales
     */
    public function resumen(string $clienteId)
    {
        try {
            $cliente = Cliente::findOrFail($clienteId);

            $totalReservas = $cliente->reservas()->count();
            $totalContratos = $cliente->contratos()->count();

            // Obtiene IDs de contratos del cliente para contar sus incidencias
            $contratosIds = $cliente->contratos()->pluck('id');
            $totalIncidencias = Incidencia::whereIn('contrato_id', $contratosIds)->count();

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'id'         => $cliente->id,
                    'nombre'     => trim(($cliente->nombre ?? '') . ' ' . ($cliente->apellido ?? '')),
                    'dui'        => $cliente->dui,
                    'telefono'   => $cliente->telefono,
                    'contadores' => [
                        'reservas'    => $totalReservas,
                        'contratos'   => $totalContratos,
                        'incidencias' => $totalIncidencias,
                    ],
                ],
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cliente no encontrado',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
                'debug'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pestaña 1: Reservas del cliente
     */
    public function reservas(Request $request, string $clienteId)
    {
        try {
            $cliente = Cliente::findOrFail($clienteId);

            $reservas = $cliente->reservas()
                ->with(['vehiculo'])
                ->latest()
                ->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $reservas,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cliente no encontrado',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
                'debug'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pestaña 2: Contratos del cliente
     */
    public function contratos(Request $request, string $clienteId)
    {
        try {
            $cliente = Cliente::findOrFail($clienteId);

            $contratos = $cliente->contratos()
                ->with(['vehiculo'])
                ->latest()
                ->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $contratos,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cliente no encontrado',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
                'debug'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pestaña 3: Incidencias del cliente (a través de sus contratos)
     */
    public function incidencias(Request $request, string $clienteId)
    {
        try {
            $cliente = Cliente::findOrFail($clienteId);

            $contratosIds = $cliente->contratos()->pluck('id');

            $incidencias = Incidencia::whereIn('contrato_id', $contratosIds)
                ->with(['vehiculo', 'contrato', 'usuario'])
                ->latest()
                ->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $incidencias,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cliente no encontrado',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
                'debug'   => $e->getMessage(),
            ], 500);
        }
    }
}
