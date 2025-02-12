<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Convocatoria extends Model
{
    protected $fillable = [
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];

    /**
     * Relación con archivos
     * Una convocatoria tiene muchos archivos
     */
    public function archivos()
{
    return $this->hasMany(Archivo::class, 'entidad_id')
                ->where('tipo', 'convocatoria');
}



    public function proyectos()
    {
        return $this->hasMany(Proyecto::class);
    }
    public function fases()
    {
        return $this->hasMany(FaseConvocatoria::class);
    }
}
