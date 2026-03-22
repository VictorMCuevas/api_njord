<?php

namespace App\Http\Controllers;

use App\Models\CondicionAtmosferica;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CondicionAtmosfericaController extends Controller
{
    /**
     * 
     * Devuelve todas las condiciones con sus rutas asociadas.
     */
    public function index(): JsonResponse
    {
        $condiciones = CondicionAtmosferica::with('rutas')->get();
        return response()->json($condiciones);
    }

    /**
     * Crea una nueva condición atmosférica.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50|unique:condiciones_atmosfericas,nombre',
        ]);

        $condicion = CondicionAtmosferica::create($validated);
        $condicion->load('rutas');

        return response()->json($condicion, 201);
    }

    /**
     * Devuelve una condición concreta con sus rutas.
     */
    public function show(CondicionAtmosferica $condicionAtmosferica): JsonResponse
    {
        $condicionAtmosferica->load('rutas');
        return response()->json($condicionAtmosferica);
    }

    /**
     * Actualiza una condición atmosférica.
     */
    public function update(Request $request, CondicionAtmosferica $condicionAtmosferica): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:50|unique:condiciones_atmosfericas,nombre,' . $condicionAtmosferica->id,
        ]);

        $condicionAtmosferica->update($validated);
        $condicionAtmosferica->load('rutas');

        return response()->json($condicionAtmosferica);
    }

    /**
     * Elimina una condición atmosférica.
     * Si tiene rutas asociadas, devuelve error 409.
     */
    public function destroy(CondicionAtmosferica $condicionAtmosferica): JsonResponse
    {
        if ($condicionAtmosferica->rutas()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar: esta condición tiene rutas asociadas'
            ], 409);
        }

        $condicionAtmosferica->delete();
        return response()->json(['message' => 'Condición atmosférica eliminada correctamente'], 200);
    }
}