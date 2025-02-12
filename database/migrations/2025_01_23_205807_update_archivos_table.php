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
        Schema::table('archivos', function (Blueprint $table) {
            $table->dropForeign(['convocatoria_id']); // Eliminar clave foránea
            $table->dropColumn('convocatoria_id'); // Eliminar columna convocatoria_id
            $table->string('tipo'); // Añadir columna tipo
            $table->unsignedBigInteger('entidad_id'); // Añadir columna entidad_id
            $table->index(['tipo', 'entidad_id']); // Añadir índice
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('archivos', function (Blueprint $table) {
            $table->dropIndex(['tipo', 'entidad_id']); // Eliminar índice
            $table->dropColumn('tipo'); // Eliminar columna tipo
            $table->dropColumn('entidad_id'); // Eliminar columna entidad_id
            $table->unsignedBigInteger('convocatoria_id')->nullable();
            $table->foreign('convocatoria_id')->references('id')->on('convocatorias')->onDelete('cascade');
        });
    }
};
