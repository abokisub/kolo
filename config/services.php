<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'xixapay' => [
        'authorization' => 'Bearer 3d47f078e1dc246f65a200104b9cefeae5caf0719b6614cfa072aec60835bfea6f450e1c1568bbbdd2a4b804bf2ac437e9abe7dea8b402c4af9be3ba',
        'api_key' => '5e1a59b5fd64b39065a83ba858c9f3dc00bbaf88',
        'business_id' => 'beaa4543320851673e7d4e3fcb05b34d329535ed',
    ],

];
