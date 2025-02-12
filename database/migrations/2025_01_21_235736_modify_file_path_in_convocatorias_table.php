<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyFilePathInConvocatoriasTable extends Migration
{
    public function up()
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->json('file_path')->nullable()->change(); // Cambia el campo a tipo JSON
        });
    }

    public function down()
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->string('file_path')->nullable()->change(); // Revertir a tipo string si es necesario
        });
    }
}
