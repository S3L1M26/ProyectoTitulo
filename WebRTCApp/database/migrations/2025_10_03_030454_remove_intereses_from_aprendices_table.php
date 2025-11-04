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
            $table->dropColumn('intereses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aprendices', function (Blueprint $table) {
            $table->json('intereses')->nullable()->after('semestre');
        });
    }
};
