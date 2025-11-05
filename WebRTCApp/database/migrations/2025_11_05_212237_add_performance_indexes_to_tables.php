<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Añade índices para optimizar queries de notificaciones, mentores y áreas de interés
     */
    public function up(): void
    {
        // Índices para tabla notifications (sistema de notificaciones de Laravel)
        Schema::table('notifications', function (Blueprint $table) {
            // Índice compuesto para búsquedas de notificaciones no leídas por usuario y tipo
            $table->index(['notifiable_id', 'notifiable_type', 'read_at'], 'idx_notifications_user_read');
            // Índice para ordenamiento por fecha
            $table->index('created_at');
        });

        // Índices para tabla mentors (optimizar búsqueda de mentores disponibles)
        Schema::table('mentors', function (Blueprint $table) {
            // Índice compuesto para filtros comunes en dashboard de estudiante
            $table->index(['disponible_ahora', 'cv_verified'], 'idx_mentors_available');
            // Índice para ordenamiento por calificación
            $table->index('calificacionPromedio');
        });

        // Índices para tabla mentor_area_interes (join frecuente para sugerencias)
        Schema::table('mentor_area_interes', function (Blueprint $table) {
            // Índice compuesto para joins eficientes
            $table->index(['area_interes_id', 'mentor_id'], 'idx_mentor_areas_lookup');
        });

        // Índices para tabla aprendiz_area_interes (join frecuente)
        Schema::table('aprendiz_area_interes', function (Blueprint $table) {
            // Índice compuesto para joins eficientes
            $table->index(['area_interes_id', 'aprendiz_id'], 'idx_aprendiz_areas_lookup');
        });

        // Índices adicionales para users (filtros por role)
        Schema::table('users', function (Blueprint $table) {
            // Índice para filtros por role (ya puede existir, se añade solo si no existe)
            if (!Schema::hasIndex('users', ['role'])) {
                $table->index('role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user_read');
            $table->dropIndex(['created_at']);
        });

        Schema::table('mentors', function (Blueprint $table) {
            $table->dropIndex('idx_mentors_available');
            $table->dropIndex(['calificacionPromedio']);
        });

        Schema::table('mentor_area_interes', function (Blueprint $table) {
            $table->dropIndex('idx_mentor_areas_lookup');
        });

        Schema::table('aprendiz_area_interes', function (Blueprint $table) {
            $table->dropIndex('idx_aprendiz_areas_lookup');
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasIndex('users', ['role'])) {
                $table->dropIndex(['role']);
            }
        });
    }
};
