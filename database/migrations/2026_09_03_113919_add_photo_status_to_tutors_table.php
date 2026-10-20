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
        // Adds 'photo_status' right after the 'image' column
        $table->string('photo_status')->default('pending')->after('image'); 
    });
  }

  public function down()
  {
     Schema::table('tutor_profiles', function (Blueprint $table) {
        $table->dropColumn('photo_status');
    });
 }
};
