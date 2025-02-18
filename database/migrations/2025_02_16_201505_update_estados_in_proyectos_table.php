<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateEstadosInProyectosTable extends Migration
{
    public function up()
    {
        // Agregar 'finalizado' al enum de 'estado'
        DB::statement("ALTER TABLE proyectos MODIFY estado ENUM('enviado','en_revision','correcciones','aprobado','finalizado') DEFAULT 'enviado'");

        // Cambiar el valor 'finalizado' por 'finalizada' en 'estado_fase'
        DB::statement("ALTER TABLE proyectos MODIFY estado_fase ENUM('subida','evaluacion','aprobado','finalizada','pendiente') DEFAULT 'subida'");
    }

    public function down()
    {
        // Revertir cambios en 'estado'
        DB::statement("ALTER TABLE proyectos MODIFY estado ENUM('enviado','en_revision','correcciones','aprobado') DEFAULT 'enviado'");

        // Revertir cambios en 'estado_fase'
        DB::statement("ALTER TABLE proyectos MODIFY estado_fase ENUM('subida','evaluacion','aprobado','finalizado','pendiente') DEFAULT 'subida'");
    }
}
