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
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convocatoria_id')->constrained()->onDelete('cascade');
            $table->string('nombre');
            $table->string('dominio');
            $table->string('fase');
            $table->json('docente_coordinador');
            $table->string('institucion_beneficiaria');
            $table->string('canton');
            $table->string('parroquia');
            $table->string('oferta_academica');
            $table->string('facultad');
            $table->string('carrera');
            $table->string('modalidad');
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyectos_');
    }
};
