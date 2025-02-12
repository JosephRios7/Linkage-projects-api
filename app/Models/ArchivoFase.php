<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivoFase extends Model
{
    protected $fillable = ['fase_id', 'titulo', 'file_data', 'mime_type'];

    public function fase()
    {
        return $this->belongsTo(FaseConvocatoria::class);
    }
}
