<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Convocatoria;

class ConvocatoriaPublicaController extends Controller
{
    /**
     * Listar solo convocatorias publicadas
     */
    public function listarPublicadas()
    {
        try {
            $convocatorias = Convocatoria::where('estado', 'publicado')
                ->with('archivos') // Relación con archivos
                ->get();

            // Agregar URLs completas para los archivos
            foreach ($convocatorias as $convocatoria) {
                foreach ($convocatoria->archivos as $archivo) {
                    $archivo->url = asset('storage/' . $archivo->file_path);
                }
            }

            return response()->json($convocatorias, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar convocatorias publicadas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function obtenerConvocatoriaPorId($id)
    {
        try {
            $convocatoria = Convocatoria::with('archivos')
                ->where('id', $id)
                ->where('estado', 'publicado')
                ->firstOrFail(); // Devuelve solo un registro o lanza un error
    
            // Generar URLs completas para los archivos relacionados
            foreach ($convocatoria->archivos as $archivo) {
                $archivo->url = asset('storage/' . $archivo->file_path);
            }
    
            return response()->json($convocatoria, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Convocatoria no encontrada o no publicada.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
    
}
