<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Devuelve todos los usuarios con sus rutas asociadas.
     */
    public function index(): JsonResponse
    {
        $usuarios = User::with('rutas')->get();
        return response()->json($usuarios);
    }

    /**
     * Crea un nuevo usuario.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $usuario = User::create($validated);
        $usuario->load('rutas');

        return response()->json($usuario, 201);
    }

    /**
     * Devuelve un usuario concreto con sus rutas.
     */
    public function show(User $usuario): JsonResponse
    {
        $usuario->load('rutas');
        return response()->json($usuario);
    }

    /**
     * Actualiza un usuario existente.
     */
    public function update(Request $request, User $usuario): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $usuario->id,
            'password' => 'sometimes|string|min:8',
        ]);

        $usuario->update($validated);
        $usuario->load('rutas');

        return response()->json($usuario);
    }

    /**
     * Elimina un usuario y sus rutas en cascada.
     */
    public function destroy(User $usuario): JsonResponse
    {
        $usuario->delete();
        return response()->json(['message' => 'Usuario eliminado correctamente'], 200);
    }
}