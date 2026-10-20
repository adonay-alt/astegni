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
        \Illuminate\Support\Facades\DB::statement('DROP VIEW IF EXISTS subject');

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('stream_id')->nullable()->after('grade_id')->constrained('streams')->nullOnDelete();
        });

        \Illuminate\Support\Facades\DB::statement('CREATE VIEW IF NOT EXISTS subject AS SELECT * FROM subjects');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement('DROP VIEW IF EXISTS subject');

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['stream_id']);
            $table->dropColumn('stream_id');
        });

        \Illuminate\Support\Facades\DB::statement('CREATE VIEW IF NOT EXISTS subject AS SELECT * FROM subjects');
    }
};
