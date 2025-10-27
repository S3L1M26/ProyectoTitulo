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
        Schema::table('aprendices', function (Blueprint $table) {
            $table->text('objetivos')->nullable()->after('intereses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aprendices', function (Blueprint $table) {
            $table->dropColumn('objetivos');
        });
    }
};
