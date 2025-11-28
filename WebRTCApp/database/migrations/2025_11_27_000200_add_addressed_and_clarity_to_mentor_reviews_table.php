<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentor_reviews', function (Blueprint $table) {
            $table->string('addressed_interests', 12)->nullable()->after('comment');
            $table->unsignedTinyInteger('interests_clarity')->nullable()->after('addressed_interests');
        });
    }

    public function down(): void
    {
        Schema::table('mentor_reviews', function (Blueprint $table) {
            $table->dropColumn(['addressed_interests', 'interests_clarity']);
        });
    }
};
