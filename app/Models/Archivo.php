<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Archivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo',        // Indica el tipo de archivo (convocatoria, proyecto, etc.)
        'entidad_id',  // El ID de la entidad asociada
        'titulo',      // Nombre del archivo original
        'file_data',
        'mime_type'  
    ];

    /**
     * Relación polimórfica para enlazar con diferentes entidades.
     */
    public function entidad()
    {
        return $this->morphTo();
    }
}
