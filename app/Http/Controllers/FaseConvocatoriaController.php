<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FaseConvocatoria;

class FaseConvocatoriaController extends Controller
{
    public function subirArchivosFase(Request $request, $fase_id)
    {
        $fase = FaseConvocatoria::findOrFail($fase_id);

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|mimes:doc,docx|max:10240', // Solo Word
        ]);

        foreach ($request->file('files') as $file) {
            $base64File = base64_encode(file_get_contents($file->getRealPath()));

            $fase->archivos()->create([
                'titulo' => $file->getClientOriginalName(),
                'file_data' => $base64File,
                'mime_type' => $file->getMimeType(),
            ]);
        }

        return response()->json(['message' => 'Archivos subidos correctamente'], 201);
    }
    public function listarFases($convocatoriaId)
    {
        // Consulta las fases donde convocatoria_id coincide con el id recibido
        $fases = FaseConvocatoria::where('convocatoria_id', $convocatoriaId)->get();
        return response()->json($fases, 200);
    }

    public function obtenerFasePorNombre(Request $request, $convocatoriaId)
    {
        $nombre = $request->query('nombre');
        if (!$nombre) {
            return response()->json(['message' => 'El parámetro nombre es requerido'], 400);
        }
        try {
            $fase = FaseConvocatoria::where('convocatoria_id', $convocatoriaId)
                ->where('nombre', $nombre)
                ->first();
            if (!$fase) {
                return response()->json(['message' => 'Fase no encontrada'], 404);
            }
            return response()->json($fase, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la fase',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


}
