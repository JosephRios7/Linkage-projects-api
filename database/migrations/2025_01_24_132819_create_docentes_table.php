<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocentesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('docentes', function (Blueprint $table) {
            $table->id(); // ID del docente
            $table->unsignedBigInteger('proyecto_id'); // Clave foránea al proyecto
            $table->string('nombre', 50); // Nombre del docente
            $table->string('apellido', 50); // Apellido del docente
            $table->string('cedula', 10); // Cédula del docente
            $table->string('correo', 50); // Correo del docente
            $table->string('telefono', 10); // Teléfono del docente
            $table->timestamps();

            // Relación con la tabla proyectos
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('docentes');
    }
}
