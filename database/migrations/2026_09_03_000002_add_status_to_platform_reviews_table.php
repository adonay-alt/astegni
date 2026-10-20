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
        if (!Schema::hasColumn('platform_reviews', 'status')) {
            Schema::table('platform_reviews', function (Blueprint $table) {
                $table->string('status')->default('pending')->after('comment');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('platform_reviews', 'status')) {
            Schema::table('platform_reviews', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
