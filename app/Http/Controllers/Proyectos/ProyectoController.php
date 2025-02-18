<?php

namespace App\Http\Controllers\Proyectos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Convocatoria;
use App\Models\Proyecto;
use App\Models\Archivo;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\FaseConvocatoria;
use App\Models\ProjectMember;
use App\Models\ProyectoArchivoFase;
use App\Models\ProyectoObservacion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProyectoController extends Controller
{

    public function crearProyecto(Request $request)
    {
        DB::beginTransaction();
        try {
            Log::info('Iniciando creación de proyecto', ['request' => $request->all()]);

            // Validación del request
            $request->validate([
                'convocatoria_id' => 'required|exists:convocatorias,id',
                'nombre' => 'required|string|max:255',
                'dominio' => 'required|string|max:255',
                'fase' => 'required|string|in:Fase1,Fase2,Fase3',
                'fasePresentacion' => 'required|string|',
                // Validación del docente coordinador
                'docente_coordinador.nombre' => 'required|string|max:255',
                'docente_coordinador.apellido' => 'required|string|max:255',
                'docente_coordinador.cedula' => 'required|string|max:20',
                'docente_coordinador.correo' => 'required|email|max:255',
                'docente_coordinador.telefono' => 'required|string|max:20',
                // Validación de otros datos del proyecto
                'institucion_beneficiaria' => 'required|string|max:255',
                'canton' => 'required|string|max:255',
                'parroquia' => 'required|string|max:255',
                'oferta_academica' => 'required|string|in:Pregrado,Posgrado',
                'facultad' => 'required|string|max:255',
                'carrera' => 'required|string|max:255',
                'modalidad' => 'required|string|in:Presencial,Híbrida,Online',
                // Validación de los estudiantes
                'estudiantes' => 'required|array',
                'estudiantes.*.nombre' => 'required|string|max:255',
                'estudiantes.*.apellido' => 'required|string|max:255',
                'estudiantes.*.cedula' => 'required|string|max:20',
                'estudiantes.*.genero' => 'required|string|in:Masculino,Femenino',
                'estudiantes.*.correo' => 'required|email|max:255',
                // Validación de los archivos (opcional)
                'archivos' => 'nullable|array',
                'archivos.*' => 'file|mimes:pdf,doc,docx|max:10240',
            ]);

            // 1. Buscar la convocatoria asociada
            $convocatoria = Convocatoria::findOrFail($request->convocatoria_id);

            // 2. Crear el proyecto asociado a la convocatoria
            $project = Proyecto::create([
                'convocatoria_id'          => $convocatoria->id,
                'nombre'                   => $request->nombre,
                'dominio'                  => $request->dominio,
                'fase'                     => $request->fase,
                'fasePresentacion' => $request->fasePresentacion,
                'institucion_beneficiaria' => $request->institucion_beneficiaria,
                'canton'                   => $request->canton,
                'parroquia'                => $request->parroquia,
                'oferta_academica'         => $request->oferta_academica,
                'facultad'                 => $request->facultad,
                'carrera'                  => $request->carrera,
                'modalidad'                => $request->modalidad,
                'estado'                   => 'enviado',  // Estado inicial
                'estado_fase'              => 'subida',   // Fase inicial
            ]);

            // 3. Procesar el docente coordinador
            $docenteData = $request->docente_coordinador;
            // Primero, buscar el usuario por correo
            $userDocente = User::where('email', $docenteData['correo'])->first();

            if ($userDocente) {
                // Actualiza nombre y rol
                $userDocente->update([
                    'name' => $docenteData['nombre'] . ' ' . $docenteData['apellido'],
                    'role' => 'profesor'
                ]);
            } else {
                // Si no existe, crea el usuario
                $userDocente = User::create([
                    'name'     => $docenteData['nombre'] . ' ' . $docenteData['apellido'],
                    'email'    => $docenteData['correo'],
                    'password' => Hash::make($docenteData['cedula']),
                    'role'     => 'profesor'
                ]);
            }
            $docenteUserId = $userDocente->id;

            // Actualizar o crear registro en la tabla docentes
            Docente::updateOrCreate(
                ['cedula' => $docenteData['cedula']],
                [
                    'nombre'   => $docenteData['nombre'],
                    'apellido' => $docenteData['apellido'],
                    'correo'   => $docenteData['correo'],
                    'telefono' => $docenteData['telefono'],
                    'user_id' => $docenteUserId,
                ]
            );

            // Insertar en project_members
            ProjectMember::firstOrCreate(
                ['project_id' => $project->id, 'user_id' => $docenteUserId],
                ['role' => 'profesor']
            );

            // 4. Procesar los estudiantes
            foreach ($request->estudiantes as $estudianteData) {

                // Verificar si el estudiante ya existe por cédula
                $existingEstudiante = Estudiante::where('cedula', $estudianteData['cedula'])->first();

                // Si existe y su estado NO es "reprobado", se asume que ya está inscrito en otro proyecto activo o aprobado
                if ($existingEstudiante && $existingEstudiante->estado !== 'reprobado') {
                    throw new \Exception("El estudiante con cédula {$estudianteData['cedula']} ya se encuentra inscrito en otro proyecto activo o aprobado.");
                }

                // Si existe pero su estado es "reprobado", actualizamos su estado a "activo" (o el estado que corresponda)
                if ($existingEstudiante && $existingEstudiante->estado === 'reprobado') {
                    $existingEstudiante->estado = 'activo';
                    $existingEstudiante->save();
                }

                // Buscar el usuario por correo
                $userEstudiante = User::where('email', $estudianteData['correo'])->first();

                if ($userEstudiante) {
                    // Actualiza nombre y rol
                    $userEstudiante->update([
                        'name' => $estudianteData['nombre'] . ' ' . $estudianteData['apellido'],
                        'role' => 'estudiante'
                    ]);
                } else {
                    // Crea usuario
                    $userEstudiante = User::create([
                        'name'     => $estudianteData['nombre'] . ' ' . $estudianteData['apellido'],
                        'email'    => $estudianteData['correo'],
                        'password' => Hash::make($estudianteData['cedula']),
                        'role'     => 'estudiante'
                    ]);
                }
                $estudianteUserId = $userEstudiante->id;

                // Actualizar o crear registro en la tabla estudiantes
                Estudiante::updateOrCreate(
                    ['cedula' => $estudianteData['cedula']],
                    [
                        'nombre'   => $estudianteData['nombre'],
                        'apellido' => $estudianteData['apellido'],
                        'genero'   => $estudianteData['genero'],
                        'correo'   => $estudianteData['correo'],
                        'user_id' => $estudianteUserId,
                        'estado'   => 'activo'  // Se asigna el estado activo para nuevos registros o reactivación de los reprobados
                    ]
                );

                // Insertar en project_members
                ProjectMember::firstOrCreate(
                    ['project_id' => $project->id, 'user_id' => $estudianteUserId],
                    ['role' => 'estudiante']
                );
            }

            // 5. Procesar archivos de la fase "Presentación de Propuestas"
            if ($request->hasFile('archivos')) {
                // Buscar la fase
                $fase = FaseConvocatoria::where('convocatoria_id', $convocatoria->id)
                    ->where('nombre', 'Presentación de Propuestas')
                    ->first();

                if ($fase) {
                    foreach ($request->file('archivos') as $file) {
                        $base64File = base64_encode(file_get_contents($file->getRealPath()));
                        ProyectoArchivoFase::create([
                            'proyecto_id' => $project->id,
                            'fase_id'     => $fase->id,
                            'titulo'      => $file->getClientOriginalName(),
                            'file_data'   => $base64File,
                            'mime_type'   => $file->getMimeType(),
                        ]);
                    }
                } else {
                    Log::warning('No se encontró la fase "Presentación de Propuestas" para la convocatoria ' . $convocatoria->id);
                }
            }

            // Si todo sale bien, confirmamos la transacción
            DB::commit();

            return response()->json([
                'message' => 'Proyecto creado exitosamente.'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Si ocurre un error de validación, revertimos los cambios
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el proyecto.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Si ocurre cualquier otro error, también revertimos los cambios
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el proyecto.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function actualizarProyecto(Request $request)
    {
        DB::beginTransaction();

        try {
            // 1. Validar los datos de entrada
            $validatedData = $request->validate([
                'proyecto_id'               => 'required|exists:proyectos,id',
                'convocatoria_id'           => 'required|exists:convocatorias,id',
                'nombre'                    => 'required|string|max:255',
                'dominio'                   => 'required|string|max:255',
                'fase'                      => 'required|string|in:Fase1,Fase2,Fase3',
                'fasePresentacion' => 'required|string|',
                'institucion_beneficiaria'  => 'required|string|max:255',
                'canton'                    => 'required|string|max:255',
                'parroquia'                 => 'required|string|max:255',
                'oferta_academica'          => 'required|string|in:Pregrado,Posgrado',
                'facultad'                  => 'required|string|max:255',
                'carrera'                   => 'required|string|max:255',
                'modalidad'                 => 'required|string|in:Presencial,Híbrida,Online',

                // Docente coordinador
                'docente_coordinador.nombre'   => 'required|string|max:255',
                'docente_coordinador.apellido' => 'required|string|max:255',
                'docente_coordinador.cedula'   => 'required|string|max:20',
                'docente_coordinador.correo'   => 'required|email|max:255',
                'docente_coordinador.telefono' => 'required|string|max:20',

                // Estudiantes
                'estudiantes'                 => 'nullable|array',
                'estudiantes.*.nombre'        => 'required|string|max:255',
                'estudiantes.*.apellido'      => 'required|string|max:255',
                'estudiantes.*.cedula'        => 'required|string|max:20',
                'estudiantes.*.genero'        => 'required|string|in:Masculino,Femenino',
                'estudiantes.*.correo'        => 'required|email|max:255',

                // Archivos
                'archivos'                    => 'nullable|array',
                'archivos.*'                  => 'file|mimes:pdf,doc,docx|max:10240'
            ]);

            // 2. Obtener el proyecto existente
            $proyecto = Proyecto::findOrFail($validatedData['proyecto_id']);

            // 3. Actualizar los campos básicos del proyecto
            $proyecto->update([
                'convocatoria_id'          => $validatedData['convocatoria_id'],
                'nombre'                   => $validatedData['nombre'],
                'dominio'                  => $validatedData['dominio'],
                'fase'                     => $validatedData['fase'],
                'institucion_beneficiaria' => $validatedData['institucion_beneficiaria'],
                'canton'                   => $validatedData['canton'],
                'parroquia'                => $validatedData['parroquia'],
                'oferta_academica'         => $validatedData['oferta_academica'],
                'facultad'                 => $validatedData['facultad'],
                'carrera'                  => $validatedData['carrera'],
                'modalidad'                => $validatedData['modalidad'],
                // Puedes cambiar el estado según tu lógica (por ejemplo, si vuelve a "enviado" o "en correcciones")
                'estado'                   => 'enviado',
                'estado_fase'                   => 'subida',
            ]);

            // 4. Procesar el docente coordinador
            $docenteData = $validatedData['docente_coordinador'];
            // Buscar/actualizar el usuario docente
            $userDocente = User::where('email', $docenteData['correo'])->first();
            if ($userDocente) {
                $userDocente->update([
                    'name' => $docenteData['nombre'] . ' ' . $docenteData['apellido'],
                    'role' => 'profesor'
                ]);
            } else {
                // Si no existe, crear
                $userDocente = User::create([
                    'name'     => $docenteData['nombre'] . ' ' . $docenteData['apellido'],
                    'email'    => $docenteData['correo'],
                    'password' => Hash::make($docenteData['cedula']), // Contraseña generada
                    'role'     => 'profesor'
                ]);
            }
            // Actualizar o crear el docente en la tabla 'docentes'
            Docente::updateOrCreate(
                ['cedula' => $docenteData['cedula']],
                [
                    'nombre'   => $docenteData['nombre'],
                    'apellido' => $docenteData['apellido'],
                    'correo'   => $docenteData['correo'],
                    'telefono' => $docenteData['telefono']
                ]
            );
            // Vincularlo al proyecto en la tabla project_members
            ProjectMember::updateOrCreate(
                ['project_id' => $proyecto->id, 'user_id' => $userDocente->id],
                ['role'       => 'profesor']
            );

            // 5. Procesar los estudiantes
            if (!empty($validatedData['estudiantes'])) {
                foreach ($validatedData['estudiantes'] as $estudianteData) {
                    // Buscar o crear usuario para el estudiante
                    $userEstudiante = User::where('email', $estudianteData['correo'])->first();
                    if ($userEstudiante) {
                        $userEstudiante->update([
                            'name' => $estudianteData['nombre'] . ' ' . $estudianteData['apellido'],
                            'role' => 'estudiante'
                        ]);
                    } else {
                        $userEstudiante = User::create([
                            'name'     => $estudianteData['nombre'] . ' ' . $estudianteData['apellido'],
                            'email'    => $estudianteData['correo'],
                            'password' => Hash::make($estudianteData['cedula']),
                            'role'     => 'estudiante'
                        ]);
                    }
                    // Actualizar o crear registro en la tabla 'estudiantes'
                    Estudiante::updateOrCreate(
                        ['cedula' => $estudianteData['cedula']],
                        [
                            'nombre'   => $estudianteData['nombre'],
                            'apellido' => $estudianteData['apellido'],
                            'genero'   => $estudianteData['genero'],
                            'correo'   => $estudianteData['correo'],
                            'user_id' => $userEstudiante->id,
                        ]
                    );
                    // Vincular en project_members
                    ProjectMember::updateOrCreate(
                        ['project_id' => $proyecto->id, 'user_id' => $userEstudiante->id],
                        ['role' => 'estudiante']
                    );
                }
            }
            // 1. Buscar la convocatoria asociada
            $convocatoria = Convocatoria::findOrFail($request->convocatoria_id);
            // 6. Procesar archivos (si se envían nuevos)
            if ($request->hasFile('archivos')) {
                // Buscar la fase
                $fase = FaseConvocatoria::where('convocatoria_id', $convocatoria->id)
                    ->where('nombre', 'Presentación de Propuestas')
                    ->first();

                if ($fase) {
                    foreach ($request->file('archivos') as $file) {
                        $base64File = base64_encode(file_get_contents($file->getRealPath()));
                        ProyectoArchivoFase::create([
                            'proyecto_id' => $proyecto->id,
                            'fase_id'     => $fase->id,
                            'titulo'      => $file->getClientOriginalName(),
                            'file_data'   => $base64File,
                            'mime_type'   => $file->getMimeType(),
                        ]);
                    }
                } else {
                    Log::warning('No se encontró la fase "Presentación de Propuestas" para la convocatoria ' . $convocatoria->id);
                }
            }

            // 7. Actualizar las observaciones pendientes a 'corregido'
            ProyectoObservacion::where('proyecto_id', $proyecto->id)
                ->where('estado', 'pendiente')
                ->update(['estado' => 'cumplida']);

            DB::commit();

            return response()->json([
                'message' => 'Proyecto actualizado correctamente.'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al actualizar el proyecto.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar proyecto: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error al actualizar el proyecto.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function obtenerDetalleProyecto($id)
    {
        try {
            // Obtener el proyecto con sus miembros (incluyendo la relación con User) y archivos.
            $proyecto = Proyecto::with(['miembros.user', 'archivos.fase'])->findOrFail($id);

            // Recorrer cada miembro para adjuntar los detalles adicionales
            foreach ($proyecto->miembros as $miembro) {
                if ($miembro->role === 'profesor') {
                    // Buscar en la tabla de docentes utilizando el correo del usuario
                    $docente = Docente::where('correo', $miembro->user->email)->first();
                    $miembro->detalles = $docente;
                } elseif ($miembro->role === 'estudiante') {
                    // Buscar en la tabla de estudiantes utilizando el correo del usuario
                    $estudiante = Estudiante::where('correo', $miembro->user->email)->first();
                    $miembro->detalles = $estudiante;
                }
            }

            return response()->json([
                'proyecto' => $proyecto
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los detalles del proyecto.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function obtenerProyectosDelProfesor(Request $request)
    {
        try {
            // Obtenemos el usuario autenticado (profesor)
            $user = Auth::user();
            // También podrías usar $request->user() si tienes activado el middleware auth:api

            // Filtramos los proyectos donde el user sea miembro con rol 'profesor'
            // Asumiendo que en tu relación se llama 'miembros' y en la pivot o tabla intermedia
            // guardas user_id y role (profesor, estudiante, etc.)
            $proyectos = Proyecto::whereHas('miembros', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('role', 'profesor');
            })
                ->with('archivos.fase') // Ejemplo si quieres traer archivos y la fase de cada archivo
                ->get();

            return response()->json([
                'proyectos' => $proyectos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los proyectos del profesor.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function subirArchivosFase(Request $request)
    {
        DB::beginTransaction();

        try {
            // 1. Validar la solicitud
            $validatedData = $request->validate([
                'proyecto_id'    => 'required|exists:proyectos,id',
                'fase'           => 'required|string|in:Fase1,Fase2,Fase3',
                'archivos'       => 'nullable|array',
                'archivos.*'     => 'file|mimes:pdf,doc,docx|max:10240', // 10 MB
            ]);

            // 2. Obtener el proyecto
            $proyecto = Proyecto::findOrFail($validatedData['proyecto_id']);
            // Guardamos el estado anterior para saber si estaba en "correcciones"
            $estadoAnterior = $proyecto->estado;

            // 3. Verificar que la fase del proyecto coincida con la solicitada
            if ($proyecto->fase !== $validatedData['fase']) {
                return response()->json([
                    'message' => "El proyecto no se encuentra realmente en {$validatedData['fase']}."
                ], 400);
            }

            // 4. Mapear el valor enviado para buscar la fase en la BD
            $nombreFaseBD = null;
            if ($validatedData['fase'] === 'Fase2') {
                $nombreFaseBD = 'Avance de Proyectos de Vinculación';
            } elseif ($validatedData['fase'] === 'Fase3') {
                $nombreFaseBD = 'Cierre de Proyectos de Vinculación';
            } else {
                $nombreFaseBD = 'Presentación de Propuestas';
            }

            // 5. Buscar la fase correspondiente en 'fase_convocatorias'
            $fase = FaseConvocatoria::where('convocatoria_id', $proyecto->convocatoria_id)
                ->where('nombre', $nombreFaseBD)
                ->first();

            if (!$fase) {
                return response()->json([
                    'message' => "No se encontró la fase '$nombreFaseBD' para este proyecto."
                ], 404);
            }

            // 6. Procesar los archivos enviados (si existen)
            if ($request->hasFile('archivos')) {
                foreach ($request->file('archivos') as $file) {
                    $base64File = base64_encode(file_get_contents($file->getRealPath()));
                    ProyectoArchivoFase::create([
                        'proyecto_id' => $proyecto->id,
                        'fase_id'     => $fase->id,
                        'titulo'      => $file->getClientOriginalName(),
                        'file_data'   => $base64File,
                        'mime_type'   => $file->getMimeType(),
                    ]);
                }
            }

            // 7. Actualizar el proyecto: se marca como 'enviado' y 'subida'
            $proyecto->update([
                'estado'      => 'enviado',
                'estado_fase' => 'subida',
            ]);

            // 8. Si el proyecto estaba previamente en "correcciones", actualizar las observaciones pendientes a "cumplida"
            if ($estadoAnterior === 'correcciones') {
                // Suponiendo que las observaciones pendientes tienen estado "pendiente"
                \App\Models\ProyectoObservacion::where('proyecto_id', $proyecto->id)
                    ->where('estado', 'pendiente')
                    ->update(['estado' => 'cumplida']);
            }

            DB::commit();

            return response()->json([
                'message' => "Archivos subidos correctamente en {$validatedData['fase']}."
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al subir archivos de la fase.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al subir archivos Fase: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al subir archivos de la fase.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function actualizarProyectoFase3(Request $request)
    {
        DB::beginTransaction();

        try {
            Log::info('Nuevos campos para fase 3: ', ['request' => $request->all()]);

            // 1. Validar datos, incluyendo resumen y nota_docente para estudiantes
            $validatedData = $request->validate([
                'convocatoria_id' => 'required|exists:convocatorias,id',
                'proyecto_id'               => 'required|exists:proyectos,id',
                'resumen'                   => 'nullable|string',
                'estudiantes'               => 'nullable|array',
                'estudiantes.*.correo'        => 'required|email|max:255',
                'estudiantes.*.cedula'        => 'required|string|max:10',
                'estudiantes.*.nota_docente' => 'nullable|numeric|min:0|max:10',
                // ... validación de archivos ...
            ]);

            // 2. Obtener el proyecto
            $proyecto = Proyecto::findOrFail($validatedData['proyecto_id']);
            // Guardamos el estado anterior para saber si estaba en "correcciones"
            $estadoAnterior = $proyecto->estado;
            // 3. Actualizar campos básicos y el resumen
            $proyecto->update([
                'estado'                   => 'enviado',
                'estado_fase'              => 'subida',
                'resumen'                  => $validatedData['resumen'],
            ]);

            // 5. Procesar los estudiantes
            if (!empty($validatedData['estudiantes'])) {
                foreach ($validatedData['estudiantes'] as $estudianteData) {
                    // Buscar o crear usuario para el estudiante
                    // Buscar o crear usuario para el estudiante
                    $userEstudiante = User::where('email', $estudianteData['correo'])->first();

                    // Actualizar o crear registro en la tabla 'estudiantes'
                    Estudiante::updateOrCreate(
                        ['cedula' => $estudianteData['cedula']],
                        [
                            'user_id' => $userEstudiante->id,
                            'nota_docente' => $estudianteData['nota_docente'] ?? null,
                        ]
                    );
                    // Vincular en project_members
                    // if ($estudianteData['nota_docente']) {
                    //     // Vincular en project_members, incluyendo la nota_docente
                    //     ProjectMember::updateOrCreate(
                    //         ['project_id' => $proyecto->id, 'user_id' => $userEstudiante->id],
                    //         [
                    //             'role' => 'estudiante',
                    //              'nota_docente' => $estudianteData['nota_docente'] ?? null,
                    //         ]
                    //     );
                    // }
                }
            }
            // 5. Procesar archivos según la fase del proyecto
            // Determinar el nombre de la fase
            $faseName = ($proyecto->fase === 'Fase3') ? 'Cierre de Proyectos de Vinculación' : 'Presentación de Propuestas';
            $convocatoria = Convocatoria::findOrFail($validatedData['convocatoria_id']);
            $fase = FaseConvocatoria::where('convocatoria_id', $convocatoria->id)
                ->where('nombre', $faseName)
                ->first();

            if ($fase && $request->hasFile('archivos')) {
                foreach ($request->file('archivos') as $file) {
                    $base64File = base64_encode(file_get_contents($file->getRealPath()));
                    ProyectoArchivoFase::create([
                        'proyecto_id' => $proyecto->id,
                        'fase_id'     => $fase->id,
                        'titulo'      => $file->getClientOriginalName(),
                        'file_data'   => $base64File,
                        'mime_type'   => $file->getMimeType(),
                    ]);
                }
            }

            // 6. Actualizar observaciones, etc. (según tu lógica)

            // 8. Si el proyecto estaba previamente en "correcciones", actualizar las observaciones pendientes a "cumplida"
            if ($estadoAnterior === 'correcciones') {
                // Suponiendo que las observaciones pendientes tienen estado "pendiente"
                \App\Models\ProyectoObservacion::where('proyecto_id', $proyecto->id)
                    ->where('estado', 'pendiente')
                    ->update(['estado' => 'cumplida']);
            }


            DB::commit();

            return response()->json([
                'message' => 'Proyecto actualizado correctamente.'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el proyecto.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar proyecto: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al actualizar el proyecto.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // public function finalizarProyectoFase3(Request $request, $projectId)
    // {
    //     // Validar que se envíe el arreglo de estudiantes con sus notas administrativas
    //     $validatedData = $request->validate([
    //         'proyecto_id'               => 'required|exists:proyectos,id',
    //         'estudiantes' => 'required|array',
    //         'estudiantes.*.id' => 'required|exists:estudiantes,id',
    //         'estudiantes.*.nota_admin' => 'required|numeric|min:1|max:10',
    //     ]);

    //     // Buscar el proyecto
    //     $proyecto = Proyecto::findOrFail($projectId);

    //     // Recorrer cada estudiante y actualizar su nota final y estado
    //     foreach ($validatedData['estudiantes'] as $estudianteData) {
    //         // Se asume que la nota del docente ya se encuentra almacenada
    //         $estudiante = Estudiante::findOrFail($estudianteData['id']);
    //         $notaDocente = $estudiante->nota_docente;
    //         $notaAdmin = $estudianteData['nota_admin'];

    //         // Calcular el promedio y redondear a dos decimales
    //         $notaFinal = round(($notaDocente + $notaAdmin) / 2, 2);

    //         // Determinar el estado según el promedio
    //         $estado = ($notaFinal >= 7) ? 'aprobado' : 'reprobado';

    //         // Actualizar el registro del estudiante
    //         $estudiante->update([
    //             'nota_admin' => $notaAdmin,
    //             'nota_final' => $notaFinal,
    //             'estado'     => $estado,
    //         ]);
    //     }

    //     // Marcar el proyecto como finalizado o actualizar su estado de fase
    //     $proyecto->update([
    //         'estado_fase' => 'finalizada',
    //         'estado' => 'finalizado',
    //     ]);

    //     return response()->json([
    //         'message' => 'Proyecto finalizado y notas actualizadas correctamente.'
    //     ], 200);
    // }
    public function finalizarProyectoFase3(Request $request, $projectId)
    {
        Log::info('Iniciando finalizacion de proyecto y certificados', ['request' => $request->all()]);
        // Validar que se envíe el arreglo de estudiantes con sus notas administrativas
        $validatedData = $request->validate([
            // 'proyecto_id'               => 'required|exists:proyectos,id',
            'estudiantes'               => 'required|array',
            'estudiantes.*.id'          => 'required|exists:estudiantes,id',
            'estudiantes.*.nota_admin'  => 'required|numeric|min:1|max:10',
        ]);

        // Buscar el proyecto
        $proyecto = Proyecto::findOrFail($projectId);

        // Recorrer cada estudiante y actualizar su nota final y estado
        foreach ($validatedData['estudiantes'] as $estudianteData) {
            $estudiante = Estudiante::findOrFail($estudianteData['id']);
            $notaDocente = $estudiante->nota_docente;
            $notaAdmin = $estudianteData['nota_admin'];

            // Calcular el promedio y redondear a dos decimales
            $notaFinal = round(($notaDocente + $notaAdmin) / 2, 2);

            // Determinar el estado según el promedio
            $estado = ($notaFinal >= 7) ? 'aprobado' : 'reprobado';

            // Actualizar el registro del estudiante
            $estudiante->update([
                'nota_admin'  => $notaAdmin,
                'nota_final'  => $notaFinal,
                'estado'      => $estado,
            ]);
        }

        // Marcar el proyecto como finalizado o actualizar su estado de fase
        $proyecto->update([
            'estado_fase' => 'finalizada',
            'estado'      => 'finalizado',
        ]);

        // Generar certificados para estudiantes aprobados
        foreach ($validatedData['estudiantes'] as $estudianteData) {
            $estudiante = Estudiante::findOrFail($estudianteData['id']);
            if ($estudiante->estado === 'aprobado') {
                // Generar contenido del código QR
                $qrData = "Universidad Estatal de Bolívar :: Certificado de Vinculación ::
                       Nombre: {$estudiante->nombre} {$estudiante->apellido} ::
                       Cédula: {$estudiante->cedula} ::
                       Código Proyecto: {$proyecto->codigo_proyecto} ::
                       Número Resolución: {$proyecto->numero_resolucion}";
                $qrCode = base64_encode(QrCode::format('svg')->size(200)->generate($qrData));

                // Genera el número de certificado secuencial
                $numeroCertificado = \App\Models\Certificado::generarNumeroCertificado();

                // Datos a enviar a la vista del certificado
                $data = [
                    'proyecto'         => $proyecto->nombre,
                    'codigo'           => $proyecto->codigo_proyecto,
                    'resolucion'       => $proyecto->numero_resolucion,
                    'estudiante'       => "{$estudiante->nombre} {$estudiante->apellido}",
                    'cedula'           => $estudiante->cedula,
                    'estudiante_email' => $estudiante->correo,
                    // 'fecha'            => now()->format('d-m-Y'),
                    'fecha' => \Carbon\Carbon::now('America/Guayaquil')->format('d-m-Y'),
                    'qrCode'           => $qrCode,
                    'certificado'      => $numeroCertificado,  // <-- Aquí se pasa el número de certificado
                    'nota_final' => $estudiante->nota_final,
                ];

                // Generar el PDF del certificado
                $pdf = Pdf::loadView('certificados.certificado', $data)
                    ->setPaper('A4', 'landscape')
                    ->setOption('margin-left', 0)
                    ->setOption('margin-right', 0)
                    ->setOption('margin-top', 0)
                    ->setOption('margin-bottom', 0);

                // Obtener el contenido binario del PDF
                $pdfContent = $pdf->output();

                // Guardar el certificado en la tabla "certificados"
                \App\Models\Certificado::create([
                    'project_id'        => $proyecto->id,
                    'user_id'           => $estudiante->user_id, // O bien, el id del usuario relacionado
                    'numero_certificado' => $numeroCertificado,
                    'fecha_emision'     => now()->format('Y-m-d'),
                    'rol'               => 'estudiante',
                    'estado'            => 'activo',
                    'titulo'            => 'Certificado de Participación',
                    'file_data'         => base64_encode($pdfContent),
                    'mime_type'         => 'application/pdf',
                ]);
            }
        }


        // Generar certificados para los docentes (pueden ser varios)
        // $docentes = $proyecto->miembros()->where('role', 'profesor')->with('user')->get();
        $docentes = $proyecto->miembros()
        ->where('role', 'profesor')
        ->with(['user', 'user.docente'])
        ->get();
        // foreach ($docentes as $docente) {
        //     // Generar contenido del código QR para el docente
        //     $qrData = "Universidad Estatal de Bolívar :: Certificado de Vinculación ::
        //            Nombre: {$docente->user->name} ::
        //            Código Proyecto: {$proyecto->codigo_proyecto} ::
        //            Número Resolución: {$proyecto->numero_resolucion}";
        //     $qrCode = base64_encode(QrCode::format('svg')->size(200)->generate($qrData));

        //     // Datos para la vista del certificado (puedes ajustar los campos según corresponda)
        //     $numeroCertificado = \App\Models\Certificado::generarNumeroCertificado();

        //     $data = [
        //         'proyecto'         => $proyecto->nombre,
        //         'codigo'           => $proyecto->codigo_proyecto,
        //         'resolucion'       => $proyecto->numero_resolucion,
        //         'estudiante'       => $docente->nombre,
        //         'cedula'           => $docente->cedula, // Si no se cuenta con cédula, se usa otro identificador
        //         'estudiante_email' => $docente->user->email,
        //         // 'fecha'            => now()->format('d-m-Y'),
        //         'fecha' => \Carbon\Carbon::now('America/Guayaquil')->format('d-m-Y'),
        //         'qrCode'           => $qrCode,
        //         'certificado'      => $numeroCertificado  // <-- Aquí se pasa el número de certificado
        //     ];

        //     // Generar el PDF del certificado
        //     $pdf = Pdf::loadView('certificados.certificado', $data)
        //         ->setPaper('A4', 'landscape')
        //         ->setOption('margin-left', 0)
        //         ->setOption('margin-right', 0)
        //         ->setOption('margin-top', 0)
        //         ->setOption('margin-bottom', 0);

        //     $pdfContent = $pdf->output();

        //     // Guardar el certificado del docente en la tabla "certificados"
        //     \App\Models\Certificado::create([
        //         'project_id'        => $proyecto->id,
        //         'user_id'           => $docente->user->id,
        //         'numero_certificado' => $numeroCertificado,
        //         'fecha_emision'     => now()->format('Y-m-d'),
        //         'rol'               => 'docente',
        //         'estado'            => 'activo',
        //         'titulo'            => 'Certificado de Participación',
        //         'file_data'         => base64_encode($pdfContent),
        //         'mime_type'         => 'application/pdf',
        //     ]);
        // }
        foreach ($docentes as $docenteMember) {
            // Obtiene el registro docente a través del usuario relacionado
            $docenteData = $docenteMember->user->docente;

            // Verifica que exista la información del docente
            if (!$docenteData) {
                Log::warning("No se encontró información de docente para el usuario {$docenteMember->user->id}");
                continue;
            }

            // Generar contenido del código QR para el docente
            $qrData = "Universidad Estatal de Bolívar :: Certificado de Vinculación ::
               Nombre: {$docenteData->nombre} {$docenteData->apellido} ::
               Código Proyecto: {$proyecto->codigo_proyecto} ::
               Número Resolución: {$proyecto->numero_resolucion}";
            $qrCode = base64_encode(QrCode::format('svg')->size(200)->generate($qrData));

            // Generar el número secuencial del certificado
            $numeroCertificado = \App\Models\Certificado::generarNumeroCertificado();

            // Datos para la vista del certificado
            $data = [
                'proyecto'         => $proyecto->nombre,
                'codigo'           => $proyecto->codigo_proyecto,
                'resolucion'       => $proyecto->numero_resolucion,
                // Aquí usamos el nombre y apellido del docente obtenido de la relación
                'estudiante'       => $docenteData->nombre . ' ' . $docenteData->apellido,
                'cedula'           => $docenteData->cedula,
                'estudiante_email' => $docenteMember->user->email,
                'fecha'            => \Carbon\Carbon::now('America/Guayaquil')->format('d-m-Y'),
                'qrCode'           => $qrCode,
                'certificado'      => $numeroCertificado
            ];

            // Generar el PDF del certificado
            $pdf = Pdf::loadView('certificados.certificado', $data)
                ->setPaper('A4', 'landscape')
                ->setOption('margin-left', 0)
                ->setOption('margin-right', 0)
                ->setOption('margin-top', 0)
                ->setOption('margin-bottom', 0);

            $pdfContent = $pdf->output();

            // Guardar el certificado del docente en la tabla "certificados"
            \App\Models\Certificado::create([
                'project_id'         => $proyecto->id,
                'user_id'            => $docenteMember->user->id,
                'numero_certificado' => $numeroCertificado,
                'fecha_emision'      => \Carbon\Carbon::now('America/Guayaquil')->format('Y-m-d'),
                'rol'                => 'docente',
                'estado'             => 'activo',
                'titulo'             => 'Certificado de Participación',
                'file_data'          => base64_encode($pdfContent),
                'mime_type'          => 'application/pdf',
            ]);
        }

        return response()->json([
            'message' => 'Proyecto finalizado, notas actualizadas y certificados generados correctamente.'
        ], 200);
    }



    public function downloadProyecto($archivoId)
    {
        $archivo = ProyectoArchivoFase::find($archivoId);
        // if (!$archivo) {
        //     return response()->json(['message' => 'Archivo no encontrado'], Response::HTTP_NOT_FOUND);
        // }

        $decodedFile = base64_decode($archivo->file_data);

        return response($decodedFile, 200)
            ->header('Content-Type', $archivo->mime_type ?? 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $archivo->titulo . '"');
    }

    public function obtenerProyectosAprobadosDelDocente()
    {
        try {
            $user = Auth::user(); // Docente logueado
            // Filtrar proyectos donde user sea miembro con rol=profesor, estado=aprobado
            $proyectos = Proyecto::where('estado', 'aprobado')->whereHas('miembros', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('role', 'profesor');
            })
                ->get();

            return response()->json(['proyectos' => $proyectos], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener proyectos aprobados del docente',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    //sirve usando la ruta convocatorias/{id}/proyectos
    public function listarProyectosPorConvocatoria($id)
    {
        try {
            // Buscar la convocatoria con sus proyectos incluyendo los campos necesarios
            $convocatoria = Convocatoria::with([
                'proyectos:id,convocatoria_id,nombre,carrera,fase,fasePresentacion,modalidad,estado,estado_fase,codigo_proyecto,numero_resolucion'
            ])->findOrFail($id);

            return response()->json([
                'convocatoria' => $convocatoria->titulo,
                'proyectos' => $convocatoria->proyectos
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Convocatoria no encontrada.'], 404);
        }
    }

    public function aprobarProyectoFase1(Request $request, $id)
    {
        $data = $request->validate([
            'codigo_proyecto' => 'required|string|max:50',
            'numero_resolucion' => 'required|string|max:50',
        ]);

        $proyecto = Proyecto::findOrFail($id);

        // 1. Verificar si está en Fase1
        if ($proyecto->fase !== 'Fase1') {
            return response()->json([
                'message' => 'El proyecto no está en Fase1.'
            ], 400);
        }

        // 2. Verificar que su estado_fase sea 'subida'
        if ($proyecto->estado_fase !== 'subida') {
            return response()->json([
                'message' => 'No se puede aprobar Fase1 porque estado_fase no es "subida".'
            ], 400);
        }


        // 3. Cambios de fase y estado
        $proyecto->estado = 'aprobado';
        $proyecto->estado_fase = 'pendiente'; // Listo para la siguiente fase
        $proyecto->fase = 'Fase2';

        // 4. Guardar código y resolución
        $proyecto->codigo_proyecto = $data['codigo_proyecto'];
        $proyecto->numero_resolucion = $data['numero_resolucion'];

        $proyecto->save();

        return response()->json(['message' => 'Proyecto aprobado en Fase1.']);
    }

    public function aprobarProyectoFaseSiguiente(Request $request, $id)
    {
        $proyecto = Proyecto::findOrFail($id);

        // Verificar que esté en Fase2
        if (!in_array($proyecto->fase, ['Fase2'])) {
            return response()->json(['message' => 'El proyecto no está en Fase2.'], 400);
        }

        // Verificar estado_fase: aquí exiges que sea si estás en Fase2
        if ($proyecto->estado_fase !== 'subida') {
            return response()->json([
                'message' => 'No se puede aprobar la Fase porque estado_fase no es "subida".'
            ], 400);
        }

        // Lógica para avanzar fase
        if ($proyecto->fase === 'Fase2') {
            $proyecto->fase = 'Fase3';
            $proyecto->estado = 'aprobado';
            $proyecto->estado_fase = 'pendiente'; // o 'finalizada' si prefieres
        } elseif ($proyecto->fase === 'Fase3') {
            // última fase
            $proyecto->estado = 'aprobado';
            $proyecto->estado_fase = 'finalizada';
        } else {
            return response()->json([
                'message' => 'El proyecto no está en Fase2 o Fase3.'
            ], 400);
        }

        $proyecto->save();

        return response()->json(['message' => 'Proyecto aprobado en la fase siguiente.']);
    }


    /**
     * Enviar Proyecto a Correcciones
     */
    public function enviarCorreccion(Request $request, $id)
    {
        $proyecto = Proyecto::findOrFail($id);
        $proyecto->estado = 'correcciones';
        $proyecto->estado_fase = 'pendiente';  // El docente debe corregirlo y volver a enviarlo
        $proyecto->save();

        return response()->json(['message' => 'Proyecto enviado para correcciones.']);
    }


    /**
     * Permitir acceso a la siguiente fase si el proyecto fue aprobado
     */
    public function siguienteFase($id)
    {
        $proyecto = Proyecto::findOrFail($id);

        if ($proyecto->estado_fase !== 'aprobado') {
            return response()->json(['message' => 'No puedes acceder a la siguiente fase aún.'], 403);
        }

        return response()->json(['message' => 'Acceso permitido a la siguiente fase.', 'proyecto' => $proyecto]);
    }


    public function obtenerEstadoProyecto($convocatoriaId)
    {
        try {
            // Buscar el proyecto asociado a la convocatoria
            $proyecto = Proyecto::where('convocatoria_id', $convocatoriaId)->first();

            if (!$proyecto) {
                return response()->json(['error' => 'Proyecto no encontrado'], 404);
            }

            return response()->json([
                'estado' => $proyecto->estado
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener el estado del proyecto'], 500);
        }
    }
    public function getMiembrosConCertificados($projectId)
    {
        $proyecto = \App\Models\Proyecto::with(['miembros.user'])->findOrFail($projectId);

        // Si deseas que cada miembro incluya su certificado (activando el accesor)
        $miembros = $proyecto->miembros->map(function ($miembro) {
            // Esto carga el certificado para cada miembro usando el accesor
            $miembro->certificado;
            return $miembro;
        });

        return response()->json($miembros);
    }
    
}
