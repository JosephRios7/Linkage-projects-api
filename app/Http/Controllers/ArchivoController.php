<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Archivo;
use App\Models\ArchivoFase;

class ArchivoController extends Controller
{
    /**
     * Listar archivos asociados a una convocatoria (por entidad_id).
     */
    public function listarArchivosConvocatoria($convocatoriaId)
    {
        $archivos = Archivo::where('entidad_id', $convocatoriaId)
            ->where('tipo', 'convocatoria')
            ->get();
        return response()->json($archivos, 200);
    }

    /**
     * Listar archivos asociados a una fase (por fase_id).
     */
    public function listarArchivosFase($faseId)
    {
        $archivos = ArchivoFase::where('fase_id', $faseId)->get();
        return response()->json($archivos, 200);
    }

    /**
     * Descargar archivo de convocatoria o de fase (dependiendo de la ruta).
     */
    public function downloadConvocatoria($archivoId)
    {
        // Puedes omitir o ajustar el control de roles según convenga
        // if (auth()->user()->role !== 'admin') {
        //     abort(403, 'No tienes permiso para descargar este archivo.');
        // }

        $archivo = Archivo::find($archivoId);
        if (!$archivo) {
            return response()->json(['message' => 'Archivo no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $decodedFile = base64_decode($archivo->file_data);

        return response($decodedFile, 200)
            ->header('Content-Type', $archivo->mime_type ?? 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $archivo->titulo . '"');
    }

    public function downloadFase($archivoId)
    {
        $archivo = ArchivoFase::find($archivoId);
        // if (!$archivo) {
        //     return response()->json(['message' => 'Archivo no encontrado'], Response::HTTP_NOT_FOUND);
        // }

        $decodedFile = base64_decode($archivo->file_data);

        return response($decodedFile, 200)
            ->header('Content-Type', $archivo->mime_type ?? 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $archivo->titulo . '"');
    }




    /**
     * Eliminar archivo de convocatoria.
     */
    public function destroyConvocatoria($archivoId)
    {
        $archivo = Archivo::findOrFail($archivoId);
        $archivo->delete();
        return response()->json(['message' => 'Archivo de convocatoria eliminado'], 200);
    }

    /**
     * Eliminar archivo de fase.
     */
    public function destroyFase($archivoId)
    {
        $archivo = ArchivoFase::findOrFail($archivoId);
        $archivo->delete();
        return response()->json(['message' => 'Archivo de fase eliminado'], 200);
    }

    /**
     * Subir (o reemplazar) un archivo nuevo para una convocatoria.
     * Se debe enviar en el request:
     * - file: el archivo (obligatorio)
     * - entidad_id: el id de la convocatoria
     */
    public function storeConvocatoria(Request $request)
    {
        $request->validate([
            'file'       => 'required|file',
            'entidad_id' => 'required|integer'
        ]);

        $file = $request->file('file');
        $base64File = base64_encode(file_get_contents($file->getRealPath()));

        $archivo = Archivo::create([
            'tipo'       => 'convocatoria',
            'entidad_id' => $request->entidad_id,
            'titulo'     => $file->getClientOriginalName(),
            'file_data'  => $base64File,
            'mime_type'  => $file->getMimeType()
        ]);

        return response()->json([
            'message' => 'Archivo de convocatoria subido exitosamente',
            'archivo' => $archivo
        ], 201);
    }

    /**
     * Subir (o reemplazar) un archivo nuevo para una fase.
     * Se debe enviar en el request:
     * - file: el archivo
     * - fase_id: el id de la fase
     */
    public function storeFase(Request $request)
    {
        $request->validate([
            'file'    => 'required|file',
            'fase_id' => 'required|integer'
        ]);

        $file = $request->file('file');
        $base64File = base64_encode(file_get_contents($file->getRealPath()));

        $archivo = ArchivoFase::create([
            'fase_id'   => $request->fase_id,
            'titulo'    => $file->getClientOriginalName(),
            'file_data' => $base64File,
            'mime_type' => $file->getMimeType()
        ]);

        return response()->json([
            'message' => 'Archivo de fase subido exitosamente',
            'archivo' => $archivo
        ], 201);
    }
}
