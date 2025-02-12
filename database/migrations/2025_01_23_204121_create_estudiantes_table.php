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
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id(); // Clave primaria
            $table->unsignedBigInteger('proyecto_id'); // Llave foránea
            $table->string('nombre');
            $table->string('apellido');
            $table->string('cedula')->unique();
            $table->string('genero');
            $table->string('correo')->unique();
            $table->timestamps();
        
            // Definición de la clave foránea
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->onDelete('cascade');
        });
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
