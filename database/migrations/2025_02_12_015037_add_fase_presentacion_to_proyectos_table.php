<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddfasePresentacionToProyectosTable extends Migration
{
    public function up()
    {
        Schema::table('proyectos', function (Blueprint $table) {
            if (!Schema::hasColumn('proyectos', 'fasePresentacion')) {
                $table->string('fasePresentacion')->nullable();
            }
        });
    }


    public function down()
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn('fasePresentacion');
        });
    }
}
