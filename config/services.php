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
        'token' => env('POSTMARK_TOKEN'),
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

    'cashfree' => [
        'app_id' => env('CASHFREE_APP_ID'),
        'secret_key' => env('CASHFREE_SECRET_KEY'),
        'env' => env('CASHFREE_ENV', 'sandbox'),
        'api_version' => env('CASHFREE_API_VERSION', '2025-01-01'),
    ],

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],

    'aisensy' => [
        'enabled' => env('AISENSY_ENABLED', true),
        'uat_otp_enabled' => env('AISENSY_UAT_OTP_ENABLED', false),
        'api_key' => env('AISENSY_API_KEY'),
        'endpoint' => env('AISENSY_ENDPOINT', 'https://backend.aisensy.com/campaign/t1/api/v2'),
        'order_paid_campaign' => env('AISENSY_ORDER_PAID_CAMPAIGN'),
        'otp_campaign' => env('AISENSY_OTP_CAMPAIGN'),
        'webhook_secret' => env('AISENSY_WEBHOOK_SECRET'),
    ],

    'shiprocket' => [
        'email' => env('SHIPROCKET_EMAIL'),
        'password' => env('SHIPROCKET_PASSWORD'),
        'base_url' => env('SHIPROCKET_BASE_URL', 'https://apiv2.shiprocket.in'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google OAuth (Socialite)
    | Credentials are stored in the DB via Setting model.
    | Falls back to .env values if DB is unavailable.
    |--------------------------------------------------------------------------
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

];
