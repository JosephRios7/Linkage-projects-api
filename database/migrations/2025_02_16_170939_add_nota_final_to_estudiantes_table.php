<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotaFinalToEstudiantesTable extends Migration
{
    public function up()
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            // Se define como decimal con precisión 4 y escala 2 (por ejemplo: 10.00)
            $table->decimal('nota_final', 4, 2)->nullable()->after('nota_admin');
        });
    }

    public function down()
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn('nota_final');
        });
    }
}
