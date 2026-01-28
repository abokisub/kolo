<?php

use App\Http\Controllers\API\PaymentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Catch-all route for React app - MUST exclude API routes
Route::get('/{any}', function () {
    return view('index'); // This will load the React app
})->where('any', '^(?!app|api|system|secure|user|card-transactions|vdf_auto_fund_habukhan).*$');
Route::any('vdf_auto_fund_habukhan', [PaymentController::class, 'VDFWEBHOOK']);

Route::get('/cache', function () {
    return
        Cache::flush();
});
