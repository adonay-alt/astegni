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
    Schema::table('national_exam_questions', function (Blueprint $table) {
        $table->string('image_url')->nullable()->after('question'); // <-- Changed this line
    });
  }

  public function down()
   {
    Schema::table('national_exam_questions', function (Blueprint $table) {
        $table->dropColumn('image_url');
    });
  }
};
