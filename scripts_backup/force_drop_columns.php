<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

echo "Dropping columns from unified_banks...\n";

try {
    Schema::table('unified_banks', function (Blueprint $table) {
        if (Schema::hasColumn('unified_banks', 'xixapay_code')) {
            $table->dropColumn('xixapay_code');
            echo "Dropped xixapay_code.\n";
        } else {
            echo "xixapay_code not found.\n";
        }

        if (Schema::hasColumn('unified_banks', 'monnify_code')) {
            $table->dropColumn('monnify_code');
            echo "Dropped monnify_code.\n";
        } else {
            echo "monnify_code not found.\n";
        }

        if (Schema::hasColumn('unified_banks', 'secondary_provider')) {
            $table->dropColumn('secondary_provider');
            echo "Dropped secondary_provider.\n";
        } else {
            echo "secondary_provider not found.\n";
        }
    });
    echo "Columns dropped successfully.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
