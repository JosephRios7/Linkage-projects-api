<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    use HasFactory;

    protected $fillable = [
        // 'proyecto_id',
        'nombre',
        'apellido',
        'cedula',
        'correo',
        'telefono',
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class);
    }
}
