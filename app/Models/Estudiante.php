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
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class);
    }
}
