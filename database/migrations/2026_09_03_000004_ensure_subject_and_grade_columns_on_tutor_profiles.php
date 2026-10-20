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
        if (Schema::hasTable('tutor_profiles')) {
            Schema::table('tutor_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('tutor_profiles', 'subject')) {
                    $table->string('subject')->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('tutor_profiles', 'grade')) {
                    $table->integer('grade')->nullable()->after('subject');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tutor_profiles')) {
            Schema::table('tutor_profiles', function (Blueprint $table) {
                if (Schema::hasColumn('tutor_profiles', 'grade')) {
                    $table->dropColumn('grade');
                }
                if (Schema::hasColumn('tutor_profiles', 'subject')) {
                    $table->dropColumn('subject');
                }
            });
        }
    }
};
