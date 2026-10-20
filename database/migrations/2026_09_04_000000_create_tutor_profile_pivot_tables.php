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
        // 1. Remove legacy flat columns from tutor_profiles if they exist
        if (Schema::hasTable('tutor_profiles')) {
            Schema::table('tutor_profiles', function (Blueprint $table) {
                if (Schema::hasColumn('tutor_profiles', 'subject')) {
                    $table->dropColumn('subject');
                }
                if (Schema::hasColumn('tutor_profiles', 'grade')) {
                    $table->dropColumn('grade');
                }
            });
        }

        // 2. Create subject_tutor_profile pivot table
        if (!Schema::hasTable('subject_tutor_profile')) {
            Schema::create('subject_tutor_profile', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tutor_profile_id')->constrained('tutor_profiles')->cascadeOnDelete();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        // 3. Create grade_tutor_profile pivot table
        if (!Schema::hasTable('grade_tutor_profile')) {
            Schema::create('grade_tutor_profile', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tutor_profile_id')->constrained('tutor_profiles')->cascadeOnDelete();
                $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_tutor_profile');
        Schema::dropIfExists('subject_tutor_profile');

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
};
