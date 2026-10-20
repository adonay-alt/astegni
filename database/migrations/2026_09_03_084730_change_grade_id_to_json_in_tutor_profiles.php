<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            // Drop the old singular columns
            $table->dropColumn(['grade', 'subject']);
        });

        Schema::table('tutor_profiles', function (Blueprint $table) {
            // Add them back as plural JSON columns so they can hold arrays
            $table->json('grades')->nullable()->after('user_id');
            $table->json('subjects')->nullable()->after('grades');
        });
    }

    public function down()
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->dropColumn(['grades', 'subjects']);
            $table->integer('grade')->nullable();
            $table->string('subject')->nullable();
        });
    }
};
