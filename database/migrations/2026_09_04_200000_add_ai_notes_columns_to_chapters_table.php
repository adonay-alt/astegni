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
        Schema::table('chapters', function (Blueprint $table) {
            if (!Schema::hasColumn('chapters', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('description');
            }
            if (!Schema::hasColumn('chapters', 'ai_generated_notes')) {
                $table->longText('ai_generated_notes')->nullable()->after('pdf_path');
            }
            if (!Schema::hasColumn('chapters', 'notes_generated_at')) {
                $table->timestamp('notes_generated_at')->nullable()->after('ai_generated_notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('chapters', 'notes_generated_at')) {
                $columnsToDrop[] = 'notes_generated_at';
            }
            if (Schema::hasColumn('chapters', 'ai_generated_notes')) {
                $columnsToDrop[] = 'ai_generated_notes';
            }
            if (Schema::hasColumn('chapters', 'pdf_path')) {
                $columnsToDrop[] = 'pdf_path';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
