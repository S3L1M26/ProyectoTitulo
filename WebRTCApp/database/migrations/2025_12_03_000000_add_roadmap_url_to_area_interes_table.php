<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('areas_interes', function (Blueprint $table) {
            $table->string('roadmap_url')->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('areas_interes', function (Blueprint $table) {
            $table->dropColumn('roadmap_url');
        });
    }
};
