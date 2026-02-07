<?php
include 'vendor/autoload.php';
$app = include 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\API\TransactionCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

try {
    $token = '970623T86016067'; // Habukhan
    $request = Request::create('/api/user/calculator/' . $token . '/habukhan/secure', 'POST', [
        'status' => 'TODAY'
    ]);

    $request->headers->set('Authorization', 'Bearer ' . $token);

    $controller = new TransactionCalculator();

    echo "Calling TransactionCalculator@User (TODAY)...\n";
    $response = $controller->User($request, $token);

    echo "Response Status: " . $response->getStatusCode() . "\n";
    $data = json_decode($response->getContent(), true);
    if (isset($data['status'])) {
        echo "Response Data Status: " . $data['status'] . "\n";
        if ($data['status'] == 'success') {
            echo "Categories Count: " . count($data['categories']) . "\n";
            foreach ($data['categories'] as $cat) {
                echo " - " . $cat['name'] . ": " . $cat['amount'] . "\n";
            }
        }
        else {
            echo "Error Message: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    }
    else {
        echo "Response Content: " . $response->getContent() . "\n";
    }

}
catch (\Throwable $e) {
    echo "ERROR CAUGHT: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . " LINE: " . $e->getLine() . "\n";
}