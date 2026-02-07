<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    if (!Schema::hasColumn('settings', 'playstore_url')) {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('playstore_url')->nullable();
        });
        echo "Added playstore_url to settings table.\n";
    }

    if (!Schema::hasColumn('settings', 'appstore_url')) {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('appstore_url')->nullable();
        });
        echo "Added appstore_url to settings table.\n";
    }

    if (!Schema::hasColumn('settings', 'app_update_title')) {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('app_update_title')->nullable();
        });
        echo "Added app_update_title to settings table.\n";
    }

    if (!Schema::hasColumn('settings', 'app_update_desc')) {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('app_update_desc')->nullable();
        });
        echo "Added app_update_desc to settings table.\n";
    }

    if (!Schema::hasColumn('settings', 'card_ngn_lock')) {
        Schema::table('settings', function (Blueprint $table) {
            $table->integer('card_ngn_lock')->default(0);
        });
        echo "Added card_ngn_lock to settings table.\n";
    }

    if (!Schema::hasColumn('settings', 'card_usd_lock')) {
        Schema::table('settings', function (Blueprint $table) {
            $table->integer('card_usd_lock')->default(0);
        });
        echo "Added card_usd_lock to settings table.\n";
    }

    if (!Schema::hasColumn('notif', 'image_url')) {
        Schema::table('notif', function (Blueprint $table) {
            $table->text('image_url')->nullable();
        });
        echo "Added image_url to notif table.\n";
    }

    echo "Database schema updates completed successfully.\n";
} catch (\Exception $e) {
    echo "Error updating database schema: " . $e->getMessage() . "\n";
}
