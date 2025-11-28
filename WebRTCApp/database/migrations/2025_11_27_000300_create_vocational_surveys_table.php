<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vocational_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('clarity_interest'); // 1-5
            $table->unsignedTinyInteger('confidence_area'); // 1-5
            $table->unsignedTinyInteger('platform_usefulness'); // 1-5
            $table->unsignedTinyInteger('mentorship_usefulness'); // 1-5
            $table->string('recent_change_reason', 200)->nullable();
            $table->float('icv'); // Índice de claridad vocacional
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocational_surveys');
    }
};
