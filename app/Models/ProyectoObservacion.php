<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProyectoObservacion extends Model
{
    protected $table = 'proyecto_observaciones';

    protected $fillable = [
        'proyecto_id',
        // 'fase_id',
        'estado',
        'comentario'
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    // public function fase()
    // {
    //     return $this->belongsTo(FaseConvocatoria::class, 'fase_id');
    // }

    public function archivos()
    {
        return $this->hasMany(ProyectoObservacionArchivo::class, 'id_observacion');
    }
}
