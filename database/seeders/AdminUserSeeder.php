<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        User::create([
            'name' => 'Administrador',
            'email' => 'user@gmail.com',
            'password' => Hash::make('password'), // Usa una contraseña segura
            'role' => 'admin', // Asigna el rol de administrador
        ]);
    } 
}
