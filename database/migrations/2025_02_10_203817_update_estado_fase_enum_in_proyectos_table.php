<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateEstadoFaseEnumInProyectosTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            // Se modifica la columna para agregar el valor 'pendiente'
            DB::statement("ALTER TABLE proyectos MODIFY estado_fase ENUM('subida','evaluacion','aprobado','finalizado','pendiente') NOT NULL DEFAULT 'subida'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            // Revertir a la definición original sin 'pendiente'
            DB::statement("ALTER TABLE proyectos MODIFY estado_fase ENUM('subida','evaluacion','aprobado','finalizado') NOT NULL DEFAULT 'subida'");
        });
    }
}
