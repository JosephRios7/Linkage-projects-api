<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->enum('estado', ['Borrador', 'Publicado', 'Finalizado'])->default('Borrador')->change();
        });
    }

    public function down(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->enum('estado', ['borrador', 'publicado'])->default('borrador')->change();
        });
    }
};
