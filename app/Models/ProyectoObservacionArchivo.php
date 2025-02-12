<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProyectoObservacionArchivo extends Model
{
    protected $table = 'proyecto_observaciones_archivos';

    protected $fillable = [
        'id_observacion',
        'titulo',
        'file_data',
        'mime_type'
    ];

    public function observacion()
    {
        return $this->belongsTo(ProyectoObservacion::class, 'id_observacion');
    }
}
