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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'mtn_momo' => [
        'base_url' => env('MTN_MOMO_BASE_URL'),
        'collection_primary_key' => env('MTN_MOMO_COLLECTION_PRIMARY_KEY'),
        'collection_secondary_key' => env('MTN_MOMO_COLLECTION_SECONDARY_KEY'),
        'api_user' => env('MTN_MOMO_API_USER'),
        'api_key' => env('MTN_MOMO_API_KEY'),
        'target_environment' => env('MTN_MOMO_TARGET_ENV', 'sandbox'),
        'currency' => env('MTN_MOMO_CURRENCY', 'UGX'),
        'callback_url' => env('MTN_MOMO_CALLBACK_URL'),
    ],

    'airtel_money' => [
        'base_url' => env('AIRTEL_MONEY_BASE_URL'),
        'client_id' => env('AIRTEL_MONEY_CLIENT_ID'),
        'client_secret' => env('AIRTEL_MONEY_CLIENT_SECRET'),
        'merchant_msisdn' => env('AIRTEL_MONEY_MERCHANT_MSISDN'),
        'currency' => env('AIRTEL_MONEY_CURRENCY', 'UGX'),
        'callback_url' => env('AIRTEL_MONEY_CALLBACK_URL'),
    ],

];
