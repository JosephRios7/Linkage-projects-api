<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Convocatoria;
use App\Models\Archivo; // Importar el modelo Archivo
use Illuminate\Support\Facades\Storage;
use App\Services\ConvocatoriaService;
use Illuminate\Support\Facades\DB;
use App\Models\FaseConvocatoria;
use ArchivoFase;
use Illuminate\Support\Facades\Log;

class ConvocatoriaController extends Controller
{
    private $convocatoriaService;

    public function __construct(ConvocatoriaService $convocatoriaService)
    {
        $this->convocatoriaService = $convocatoriaService;
    }


    public function crearConvocatoria(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'No tienes permiso para crear convocatorias.');
        }

        try {
            Log::info('Iniciando creación de convocatoria', ['request' => $request->all()]);

            DB::beginTransaction();

            // Crear la convocatoria manualmente
            $convocatoriaId = DB::table('convocatorias')->insertGetId([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'estado' => 'borrador',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Convocatoria creada', ['id' => $convocatoriaId]);

            // Manejar archivos de la convocatoria
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    Log::info('Guardando archivo de convocatoria', ['nombre' => $file->getClientOriginalName()]);
                    $base64File = base64_encode(file_get_contents($file->getRealPath()));

                    DB::table('archivos')->insert([
                        'tipo' => 'convocatoria',
                        'entidad_id' => $convocatoriaId,
                        'titulo' => $file->getClientOriginalName(),
                        'file_data' => $base64File,
                        'mime_type' => $file->getMimeType(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // ✅ Decodificar `fases` si es un string JSON
            $fases = is_string($request->fases) ? json_decode($request->fases, true) : $request->fases;

            if (!is_array($fases)) {
                throw new \Exception('Las fases no están en el formato correcto.');
            }

            
            foreach ($fases as $index => $faseData) {
                Log::info('Creando fase', ['fase' => $faseData]);

                if (!isset($faseData['nombre'], $faseData['fecha_inicio'], $faseData['fecha_fin'])) {
                    throw new \Exception("Datos de fase inválidos en la fase {$index}.");
                }

                // Convertir `estado` a booleano correcto
                $estado = filter_var($faseData['estado'], FILTER_VALIDATE_BOOLEAN);

                // Insertar la fase en la base de datos
                $faseId = DB::table('fase_convocatorias')->insertGetId([
                    'convocatoria_id' => $convocatoriaId,
                    'nombre' => $faseData['nombre'],
                    'estado' => $estado,
                    'resumen' => $faseData['resumen'] ?? '',
                    'fecha_inicio' => $faseData['fecha_inicio'],
                    'fecha_fin' => $faseData['fecha_fin'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('Fase creada', ['id' => $faseId, 'estado' => $estado]);

                // ✅ Validar que los archivos lleguen correctamente
                if ($request->hasFile("fases.{$index}.archivos")) {
                    $archivosFase = $request->file("fases.{$index}.archivos");

                    foreach ($archivosFase as $archivo) {
                        if ($archivo->isValid()) {
                            Log::info('Guardando archivo de fase', ['nombre' => $archivo->getClientOriginalName()]);
                            $base64File = base64_encode(file_get_contents($archivo->getRealPath()));

                            DB::table('archivo_fases')->insert([
                                'fase_id' => $faseId,
                                'titulo' => $archivo->getClientOriginalName(),
                                'file_data' => $base64File,
                                'mime_type' => $archivo->getMimeType(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } else {
                            Log::warning('Archivo inválido, no se guardará.', ['nombre' => $archivo->getClientOriginalName()]);
                        }
                    }
                } else {
                    Log::warning('No se encontraron archivos para la fase.', ['fase_id' => $faseId]);
                }
            }



            DB::commit();
            Log::info('Convocatoria y fases creadas exitosamente.');

            return response()->json([
                'message' => 'Convocatoria creada exitosamente',
                'convocatoria_id' => $convocatoriaId,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al crear convocatoria', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error al crear la convocatoria',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Listar convocatorias (admin y revisor)
     */
    public function verConvocatoria(Request $request)
    {
        $this->convocatoriaService->actualizarEstados();

        try {
            $query = Convocatoria::query();

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('search')) {
                $query->where('titulo', 'like', '%' . $request->search . '%');
            }

            $convocatorias = $query->with('archivos')->get();

            return response()->json($convocatorias, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar las convocatorias',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publicar convocatoria (solo admin)
     */
    public function publicar($id)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'No tienes permiso para publicar convocatorias.');
        }

        try {
            $convocatoria = Convocatoria::findOrFail($id);
            $convocatoria->update(['estado' => 'publicado']);

            return response()->json([
                'message' => 'Convocatoria publicada exitosamente',
                'convocatoria' => $convocatoria,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al publicar la convocatoria',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar convocatoria (solo admin)
     */
    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'No tienes permiso para eliminar convocatorias.');
        }

        try {
            $convocatoria = Convocatoria::findOrFail($id);

            $archivos = Archivo::where('tipo', 'convocatoria')
                ->where('entidad_id', $convocatoria->id)
                ->get();

            foreach ($archivos as $archivo) {
                if (Storage::exists('/public' . $archivo->file_path)) {
                    Storage::delete('/public' . $archivo->file_path);
                }
                $archivo->delete();
            }

            $convocatoria->delete();

            return response()->json([
                'message' => 'Convocatoria eliminada exitosamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la convocatoria',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {


        if (auth()->user()->role !== 'admin') {
            abort(403, 'No tienes permiso para eliminar convocatorias.');
        }

        try {
            $convocatoria = Convocatoria::with('fases')->findOrFail($id);
            return response()->json($convocatoria, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la convocatoria',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function editarConvocatoria(Request $request, $id)
    {
        try {
            // Buscar la convocatoria a actualizar
            $convocatoria = Convocatoria::findOrFail($id);

            // Actualizar datos principales de la convocatoria
            $data = $request->only(['titulo', 'descripcion', 'fecha_inicio', 'fecha_fin']);
            $convocatoria->update($data);

            if ($request->has('fases')) {
                // Obtener los IDs de las fases existentes en la base de datos
                $existingPhaseIds = $convocatoria->fases()->pluck('id')->toArray();
                $requestPhaseIds = [];

                foreach ($request->fases as $faseData) {
                    if (isset($faseData['id'])) {
                        $requestPhaseIds[] = $faseData['id'];
                        // Actualizar la fase existente
                        $fase = FaseConvocatoria::find($faseData['id']);
                        if ($fase) {
                            $fase->update($faseData);
                        }
                    } else {
                        // Crear una nueva fase asociada a la convocatoria
                        FaseConvocatoria::create(array_merge($faseData, ['convocatoria_id' => $convocatoria->id]));
                    }
                }

                // Opcional: eliminar las fases que existen en la base de datos pero no se enviaron en el request
                $phasesToDelete = array_diff($existingPhaseIds, $requestPhaseIds);
                if (!empty($phasesToDelete)) {
                    FaseConvocatoria::destroy($phasesToDelete);
                }
            }

            return response()->json([
                'message' => 'Convocatoria actualizada exitosamente',
                'convocatoria' => $convocatoria->load('fases')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la convocatoria',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function finalizar($id)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'No tienes permiso para finalizar convocatorias.');
        }

        try {
            $convocatoria = Convocatoria::findOrFail($id);
            $convocatoria->update(['estado' => 'Finalizado']);

            return response()->json([
                'message' => 'Convocatoria finalizada exitosamente',
                'convocatoria' => $convocatoria,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al finalizar la convocatoria',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function listarConvocatoriasPublicadasFinalizadas()
    {
        $convocatorias = \App\Models\Convocatoria::whereIn('estado', ['publicado', 'finalizado'])->get();
        return response()->json($convocatorias);
    }
    // public function listarProyectosConvocatoria($convocatoriaId)
    // {
    //     $convocatoria = \App\Models\Convocatoria::with('proyectos')
    //         ->where('estado', 'finalizado')
    //         ->findOrFail($convocatoriaId);
    //     return response()->json($convocatoria->proyectos);
    // }
}
