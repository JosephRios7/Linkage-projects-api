<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->enum('estado', ['enviado', 'en_revision', 'correcciones', 'aprobado'])->default('enviado');
            $table->string('codigo_proyecto')->nullable();
            $table->string('numero_resolucion')->nullable();
            $table->enum('estado_fase', ['subida', 'evaluacion', 'aprobado', 'finalizado'])->default('subida');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn(['estado', 'codigo_proyecto', 'numero_resolucion', 'estado_fase']);
        });
    }
};
