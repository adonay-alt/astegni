<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('student')->after('is_admin');
            });
        }

        // Ensure users marked as admin have a super_admin role by default if still 'student' or null
        try {
            DB::table('users')
                ->where('is_admin', 1)
                ->where(function ($query) {
                    $query->whereNull('role')
                          ->orWhere('role', 'student');
                })
                ->update(['role' => 'super_admin']);

            // Ensure is_admin = 1 whenever role is an administrative staff role
            DB::table('users')
                ->whereIn('role', ['super_admin', 'academic_editor', 'tutor_moderator', 'support_agent'])
                ->update(['is_admin' => 1]);

            // Ensure standard learners have is_admin = 0 and role = 'student'
            DB::table('users')
                ->where('role', 'student')
                ->update(['is_admin' => 0]);
        } catch (\Throwable $e) {
            // Silently handle if table is empty or driver error
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
