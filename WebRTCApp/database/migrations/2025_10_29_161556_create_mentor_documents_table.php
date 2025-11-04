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
        Schema::create('mentor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Archivo (CV del mentor)
            $table->string('file_path');
            
            // Texto extraído del OCR
            $table->longText('extracted_text')->nullable();
            
            // Puntuación de palabras clave (0-100)
            $table->integer('keyword_score')->default(0);
            
            // Estado del documento
            $table->enum('status', ['pending', 'approved', 'rejected', 'invalid'])->default('pending');
            
            // Fecha de procesamiento
            $table->timestamp('processed_at')->nullable();
            
            // Razón de rechazo (si aplica)
            $table->text('rejection_reason')->nullable();
            
            // Visibilidad pública del CV
            $table->boolean('is_public')->default(true);
            
            $table->timestamps();
            $table->softDeletes(); // Permite reenvío de documento
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentor_documents');
    }
};
