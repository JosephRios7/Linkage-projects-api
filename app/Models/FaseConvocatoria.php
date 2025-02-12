<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FaseConvocatoria extends Model
{
    protected $table = 'fase_convocatorias'; // Asegurar que coincida con la tabla en la BD
    protected $fillable = ['convocatoria_id', 'nombre', 'resumen', 'estado', 'fecha_inicio', 'fecha_fin'];

    public function archivos()
    {
        return $this->hasMany(ArchivoFase::class, 'fase_id');
    }

    public function convocatoria()
    {
        return $this->belongsTo(Convocatoria::class);
    }
}
