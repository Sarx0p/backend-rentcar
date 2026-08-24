<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Incidencia;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class HistorialController extends Controller
{
   
    public function resumen(string $clienteId)
    {
        try {
            $cliente = Cliente::findOrFail($clienteId);

            $totalReservas = $cliente->reservas()->count();
            $totalContratos = $cliente->contratos()->count();

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
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

   
    public function reservas(Request $request, string $clienteId)
    {
        try {
            $cliente = Cliente::findOrFail($clienteId);

            $reservas = $cliente->reservas()
                ->with(['vehiculo'])
                ->when($request->filled('estado'), function ($query) use ($request) {
                    $query->where('estado', $request->estado);
                })
                ->when($request->filled('fecha_inicio') && $request->filled('fecha_fin'), function ($query) use ($request) {
                    $query->whereDate('fecha_inicio', '>=', $request->fecha_inicio)
                        ->whereDate('fecha_fin', '<=', $request->fecha_fin);
                })
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
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    
    public function contratos(Request $request, string $clienteId)
    {
        try {
            $cliente = Cliente::findOrFail($clienteId);

            $contratos = $cliente->contratos()
                ->with(['vehiculo'])
                ->when($request->filled('estado'), function ($query) use ($request) {
                    $query->where('estado_contrato', $request->estado);
                })
                ->when($request->filled('fecha_inicio') && $request->filled('fecha_fin'), function ($query) use ($request) {
                    $query->whereDate('fecha_hora_entrega', '>=', $request->fecha_inicio)
                        ->whereDate('fecha_hora_devolucion', '<=', $request->fecha_fin);
                })
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
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    
    public function incidencias(Request $request, string $clienteId)
    {
        try {
            $cliente = Cliente::findOrFail($clienteId);

            $contratosIds = $cliente->contratos()->pluck('id');

            $incidencias = Incidencia::whereIn('contrato_id', $contratosIds)
                ->with(['vehiculo', 'contrato', 'usuario'])
                ->when($request->filled('estado'), function ($query) use ($request) {
                    $query->where('estado_incidencia', $request->estado);
                })
                ->when($request->filled('fecha_inicio') && $request->filled('fecha_fin'), function ($query) use ($request) {
                    $query->whereDate('fecha', '>=', $request->fecha_inicio)
                        ->whereDate('fecha', '<=', $request->fecha_fin);
                })
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
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }
}