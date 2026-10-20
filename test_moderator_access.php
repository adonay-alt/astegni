<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$consoleKernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$consoleKernel->bootstrap();

// Test both databases or default to asteghi_admin_dev if specified
$targetDb = isset($argv[1]) ? $argv[1] : 'asteghi_admin_dev';
echo "Testing against database: {$targetDb}\n";

config(['database.connections.mysql.database' => $targetDb]);
Illuminate\Support\Facades\DB::purge('mysql');
Illuminate\Support\Facades\DB::reconnect('mysql');

$moderator = App\Models\User::where('role', 'tutor_moderator')
    ->orWhere('email', 'moderator@asteghi.com')
    ->first();

if (!$moderator) {
    echo "ERROR: Moderator user not found in {$targetDb}!\n";
    exit(1);
}

echo "Found Moderator: ID={$moderator->id}, Name={$moderator->name}, Role={$moderator->role}, IsAdmin={$moderator->is_admin}\n";

$token = $moderator->createToken('test-moderator-token')->plainTextToken;
echo "Generated Token: " . substr($token, 0, 15) . "...\n";

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/api/admin/tutors', 'GET', [], [], [], [
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
]);

$response = $httpKernel->handle($request);

echo "HTTP Status Code: " . $response->getStatusCode() . "\n";
$content = json_decode($response->getContent(), true);

echo "Response Status: " . ($content['status'] ?? 'N/A') . "\n";
echo "Response Counts: " . json_encode($content['counts'] ?? $content['stats'] ?? []) . "\n";
$dataCount = is_array($content['data'] ?? null) ? count($content['data']) : 0;
echo "Applicants count: " . $dataCount . "\n";

if ($dataCount > 0) {
    foreach ($content['data'] as $idx => $t) {
        $name = $t['name'] ?? ($t['user']['first_name'] ?? '') . ' ' . ($t['user']['last_name'] ?? '');
        echo "  - Applicant #" . ($idx + 1) . ": " . trim($name) . " (Status: " . ($t['status'] ?? 'unknown') . ")\n";
    }
}

$httpKernel->terminate($request, $response);

if ($response->getStatusCode() === 200 && ($content['status'] ?? '') === 'success') {
    echo "\n=== VERIFICATION TEST PASSED (HTTP 200 OK) ===\n";
    exit(0);
} else {
    echo "\n=== VERIFICATION TEST FAILED ===\n";
    echo $response->getContent() . "\n";
    exit(1);
}
