<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentor_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('mentors')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5 (validado en app y opcionalmente por CHECK)
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['mentor_id', 'user_id']);
        });

        // Intentar añadir una CHECK constraint (MySQL 8+ la aplica; versiones previas la ignoran)
        try {
            Schema::getConnection()->statement('ALTER TABLE mentor_reviews ADD CONSTRAINT chk_mentor_reviews_rating CHECK (rating BETWEEN 1 AND 5)');
        } catch (\Throwable $e) {
            // Silenciar si el motor no soporta CHECK o ya existe
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_reviews');
    }
};
