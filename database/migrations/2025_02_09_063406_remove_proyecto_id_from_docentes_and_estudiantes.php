<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveProyectoIdFromDocentesAndEstudiantes extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Para la tabla docentes
        Schema::table('docentes', function (Blueprint $table) {
            if (Schema::hasColumn('docentes', 'proyecto_id')) {
                // Primero se elimina la clave foránea
                $table->dropForeign(['proyecto_id']);
                // Luego se elimina la columna
                $table->dropColumn('proyecto_id');
            }
        });

        // Para la tabla estudiantes
        Schema::table('estudiantes', function (Blueprint $table) {
            if (Schema::hasColumn('estudiantes', 'proyecto_id')) {
                $table->dropForeign(['proyecto_id']);
                $table->dropColumn('proyecto_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // En el método down se vuelve a agregar la columna y la relación en caso de ser necesario revertir la migración.
        Schema::table('docentes', function (Blueprint $table) {
            $table->unsignedBigInteger('proyecto_id')->after('id');
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->onDelete('cascade');
        });

        Schema::table('estudiantes', function (Blueprint $table) {
            $table->unsignedBigInteger('proyecto_id')->after('id');
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->onDelete('cascade');
        });
    }
}
