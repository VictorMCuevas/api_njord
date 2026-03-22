<?php

namespace App\Http\Controllers;

use App\Models\Ruta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
// use Symfony\Component\HttpFoundation\JsonResponse;

class RutaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     return Ruta::all();
    // }

    /**
     * Devuelve todas las rutas con sus relaciones.
     */
    public function index(): JsonResponse
    {
        $rutas = Ruta::with(['condicionAtmosferica', 'user'])->get();
        return response()->json($rutas);
    }

    /**
     * Crea una nueva ruta tras validar los datos.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_user'                  => 'required|exists:users,id',
            'path'                     => 'required|string|max:200|unique:rutas,path',
            'descripcion'              => 'required|string',
            'provincia_inicio'         => 'required|string|max:50',
            'provincia_fin'            => 'required|string|max:50',
            'temperatura'              => 'required|integer',
            'id_condicion_atmosferica' => 'required|exists:condiciones_atmosfericas,id',
            'puntuacion'               => 'required|integer|min:1|max:5',
        ]);

        $ruta = Ruta::create($validated);

        // Cargamos las relaciones para devolverlas en la respuesta
        $ruta->load(['condicionAtmosferica', 'user']);

        return response()->json($ruta, 201);
    }


    /**
     * Devuelve una ruta concreta con sus relaciones.
     */
    public function show(Ruta $ruta): JsonResponse
    {
        $ruta->load(['condicionAtmosferica', 'user']);
        return response()->json($ruta);
    }

    /**
     * Actualiza una ruta existente.
     * Usamos 'sometimes' para permitir actualizaciones parciales (PATCH).
     */
    public function update(Request $request, Ruta $ruta): JsonResponse
    {
        $validated = $request->validate([
            'id_user'                  => 'sometimes|exists:users,id',
            'path'                     => 'sometimes|string|max:200|unique:rutas,path,' . $ruta->id,
            'descripcion'              => 'sometimes|string',
            'provincia_inicio'         => 'sometimes|string|max:50',
            'provincia_fin'            => 'sometimes|string|max:50',
            'temperatura'              => 'sometimes|integer',
            'id_condicion_atmosferica' => 'sometimes|exists:condiciones_atmosfericas,id',
            'puntuacion'               => 'sometimes|integer|min:1|max:5',
        ]);

        $ruta->update($validated);
        $ruta->load(['condicionAtmosferica', 'user']);

        return response()->json($ruta);
    }

    /**
     * Elimina una ruta.
     */
    public function destroy(Ruta $ruta): JsonResponse
    {
        $ruta->delete();
        return response()->json(['message' => 'Ruta eliminada correctamente'], 200);
    }
}
