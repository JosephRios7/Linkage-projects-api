<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConvocatoriasTable extends Migration
{
    public function up()
    {
        Schema::create('convocatorias', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('file_path')->nullable(); // Columna para almacenar la ruta del archivo
            $table->enum('estado', ['borrador', 'publicado'])->default('borrador'); // Control de estado
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('convocatorias');
    }
}
