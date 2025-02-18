<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    use HasFactory;

    protected $fillable = [
        // 'proyecto_id',
        'nombre',
        'apellido',
        'cedula',
        'genero',
        'correo',
        'nota_docente',
        'nota_admin',
        'estado',
        'nota_final',
        'user_id',
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
