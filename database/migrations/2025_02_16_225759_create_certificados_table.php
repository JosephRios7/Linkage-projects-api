<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCertificadosTable extends Migration
{
    public function up()
    {
        Schema::create('certificados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('user_id');
            $table->string('numero_certificado')->unique();
            $table->date('fecha_emision')->nullable();
            $table->enum('rol', ['estudiante', 'docente']);
            $table->string('estado')->default('activo');
            $table->string('titulo');
            $table->longText('file_data'); // almacenar el contenido del PDF (codificado en base64)
            $table->string('mime_type');   // 'application/pdf'
            $table->timestamps();

            // Claves foráneas
            $table->foreign('project_id')->references('id')->on('proyectos')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('certificados');
    }
}
