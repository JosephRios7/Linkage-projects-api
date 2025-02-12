<?php

namespace App\Http\Controllers;

use App\Models\ProyectoObservacion;
use App\Models\ProyectoObservacionArchivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProyectoObservacionController extends Controller
{
    /**
     * Recibe y procesa la observación de un proyecto.
     */
    public function enviarObservacion(Request $request)
    {
        Log::info('Iniciando creación de observacion', ['request' => $request->all()]);

        // Validar campos obligatorios y múltiples archivos
        $validatedData = $request->validate([
            'comentario'  => 'required|string',
            'proyecto_id' => 'required|exists:proyectos,id',
            'archivo'     => 'nullable|array',
            'archivo.*'   => 'file|mimes:doc,docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document|max:10240'
        ]);

        try {
            // Crear la observación
            $observacion = ProyectoObservacion::create([
                'proyecto_id' => $validatedData['proyecto_id'],
                'estado'      => 'pendiente',
                'comentario'  => $validatedData['comentario']
            ]);

            // Procesar múltiples archivos (si existen)
            if ($request->has('archivo')) {
                foreach ($validatedData['archivo'] as $file) {
                    $base64File = base64_encode(file_get_contents($file->getRealPath()));
                    ProyectoObservacionArchivo::create([
                        'id_observacion' => $observacion->id,
                        'titulo'         => $file->getClientOriginalName(),
                        'file_data'      => $base64File,
                        'mime_type'      => $file->getMimeType()
                    ]);
                }
            }

            return response()->json([
                'message'     => 'Observación enviada correctamente.',
                'observacion' => $observacion
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al enviar la observación: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al enviar la observación.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function obtenerObservacionesProyecto($proyectoId)
    {
        try {
            // Se obtienen todas las observaciones de un proyecto, cargando también sus archivos
            $observaciones = ProyectoObservacion::where('proyecto_id', $proyectoId)
                ->with('archivos') // relación definida en el modelo ProyectoObservacion
                ->get();

            return response()->json([
                'observaciones' => $observaciones
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener observaciones del proyecto.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
