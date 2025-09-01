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
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'paystack'=>[
        'base_url'=> getenv('PAYSTACK_BASE_URL'),
        'pk'=> getenv('PAYSTACK_PK'),
        'sk'=> getenv('PAYSTACK_SK'),
        'call_back' => getenv('PAYSTACK_CALLBACK'),
        'webhook_secret' => getenv('PAYSTACK_WEBHOOK_SECRET'),
        'timeout' => 60
    ],


    'dojah' => [
        'base_url' => env('DOJAH_BASE_URL', 'https://your-bvn-service-url.com'),
        'app_id' => env('DOJAH_APP_ID'),
        'secret_key' => env('DOJAH_SEC_KEY'),
        'public_key' => env('DOJAH_PUB_KEY'),
    ],

    'eversend' => [
            'base_url' => env('EVERSEND_BASE_URL', 'https://api.eversend.co/v1'),
            'api_key' => env('EVERSEND_API_KEY'),
            'client_id' => env('EVERSEND_CLIENT_ID'),
            'client_secret' => env('EVERSEND_CLIENT_SECRET'),
            'secret_key' => env('EVERSEND_SECRET'),
    ],


    'betting_site' => [
        'secret_key' => env('NC_SECRET_KEY'),
        'pin' => env('NC_PIN'),
        'base_url' =>  env('NC_BASE_URL')
    ],

    'fcm' => [
    'key' => env('FCM_SERVER_KEY'),

        ],

        'nomba' => [
            'base_url' => env('NOMBA_BASE_URL'),
            'client_id' => env('NOMBA_CLIENT_ID'),
            'client_secret' => env('NOMBA_CLIENT_SECRET'),
            'account_id' => env('NOMBA_ACCOUNT_ID'),

        ],

    'redbiller' => [
        'base_url' => env('REDBILLER_BASEURL'),
        'public_key' => env('REDBILLER_PUBLIC_KEY'),
        'call_back' => env('REDBILLER_CALLBACK_URL'),
        'private_key' => env('REDBILLER_PRIVATE_KEY'),
        'auth_hook' => env('REDBILLER_AUTHENTICATION_HOOK'),

    ],

    'strowallet' => [
        'base_url'    => env('STROWALLET_BASE_URL', 'https://strowallet.com/api'),
        'public_key'  => env('STROLLWALLET_PUBLIC_KEY'),
    ],



];
