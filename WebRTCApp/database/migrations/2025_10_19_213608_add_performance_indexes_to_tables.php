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
        // Índice crítico para filtro de disponibilidad en mentores
        Schema::table('mentors', function (Blueprint $table) {
            $table->index('disponible_ahora', 'idx_mentors_disponible_ahora');
            $table->index('user_id', 'idx_mentors_user_id'); // FK optimization
        });

        // Índice crítico para filtro de roles
        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'idx_users_role');
        });

        // Índices compuestos para tablas pivot de áreas de interés (críticos para matching)
        Schema::table('mentor_area_interes', function (Blueprint $table) {
            $table->index(['mentor_id', 'area_interes_id'], 'idx_mentor_area_composite');
            $table->index('area_interes_id', 'idx_mentor_area_interes_area_id');
        });

        Schema::table('aprendiz_area_interes', function (Blueprint $table) {
            $table->index(['aprendiz_id', 'area_interes_id'], 'idx_aprendiz_area_composite');
            $table->index('area_interes_id', 'idx_aprendiz_area_interes_area_id');
        });

        // Índice para ordenamiento por calificación (para sugerencias ordenadas)
        Schema::table('mentors', function (Blueprint $table) {
            $table->index('calificacionPromedio', 'idx_mentors_calificacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentors', function (Blueprint $table) {
            $table->dropIndex('idx_mentors_disponible_ahora');
            $table->dropIndex('idx_mentors_user_id');
            $table->dropIndex('idx_mentors_calificacion');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
        });

        Schema::table('mentor_area_interes', function (Blueprint $table) {
            $table->dropIndex('idx_mentor_area_composite');
            $table->dropIndex('idx_mentor_area_interes_area_id');
        });

        Schema::table('aprendiz_area_interes', function (Blueprint $table) {
            $table->dropIndex('idx_aprendiz_area_composite');
            $table->dropIndex('idx_aprendiz_area_interes_area_id');
        });
    }
};
