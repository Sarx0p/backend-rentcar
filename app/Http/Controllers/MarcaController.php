<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Models\Marca;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarcaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            $marcas = Marca::orderBy('nombre')->get();

            if($marcas->isEmpty()){
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No hay marcas registradas.'
                ],404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => $marcas,
            ],200);

        } catch(\Exception $e){
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ],500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            $userAuth = auth('api')->user();

            if(!$userAuth->hasRole(RolEnum::ADMINISTRADOR->value)){
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para realizar esta acción.',
                ],403);
            }

            $request->validate([
                'nombre' => 'required|string|max:80|unique:marcas,nombre',
            ]);

            DB::beginTransaction();

            $marca = Marca::create([
                'nombre' => $request->nombre,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Marca registrada con exito.',
                'data'    => $marca,
            ],201);

        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Faltan campos requeridos.',
            ],422);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ],500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $marcas = Marca::findOrField($id);

            return response()->json([
                "status" => "succes",
                "data" => $marcas
            ],200);


        }
        catch(ModelNotFoundException){
            return response()->json([
                "status" => "errors",
                "message" => "No se encontraron marcas"
            ],404);

        }
        catch(\Exception $e){
            return response()->json([
                "status" => "errors",
                "message" => "Error interno de el servidor"
            ],500);

        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
         try{
            $userAuth = auth('api')->user();

            if(!$userAuth->hasRole(RolEnum::ADMINISTRADOR->value)){
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para realizar esta acción.',
                ],403);
            }

           $marcas = Marca::findOrFail($id);

           $marcas->update($request->only(["nombre"]));

           return response()->json([
            "message" => "Marca actualizada correctamente",
            "data" => $marcas
           ],201);



        }catch(ModelNotFoundException){
            return response()->json([
                "status" => "errors",
                "message" => "propietario no encontrado "
            ],404);

        }catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ],500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{

        }catch(\Exception $e){
            return response()->json([
                "ststus" => "Errors",
                "message" => "Error interno de el servidor"
            ]);
        }
    }
}
