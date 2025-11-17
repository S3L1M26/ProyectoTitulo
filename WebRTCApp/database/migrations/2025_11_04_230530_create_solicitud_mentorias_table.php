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
        Schema::create('solicitud_mentorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('mentor_id')->constrained('users')->onDelete('cascade');
            $table->text('mensaje')->nullable();
            $table->enum('estado', ['pendiente', 'aceptada', 'rechazada'])->default('pendiente');
            $table->timestamp('fecha_solicitud')->useCurrent();
            $table->timestamp('fecha_respuesta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para optimización de queries
            $table->index('estudiante_id');
            $table->index('mentor_id');
            $table->index('estado');
            $table->index(['mentor_id', 'estado']); // Índice compuesto
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_mentorias');
    }
};
