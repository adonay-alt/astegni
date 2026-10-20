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
        Schema::dropIfExists('chapter_progress');
        Schema::dropIfExists('user_progress');
        Schema::dropIfExists('progress');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('chapter_progress')) {
            Schema::create('chapter_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
                $table->boolean('completed')->default(true);
                $table->timestamps();
            });
        }
    }
};
