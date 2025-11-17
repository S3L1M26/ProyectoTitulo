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
        // Hacer nullable los campos de mentores (sin especialidad que ya fue eliminada)
        Schema::table('mentors', function (Blueprint $table) {
            $table->text('experiencia')->nullable()->change();
            // La columna 'especialidad' ya fue eliminada en migración anterior
            // La disponibilidad ya es nullable por migración anterior
        });

        // Hacer nullable el campo semestre de aprendices
        Schema::table('aprendices', function (Blueprint $table) {
            $table->integer('semestre')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir los cambios
        Schema::table('mentors', function (Blueprint $table) {
            $table->text('experiencia')->nullable(false)->change();
        });

        Schema::table('aprendices', function (Blueprint $table) {
            $table->integer('semestre')->nullable(false)->change();
        });
    }
};
