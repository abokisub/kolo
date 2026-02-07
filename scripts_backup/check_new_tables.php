<?php

use Illuminate\Support\Facades\Schema;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = ['user_kyc', 'virtual_cards', 'card_transactions', 'system_locks'];
$results = [];

foreach ($tables as $table) {
    echo $table . ": " . (Schema::hasTable($table) ? "EXISTS" : "MISSING") . "\n";
}
