<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectMember extends Model
{
    use HasFactory;

    // Especifica el nombre de la tabla (opcional si sigue la convención plural en inglés)
    protected $table = 'project_members';

    // Los atributos que se pueden asignar masivamente.
    protected $fillable = [
        'project_id',
        'user_id',
        'role',
    ];

    /**
     * Relación con el proyecto.
     * Se asume que el modelo de proyecto es "Proyecto" y que la clave foránea es "project_id".
     */
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'project_id');
    }

    /**
     * Relación con el usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
