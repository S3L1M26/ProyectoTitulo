<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modificar el enum para agregar 'cancelada'
        DB::statement("ALTER TABLE solicitud_mentorias MODIFY COLUMN estado ENUM('pendiente', 'aceptada', 'rechazada', 'cancelada') DEFAULT 'pendiente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir a los estados originales
        DB::statement("ALTER TABLE solicitud_mentorias MODIFY COLUMN estado ENUM('pendiente', 'aceptada', 'rechazada') DEFAULT 'pendiente'");
    }
};
