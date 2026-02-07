<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$u = DB::table('user')->where('username', 'developer')->first();
if ($u) {
    echo "USER_INFO_START\n";
    $interesting = [
        'id',
        'username',
        'email',
        'phone',
        'bal',
        'customer_id',
        'kyc_status',
        'customer_data',
        'xixapay_kyc_data',
        'address'
    ];
    $data = [];
    foreach ($interesting as $key) {
        $data[$key] = $u->$key ?? 'UNDEFINED';
    }
    echo json_encode($data, JSON_PRETTY_PRINT);
    echo "\nUSER_INFO_END\n";
} else {
    echo "User not found\n";
}
