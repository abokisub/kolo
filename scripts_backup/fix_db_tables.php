<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

try {
    echo "Starting Table Creation...\n";

    if (!Schema::hasTable('transfers')) {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('reference')->unique();
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('charge', 10, 2)->default(0);
            $table->string('bank_code', 50)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('account_name')->nullable();
            $table->string('narration')->nullable();
            $table->string('status', 50)->default('PENDING');
            $table->decimal('oldbal', 10, 2)->nullable();
            $table->decimal('newbal', 10, 2)->nullable();
            $table->string('system', 50)->nullable();
            $table->timestamps();
        });
        echo "Table 'transfers' created successfully.\n";
    } else {
        echo "Table 'transfers' already exists.\n";
    }

    if (!Schema::hasTable('transfer_providers')) {
        Schema::create('transfer_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('priority')->default(0);
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
        });
        echo "Table 'transfer_providers' created successfully.\n";

        // Seed default providers
        DB::table('transfer_providers')->insert([
            ['name' => 'Monnify', 'slug' => 'monnify', 'priority' => 1, 'is_locked' => 0],
            ['name' => 'Paylony', 'slug' => 'paylony', 'priority' => 2, 'is_locked' => 0],
            ['name' => 'Xixapay', 'slug' => 'xixapay', 'priority' => 3, 'is_locked' => 0],
            ['name' => 'Paystack', 'slug' => 'paystack', 'priority' => 4, 'is_locked' => 0],
        ]);
        echo "Seeded default providers.\n";
    } else {
        echo "Table 'transfer_providers' already exists.\n";
    }

    // Add columns to settings table
    Schema::table('settings', function (Blueprint $table) {
        if (!Schema::hasColumn('settings', 'transfer_lock_all')) {
            $table->boolean('transfer_lock_all')->default(0);
        }
        if (!Schema::hasColumn('settings', 'transfer_charge_type')) {
            $table->string('transfer_charge_type')->default('FLAT');
        }
        if (!Schema::hasColumn('settings', 'transfer_charge_value')) {
            $table->decimal('transfer_charge_value', 10, 2)->default(0);
        }
        if (!Schema::hasColumn('settings', 'transfer_charge_cap')) {
            $table->decimal('transfer_charge_cap', 10, 2)->default(0);
        }
    });
    echo "Settings table columns checked/added.\n";

    echo "Finished.\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
