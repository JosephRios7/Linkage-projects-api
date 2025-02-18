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
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->decimal('nota_docente', 5, 2)->nullable();
            $table->decimal('nota_admin', 5, 2)->nullable();
            $table->enum('estado', ['activo', 'reprobado', 'aprobado'])->default('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn('nota_docente');
            $table->dropColumn('nota_admin');
            $table->dropColumn('estado');
        });
    }
};
