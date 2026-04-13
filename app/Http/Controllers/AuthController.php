<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registrar un nuevo usuario
     * POST /api/auth/registrar
     */
    // public function register(Request $request)
    // {
    //     try {
    //         $validado = $request->validate([
    //             'name' => 'required|string|max:255',
    //             'email' => 'required|string|email|max:255|unique:users',
    //             'password' => 'required|string|min:6|confirmed',
    //         ]);

    //         $usuario = User::create([
    //             'name' => $validado['name'],
    //             'email' => $validado['email'],
    //             'password' => Hash::make($validado['password']),
    //         ]);

    //         // ✅ CREAR CARPETA GPX AUTOMÁTICAMENTE
    //             $carpeta_gpx = 'gpx/usuario_' . $usuario->id;
    //         if (!Storage::exists($carpeta_gpx)) {
    //             Storage::makeDirectory($carpeta_gpx);
    //             Log::info('Carpeta GPX creada:', ['carpeta' => $carpeta_gpx]);
    //         }

    //         $token = $usuario->createToken('auth_token')->plainTextToken;

    //         return response()->json([
    //             'estado' => 'exito',
    //             'mensaje' => 'Usuario registrado exitosamente',
    //             'datos' => [
    //                 'usuario' => $usuario,
    //                 'token' => $token,
    //                 'tipo_token' => 'Bearer',
    //             ],
    //         ], 201);

    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'estado' => 'error',
    //             'mensaje' => 'Validación fallida',
    //             'errores' => $e->errors(),
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'estado' => 'error',
    //             'mensaje' => 'Registro fallido',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function register(Request $request)
{
    try {
        $validado = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $usuario = User::create([
            'name' => $validado['name'],
            'email' => $validado['email'],
            'password' => Hash::make($validado['password']),
        ]);

        // ✅ CREAR CARPETA GPX AUTOMÁTICAMENTE
        try {
            $carpeta_gpx = 'gpx/usuario_' . $usuario->id;
            Log::info('Intentando crear carpeta:', ['carpeta' => $carpeta_gpx]);
            
            if (!Storage::exists($carpeta_gpx)) {
                Storage::makeDirectory($carpeta_gpx);
                Log::info('✅ Carpeta GPX creada:', ['carpeta' => $carpeta_gpx]);
            } else {
                Log::info('✅ Carpeta GPX ya existe:', ['carpeta' => $carpeta_gpx]);
            }
        } catch (\Exception $carpetaError) {
            Log::error('❌ Error al crear carpeta GPX:', ['error' => $carpetaError->getMessage()]);
        }

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'estado' => 'exito',
            'mensaje' => 'Usuario registrado exitosamente',
            'datos' => [
                'usuario' => $usuario,
                'token' => $token,
                'tipo_token' => 'Bearer',
            ],
        ], 201);

    } catch (ValidationException $e) {
        return response()->json([
            'estado' => 'error',
            'mensaje' => 'Validación fallida',
            'errores' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error en register:', ['error' => $e->getMessage()]);
        return response()->json([
            'estado' => 'error',
            'mensaje' => 'Registro fallido',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Iniciar sesión
     * POST /api/auth/iniciar-sesion
     */
    public function login(Request $request)
    {
        try {
            $validado = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            $usuario = User::where('email', $validado['email'])->first();

            if (!$usuario || !Hash::check($validado['password'], $usuario->password)) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'Email o contraseña inválidos',
                ], 401);
            }

            $token = $usuario->createToken('auth_token')->plainTextToken;

            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Inicio de sesión exitoso',
                'datos' => [
                    'usuario' => $usuario,
                    'token' => $token,
                    'tipo_token' => 'Bearer',
                ],
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'Validación fallida',
                'errores' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'Inicio de sesión fallido',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cerrar sesión
     * POST /api/auth/cerrar-sesion
     */
    public function cerrarSesion(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Sesión cerrada exitosamente',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'Error al cerrar sesión',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener perfil
     * GET /api/auth/perfil
     */
    public function profile(Request $request)
    {
        try {
            return response()->json([
                'estado' => 'exito',
                'datos' => $request->user(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'No se pudo obtener el perfil',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar perfil
     * PUT /api/auth/perfil
     */
    // public function actualizarPerfil(Request $request)
    // {
    //     try {
    //         $validado = $request->validate([
    //             'name' => 'sometimes|string|max:255',
    //             'email' => 'sometimes|string|email|max:255|unique:users,email,' . $request->user()->id,
    //         ]);

    //         $request->user()->update($validado);

    //         return response()->json([
    //             'estado' => 'exito',
    //             'mensaje' => 'Perfil actualizado exitosamente',
    //             'datos' => $request->user()->fresh(),
    //         ], 200);

    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'estado' => 'error',
    //             'mensaje' => 'Validación fallida',
    //             'errores' => $e->errors(),
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'estado' => 'error',
    //             'mensaje' => 'Error al actualizar perfil',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function actualizarPerfil(Request $request)
{
    try {
        // 1. Validar
        $validado = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $request->user()->id,
        ]);

        // 2. Debug: qué datos llegaron
        Log::info('=== ACTUALIZANDO PERFIL ===');
        Log::info('Usuario ID: ' . $request->user()->id);
        Log::info('Datos validados:', $validado);
        Log::info('Nombre actual en BD antes:', ['name' => $request->user()->name]);

        // 3. Actualizar en BD
        $usuarioActual = $request->user();
        $resultado = $usuarioActual->update($validado);
        
        Log::info('¿Se actualizó?', ['resultado' => $resultado]);

        // 4. Recargar de BD
        $usuarioActualizado = User::find($request->user()->id);
        
        Log::info('Nombre en BD después:', ['name' => $usuarioActualizado->name]);
        Log::info('=== FIN DEBUG ===');

        return response()->json([
            'estado' => 'exito',
            'mensaje' => 'Perfil actualizado exitosamente',
            'datos' => $usuarioActualizado,
        ], 200);

    } catch (ValidationException $e) {
        return response()->json([
            'estado' => 'error',
            'mensaje' => 'Validación fallida',
            'errores' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error al actualizar:', ['error' => $e->getMessage()]);
        return response()->json([
            'estado' => 'error',
            'mensaje' => 'Error al actualizar perfil',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}