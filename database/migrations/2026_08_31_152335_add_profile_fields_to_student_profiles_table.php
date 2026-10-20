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
        Schema::table('student_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('student_profiles', 'phone')) {
                $table->string('phone')->nullable();
            }
            $table->string('stream')->nullable()->after('school');       // e.g. Natural/Social science
            $table->string('gender')->nullable()->after('phone');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('city_town')->nullable()->after('date_of_birth');
            $table->string('region')->nullable()->after('city_town');
            $table->string('parent_guardian_contact')->nullable()->after('region');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn(['stream', 'gender', 'date_of_birth', 'city_town', 'region', 'parent_guardian_contact']);
        });
    }
};