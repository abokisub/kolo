<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "Starting Database Cleanup for Paystack-Only Mode...\n";

try {
    // 1. Set Global Setting to Paystack
    DB::table('settings')->update(['primary_transfer_provider' => 'paystack']);
    echo "Global Setting: 'primary_transfer_provider' set to 'paystack'.\n";

    // 2. Reset Unified Banks Routing
    // Set all primary to 'paystack' and clear secondary
    $updated = DB::table('unified_banks')->update([
        'primary_provider' => 'paystack',
        'secondary_provider' => null,
        'updated_at' => now()
    ]);
    echo "Unified Banks: Reset $updated banks to Primary=Paystack, Secondary=NULL.\n";

    // 3. Deactivate banks that don't have a paystack_code (optional, but requested "clean")
    // Only keeping banks that Paystack supports active? 
    // Or just keeping everything active but pointing to Paystack (which might fail if no code).
    // Safer: Ensure banks with no paystack_code are flagged or outputted.

    $noPaystack = DB::table('unified_banks')->whereNull('paystack_code')->orWhere('paystack_code', '')->count();
    echo "Warning: $noPaystack banks have no Paystack Int. Code. Auto-filling with generic code...\n";

    // Auto-fill paystack_code with local code if empty
    DB::statement("UPDATE unified_banks SET paystack_code = code WHERE paystack_code IS NULL OR paystack_code = ''");
    echo "Auto-filling complete.\n";

    echo "Cleanup Complete!\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
