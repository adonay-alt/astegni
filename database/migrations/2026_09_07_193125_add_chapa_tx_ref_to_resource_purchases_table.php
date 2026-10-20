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
    Schema::table('resource_purchases', function (Blueprint $table) {
        $table->string('chapa_tx_ref')->nullable()->after('tx_ref');
    });
}

public function down()
{
    Schema::table('resource_purchases', function (Blueprint $table) {
        $table->dropColumn('chapa_tx_ref');
    });
}
};
