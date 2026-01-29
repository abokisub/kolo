<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVirtualAccountControlsToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('palmpay_enabled')->default(true)->after('xixapay_charge');
            $table->boolean('monnify_enabled')->default(true)->after('palmpay_enabled');
            $table->boolean('wema_enabled')->default(true)->after('monnify_enabled');
            $table->boolean('xixapay_enabled')->default(true)->after('wema_enabled');
            $table->string('default_virtual_account', 20)->default('palmpay')->after('xixapay_enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'palmpay_enabled',
                'monnify_enabled',
                'wema_enabled',
                'xixapay_enabled',
                'default_virtual_account'
            ]);
        });
    }
}
