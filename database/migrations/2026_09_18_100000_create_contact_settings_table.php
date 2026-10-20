<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('contact_settings')) {
            Schema::create('contact_settings', function (Blueprint $table) {
                $table->id();
                $table->string('email')->default('support@asteghi.com');
                $table->string('phone')->default('+251 911 000 000');
                $table->string('secondary_phone')->nullable();
                $table->timestamps();
            });

            // Seed initial default record
            DB::table('contact_settings')->insert([
                'email' => 'support@asteghi.com',
                'phone' => '+251 911 000 000',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_settings');
    }
};
