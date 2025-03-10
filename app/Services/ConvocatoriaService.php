<?php

namespace App\Services;

use App\Models\Convocatoria;
use Illuminate\Support\Facades\Log;

class ConvocatoriaService
{
    public function actualizarEstados()
    {
        try {
            // Cambiar convocatorias "publicado" a "finalizado" si ya pasó la fecha_fin
            Convocatoria::where('estado', 'Publicado')
                ->where('fecha_fin', '<', now())
                ->update(['estado' => 'Finalizado']);
        } catch (\Exception $e) {
            Log::error('Error al actualizar estados de convocatorias: ' . $e->getMessage());
        }
    }
}
