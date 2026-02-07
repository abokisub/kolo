<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumnListing('habukhan_key');
file_put_contents('habukhan_key_columns.json', json_encode($columns, JSON_PRETTY_PRINT));
echo "Dumped " . count($columns) . " columns to habukhan_key_columns.json\n";
