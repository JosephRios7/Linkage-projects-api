<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('archivo_fases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fase_id')->constrained('fase_convocatorias')->onDelete('cascade'); // Relación con fase
            $table->string('titulo');
            $table->longText('file_data'); // Guardar en Base64
            $table->string('mime_type'); // Almacenar el tipo (ejemplo: application/msword)
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archivo_fases');
    }
};
