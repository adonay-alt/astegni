<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (['asteghcx_apparent2', 'asteghi_admin_dev'] as $db) {
    $count = Illuminate\Support\Facades\DB::select("SELECT count(*) as c FROM information_schema.tables WHERE TABLE_SCHEMA = '$db'")[0]->c;
    echo "DB $db has $count tables\n";
}
