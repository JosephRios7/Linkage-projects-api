<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    //Verifica las credenciales del usuario y genera un token de acceso
    public function login(Request $request)
    {
        // Validar las credenciales recibidas
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        // Buscar el usuario por email
        $user = User::where('email', $request->email)->first();

        // Verificar que el usuario exista y que la contraseña sea correcta
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }
        // Verificar si el usuario tiene un docente asociado
        $docente = $user->docente ? $user->docente : null;
        // Verificar si el usuario tiene un estudiante asociado
        $estudiante = $user->estudiante ? $user->estudiante : null;
        // Generar un token de acceso con Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;
        if ($docente) {
            return response()->json([
                'message' => 'Inicio de sesión exitoso',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role, // Incluye el rol del usuario
                    'docente_id' => $user->docente->id, // Asegurar relación con Docente
                    'docente' => $user->docente, // Aquí se incluye la información de Docente
                ],
            ]);
        } elseif($estudiante){
            return response()->json([
                'message' => 'Inicio de sesión exitoso',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role, // Incluye el rol del usuario
                    'estudiante_id' => $user->estudiante->id, // Asegurar relación con Docente
                    'estudiante' => $user->estudiante, // Aquí se incluye la información de Docente
                ],
            ]);
        }
        
        
        else {
            return response()->json([
                'message' => 'Inicio de sesión exitoso',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role, // Incluye el rol del usuario
                ],
            ]);
        }
    }
    //cerrar sesion
    public function logout(Request $request)
    {
        // Elimina el token activo del usuario
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Cierre de sesión exitoso',
        ]);
    }
}
