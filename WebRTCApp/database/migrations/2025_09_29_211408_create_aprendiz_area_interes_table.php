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
        Schema::create('aprendiz_area_interes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aprendiz_id')->constrained('aprendices')->onDelete('cascade');
            $table->foreignId('area_interes_id')->constrained('areas_interes')->onDelete('cascade');
            $table->timestamps();
            
            // Evitar duplicados
            $table->unique(['aprendiz_id', 'area_interes_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aprendiz_area_interes');
    }
};
