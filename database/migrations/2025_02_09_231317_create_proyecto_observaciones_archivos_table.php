<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProyectoObservacionesArchivosTable extends Migration
{
    public function up()
    {
        Schema::create('proyecto_observaciones_archivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_observacion');
            $table->string('titulo');
            $table->longText('file_data');
            $table->string('mime_type');
            $table->timestamps();

            // Clave foránea
            $table->foreign('id_observacion')->references('id')->on('proyecto_observaciones')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('proyecto_observaciones_archivos');
    }
}
