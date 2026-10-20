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
        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'subject')) {
                $table->string('subject')->nullable()->after('email');
            }
            if (!Schema::hasColumn('contacts', 'status')) {
                $table->string('status')->default('unread')->after('message'); // unread, read, replied
            }
            if (!Schema::hasColumn('contacts', 'reply_message')) {
                $table->text('reply_message')->nullable()->after('status');
            }
            if (!Schema::hasColumn('contacts', 'replied_at')) {
                $table->timestamp('replied_at')->nullable()->after('reply_message');
            }
            if (!Schema::hasColumn('contacts', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('replied_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $drop = [];
            foreach (['admin_id', 'replied_at', 'reply_message'] as $col) {
                if (Schema::hasColumn('contacts', $col)) {
                    $drop[] = $col;
                }
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
