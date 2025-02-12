<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProyectoArchivoFase extends Model
{
    use HasFactory;

    // Especificamos la tabla si el nombre no sigue la convención plural
    protected $table = 'proyecto_archivo_fase';

    // Definimos los campos que se pueden asignar de forma masiva
    protected $fillable = [
        'proyecto_id',
        'fase_id',
        'titulo',
        'file_data',
        'mime_type'
    ];

    /**
     * Relación: Un archivo pertenece a un proyecto.
     */
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    /**
     * Relación: Un archivo pertenece a una fase.
     */
    public function fase()
    {
        return $this->belongsTo(FaseConvocatoria::class, 'fase_id');
    }
}
