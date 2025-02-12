<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProyectoObservacionesTable extends Migration
{
    public function up()
    {
        Schema::create('proyecto_observaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proyecto_id');
            // $table->unsignedBigInteger('fase_id');
            $table->enum('estado', ['pendiente', 'cumplida'])->default('pendiente');
            $table->text('comentario')->nullable();
            $table->timestamps();

            // Claves foráneas
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->onDelete('cascade');
            // $table->foreign('fase_id')->references('id')->on('fase_convocatorias')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('proyecto_observaciones');
    }
}
