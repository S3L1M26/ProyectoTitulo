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
        Schema::table('mentors', function (Blueprint $table) {
            // 1. Cambiar experiencia de varchar(255) a text
            $table->text('experiencia')->change();
            
            // 2. Agregar campo biografia (text)
            $table->text('biografia')->nullable()->after('experiencia');
            
            // 3. Agregar campo años_experiencia (integer)
            $table->integer('años_experiencia')->nullable()->after('biografia');
            
            // 4. Agregar campo disponibilidad_detalle (text)
            $table->text('disponibilidad_detalle')->nullable()->after('disponibilidad');
            
            // Nota: El campo especialidad se eliminará después de crear la relación many-to-many
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentors', function (Blueprint $table) {
            $table->string('experiencia', 255)->change();
            $table->dropColumn(['biografia', 'años_experiencia', 'disponibilidad_detalle']);
        });
    }
};