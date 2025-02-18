<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserController extends Controller
{
    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,profesor,estudiante,revisor,editor', // Opciones válidas para roles
        ]);

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'Usuario creado con éxito.',
            'user' => $user,
        ], 201);
    }




    public function listUsers(Request $request)
    {
        $users = \App\Models\User::all();

        return response()->json([
            'users' => $users,
        ]);
    }


    public function updateUser(Request $request, $id)
{
    $user = \App\Models\User::findOrFail($id);

    $validated = $request->validate([
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|string|email|unique:users,email,' . $user->id,
        'password' => 'sometimes|string|min:6',
        'role' => 'sometimes|in:admin,profesor,estudiante', // Opciones válidas para roles
    ]);

    $user->update([
        'name' => $validated['name'] ?? $user->name,
        'email' => $validated['email'] ?? $user->email,
        'password' => isset($validated['password']) ? Hash::make($validated['password']) : $user->password,
        'role' => $validated['role'] ?? $user->role,
    ]);

    return response()->json([
        'message' => 'Usuario actualizado con éxito.',
        'user' => $user,
    ]);
}



    // public function deleteUser($id)
    // {
    //     $user = \App\Models\User::findOrFail($id);

    //     $user->delete();

    //     return response()->json([
    //         'message' => 'Usuario eliminado con éxito.',
    //     ]);
    // }
    public function deleteUser($id)
    {
        // Buscamos el usuario por su ID
        $user = \App\Models\User::findOrFail($id);

        // Obtenemos el correo del usuario
        $email = $user->email;

        // Buscamos y eliminamos el docente que tenga el mismo correo, si existe
        $docente = \App\Models\Docente::where('correo', $email)->first();
        if ($docente) {
            $docente->delete();
        }

        // Buscamos y eliminamos el estudiante que tenga el mismo correo, si existe
        $estudiante = \App\Models\Estudiante::where('correo', $email)->first();
        if ($estudiante) {
            $estudiante->delete();
        }

        // Eliminamos el usuario
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado con éxito.',
        ]);
    }


}
