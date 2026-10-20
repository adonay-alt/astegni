<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure grades exist so foreign key assignment is safe
        $gradeMap = [
            'Grade 8'  => 0,
            'Grade 9'  => 1,
            'Grade 10' => 1,
            'Grade 11' => 1,
            'Grade 12' => 2,
        ];

        foreach ($gradeMap as $name => $type) {
            $existing = DB::table('grades')->where('name', $name)->first();
            if (!$existing) {
                $numName = str_replace('Grade ', '', $name);
                $existing = DB::table('grades')->where('name', $numName)->first();
            }

            if ($existing) {
                DB::table('grades')->where('id', $existing->id)->update(['type' => $type]);
            } else {
                DB::table('grades')->insert([
                    'name' => $name,
                    'type' => $type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Build mapping of grade number -> grades.id
        $grades = DB::table('grades')->get();
        $idMap = []; // maps grade number (e.g. 9) to grades.id
        $validGradeIds = [];

        foreach ($grades as $g) {
            preg_match('/\d+/', $g->name, $matches);
            if (!empty($matches)) {
                $num = (int)$matches[0];
                if (in_array($num, [9, 10, 11, 12])) {
                    $idMap[$num] = $g->id;
                    $validGradeIds[] = $g->id;
                }
            }
        }

        // 2. Clean up / map existing users.grade values
        $users = DB::table('users')->whereNotNull('grade')->get();
        foreach ($users as $user) {
            $val = (int)$user->grade;
            if (in_array($val, $validGradeIds)) {
                // Already points to a valid assignable grade ID
                continue;
            } elseif (isset($idMap[$val])) {
                // Stored as grade number (9, 10, 11, 12) -> convert to grades.id
                DB::table('users')->where('id', $user->id)->update(['grade' => $idMap[$val]]);
            } else {
                // Invalid or Grade 8 -> set to null
                DB::table('users')->where('id', $user->id)->update(['grade' => null]);
            }
        }

        // 3. Change column type and add foreign key constraint
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('grade')->nullable()->change();
            $table->foreign('grade')->references('id')->on('grades')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['grade']);
            $table->integer('grade')->nullable()->change();
        });
    }
};
