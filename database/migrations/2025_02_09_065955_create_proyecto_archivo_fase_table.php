<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProyectoArchivoFaseTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proyecto_archivo_fase', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proyecto_id');
            $table->unsignedBigInteger('fase_id');
            $table->string('titulo');
            // Cambia 'binary' por 'longText' para almacenar la cadena base64
            $table->longText('file_data');
            $table->string('mime_type');
            $table->timestamps();

            $table->foreign('proyecto_id')
            ->references('id')
                ->on('proyectos')
                ->onDelete('cascade');

            $table->foreign('fase_id')
            ->references('id')
                ->on('fase_convocatorias')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyecto_archivo_fase');
    }
}
