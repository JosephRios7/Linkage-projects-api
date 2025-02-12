<?php

use App\Http\Controllers\ArchivoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ConvocatoriaController;
use App\Http\Controllers\ConvocatoriaPublicaController;
use App\Http\Controllers\FaseConvocatoriaController;
use App\Http\Controllers\Proyectos\ProyectoController;
use App\Http\Controllers\FileDownloadController;
use App\Http\Controllers\ProyectoObservacionController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


// Descargar archivos
Route::get('/convocatorias/archivos/convocatoria/{archivoId}/download', [ArchivoController::class, 'downloadConvocatoria'])->name('archivos.convocatoria.download');
Route::get('/convocatorias/archivos/fase/{archivoId}/download', [ArchivoController::class, 'downloadFase'])->name('archivos.fase.download');
Route::get('/convocatorias/archivos/proyecto/{archivoId}/download', [ProyectoController::class, 'downloadProyecto'])->name('archivos.fase.download');


// Rutas protegidas por roles
Route::middleware(['auth:sanctum'])->group(function () {

    // Rutas para gestión de usuarios (administrador)
    Route::middleware(['role:admin'])->prefix('users')->group(function () {
        Route::post('/crear', [UserController::class, 'createUser']);
        Route::get('/listar', [UserController::class, 'listUsers']);
        Route::put('/editar/{id}', [UserController::class, 'updateUser']);
        Route::delete('/eliminar/{id}', [UserController::class, 'deleteUser']);
    });

    // Rutas para convocatorias (administrador)
    Route::middleware(['role:admin,revisor'])->prefix('convocatorias')->group(function () {

        //convocatorias
        Route::post('/', [ConvocatoriaController::class, 'crearConvocatoria'])->name('convocatorias.crear');
        Route::post('/fase/{fase_id}/archivos', [FaseConvocatoriaController::class, 'subirArchivosFase']);

        Route::post('/finalizar/{id}', [ConvocatoriaController::class, 'finalizar'])->name('convocatorias.finalizar');
        Route::post('/{id}', [ConvocatoriaController::class, 'publicar'])->name('convocatorias.publicar');
        Route::delete('/{id}', [ConvocatoriaController::class, 'destroy'])->name('convocatorias.destroy');
        Route::get('/obtener/{id}', [ConvocatoriaController::class, 'show'])->name('convocatorias.show');
        Route::put('/{id}', [ConvocatoriaController::class, 'editarConvocatoria'])->name('convocatorias.editar');



        // RGestion de proyectos Rol admin y revisor
        Route::get('/', [ConvocatoriaController::class, 'verConvocatoria'])->name('convocatorias.ver');
        Route::get('convocatorias/{id}/proyectos', [ProyectoController::class, 'listarProyectosPorConvocatoria']);
        // Route::get('/proyectos/{id}', [ProyectoController::class, 'verProyecto'])->name('proyectos.detalle');
        // Revisar y aprobar proyectos (Admin y Revisor)

        // Route::post('/proyectos/{id}/aprobar', [ProyectoController::class, 'aprobarProyecto'])->name('proyectos.aprobar');
        // Para aprobar en Fase1 (requiere código y resolución)
        Route::post('/proyectos/{id}/aprobar-fase1', [ProyectoController::class, 'aprobarProyectoFase1']);

        // Para aprobar en las fases siguientes (no requiere código/resolución)
        Route::post('/proyectos/{id}/aprobar-fase-siguiente', [ProyectoController::class, 'aprobarProyectoFaseSiguiente']);


        Route::post('/proyectos/{id}/correcciones', [ProyectoController::class, 'enviarCorreccion'])->name('proyectos.correcciones');
        Route::post('/proyectos/observaciones', [ProyectoObservacionController::class, 'enviarObservacion']);


        // Nueva ruta para descargar archivos
        // Descarga de archivo
        // Listar archivos
        Route::get('/archivos/convocatoria/{convocatoriaId}', [ArchivoController::class, 'listarArchivosConvocatoria'])->name('archivos.convocatoria.list');
        Route::get('/archivos/fase/{faseId}', [ArchivoController::class, 'listarArchivosFase'])->name('archivos.fase.list');

        // Eliminar archivos
        Route::delete('/archivos/convocatoria/{archivoId}', [ArchivoController::class, 'destroyConvocatoria'])->name('archivos.convocatoria.destroy');
        Route::delete('/archivos/fase/{archivoId}', [ArchivoController::class, 'destroyFase'])->name('archivos.fase.destroy');
        // Subir archivos
        Route::post('/archivos/convocatoria', [ArchivoController::class, 'storeConvocatoria'])->name('archivos.convocatoria.store');
        Route::post('/archivos/fase', [ArchivoController::class, 'storeFase'])->name('archivos.fase.store');

        Route::get('/fase_convocatorias/{convocatoriaId}', [FaseConvocatoriaController::class, 'listarFases'])
            ->name('fase_convocatorias.list');



        // Route::get('/proyectos', [ProyectoController::class, 'listarProyectos'])->name('proyectos.list');
    });
    Route::middleware(['auth:sanctum', 'role:profesor,admin'])->prefix('convocatorias')->group(function () {
        // ...
        Route::get('/archivos/fase/{faseId}', [ArchivoController::class, 'listarArchivosFase'])->name('archivos.fase.list');

        Route::get('/fase_convocatorias/{convocatoriaId}', [FaseConvocatoriaController::class, 'listarFases'])
            ->name('fase_convocatorias.list');
        Route::get('/fase_convocatorias/{convocatoriaId}/por_nombre', [FaseConvocatoriaController::class, 'obtenerFasePorNombre'])
            ->name('fase_convocatorias.obtenerPorNombre');
        // ...


    });

    Route::middleware(['auth:sanctum', 'role:profesor,admin,revisor'])->prefix('convocatorias')->group(function () {

        Route::get('/proyectos/{id}', [ProyectoController::class, 'obtenerDetalleProyecto']);
        Route::get('/proyectos/{proyectoId}/observaciones', [ProyectoObservacionController::class, 'obtenerObservacionesProyecto']);
    });


    // Rutas para convocatorias (profesor)
    Route::middleware(['role:profesor'])->prefix('convocatorias')->group(function () {
        Route::get('/publicadas', [ConvocatoriaPublicaController::class, 'listarPublicadas'])->name('convocatorias.publicadas');
        Route::get('/{id}', [ConvocatoriaPublicaController::class, 'obtenerConvocatoriaPorId'])->name('convocatorias.id');
        Route::post('/proyectos/formato', [ProyectoController::class, 'crearProyecto'])->name('proyectos.crear');
        // Ver la siguiente fase si el proyecto está aprobado
        Route::get('/convocatorias/{id}/estado', [ProyectoController::class, 'obtenerEstadoProyecto']);


        Route::get('/siguiente/{id}', [ProyectoController::class, 'siguienteFase'])->name('proyectos.siguienteFase');
        //obtenerProyectos de profesor
        Route::get('/profesor/proyectos', [ProyectoController::class, 'obtenerProyectosDelProfesor']);
        //editar
        Route::post('/proyectos/update', [ProyectoController::class, 'actualizarProyecto']);

        //ver proyectos aprovados
        Route::get('/profesor/proyectos-aprobados', [ProyectoController::class, 'obtenerProyectosAprobadosDelDocente']);
        //subir archivos a proyecto
        Route::post('/proyectos/subir-fase', [ProyectoController::class, 'subirArchivosFase']);
    });




    // Rutas para estudiantes (si las agregas en el futuro)
    Route::middleware(['role:estudiante'])->prefix('convocatorias')->group(function () {
        // Aquí puedes agregar rutas específicas para estudiantes
    });
});
