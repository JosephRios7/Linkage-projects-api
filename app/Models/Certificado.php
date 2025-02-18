<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificado extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'numero_certificado',
        'fecha_emision',
        'rol',
        'estado',
        'titulo',
        'file_data',
        'mime_type',
    ];

    /**
     * Relación con el proyecto.
     */
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'project_id');
    }

    /**
     * Relación con el usuario (estudiante o docente).
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    // En el modelo Certificado (por ejemplo, Certificado.php)
    public static function generarNumeroCertificado()
    {
        $currentYear = date('Y');
        // Buscar el último certificado emitido en el año actual
        $lastCertificado = self::whereYear('fecha_emision', $currentYear)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastCertificado) {
            // Suponiendo que el número se guarda con el formato "VRIV-CR-00001-2024"
            // Extraemos la parte numérica (posición 8 y longitud 5)
            $ultimoNumero = (int) substr($lastCertificado->numero_certificado, 8, 5);
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }

        // Formatear con 5 dígitos, completando con ceros a la izquierda
        $secuencia = str_pad($nuevoNumero, 5, '0', STR_PAD_LEFT);
        return "VRIV-CR-{$secuencia}-{$currentYear}";
    }

}
