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
        Schema::create('mentorias', function (Blueprint $table) {
            $table->id();
            
                // Relaciones
                $table->foreignId('solicitud_id')->constrained('solicitud_mentorias')->onDelete('cascade');
                $table->foreignId('aprendiz_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('mentor_id')->constrained('users')->onDelete('cascade');
            
                // Datos de programación
                $table->date('fecha');
                $table->time('hora');
                $table->integer('duracion_minutos')->default(60);
            
                // Datos de Zoom
                $table->string('enlace_reunion', 500)->nullable();
                $table->string('zoom_meeting_id', 100)->nullable();
                $table->string('zoom_password', 50)->nullable();
            
                // Estado y notas
                $table->enum('estado', ['confirmada', 'completada', 'cancelada'])->default('confirmada');
                $table->text('notas_mentor')->nullable();
                $table->text('notas_aprendiz')->nullable();
            
            $table->timestamps();
            
                // Índices para optimizar queries
                $table->index('solicitud_id');
                $table->index('aprendiz_id');
                $table->index('mentor_id');
                $table->index('fecha');
                $table->index(['fecha', 'estado']); // Índice compuesto para filtros comunes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentorias');
    }
};
