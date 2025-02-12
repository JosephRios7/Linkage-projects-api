<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('archivos', function (Blueprint $table) {
            $table->longText('file_data')->nullable(); // Guardará el archivo en Base64
            $table->dropColumn('file_path'); // Eliminamos el almacenamiento de rutas
            $table->string('mime_type'); // Almacena el tipo MIME del archivo
        });
    }

    public function down()
    {
        Schema::table('archivos', function (Blueprint $table) {
            $table->string('file_path')->nullable();
            $table->dropColumn('file_data');
        });
    }
};
