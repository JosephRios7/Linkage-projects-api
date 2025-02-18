<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model
{
    use HasFactory;

    protected $fillable = [
        'convocatoria_id',
        'nombre',
        'dominio',
        'fase',
        'fasePresentacion',  // para 'fase' de la presentacion
        'institucion_beneficiaria',
        'canton',
        'parroquia',
        'oferta_academica',
        'facultad',
        'carrera',
        'modalidad',
        'estado',
        'codigo_proyecto',
        'numero_resolucion',
        'estado_fase',
        'resumen'
    ];

    public function convocatoria()
    {
        return $this->belongsTo(Convocatoria::class);
    }

    // public function estudiantes()
    // {
    //     return $this->hasMany(Estudiante::class,  'proyecto_id');
    // }

    // public function archivos()
    // {
    //     return $this->hasMany(Archivo::class, 'entidad_id')->where('tipo', 'proyecto');
    // }

    /**
     * Relación con el docente coordinador (hasOne).
     */
    // public function docenteCoordinador()
    // {
    //     return $this->hasOne(Docente::class, 'proyecto_id','id');
    // }

    public function miembros()
    {
        // Asumiendo que en la tabla project_members se almacena el rol (profesor/estudiante) 
        // y se relaciona con el usuario a través de user_id
        return $this->hasMany(ProjectMember::class, 'project_id');
    }
    public function estudiantes()
    {
        return $this->hasMany(ProjectMember::class, 'project_id')
        ->where('role', 'estudiante')
        ->with('user'); // Esto asume que en ProjectMember hay una relación con User
    }

    public function archivos()
    {
        // Relación con los archivos que se suben para la fase de presentación de propuestas
        return $this->hasMany(ProyectoArchivoFase::class, 'proyecto_id');
    }
    public function certificados()
    {
        return $this->hasMany(Certificado::class, 'project_id');
    }

}
