<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- Current Configuration ---" . PHP_EOL;

$primary = DB::table('settings')->value('primary_transfer_provider');
echo "Primary Provider: " . $primary . PHP_EOL;

$providers = DB::table('transfer_providers')->get();
foreach ($providers as $p) {
    echo "Provider [{$p->slug}]: " . ($p->is_locked ? 'LOCKED (1)' : 'ACTIVE (0)') . PHP_EOL;
}
echo "-----------------------------" . PHP_EOL;
