<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveFilePathFromConvocatoriasTable extends Migration
{
    public function up()
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->dropColumn('file_path'); // Eliminar el campo
        });
    }

    public function down()
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->string('file_path')->nullable(); // Volver a agregar el campo en caso de rollback
        });
    }
}
