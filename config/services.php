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

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
        ],
    ],

    'ninerouter' => [
        'url'         => env('NINE_ROUTER_URL'),
        'login_path'  => env('NINE_ROUTER_LOGIN_PATH', '/api/auth/login'),
        'login_field' => env('NINE_ROUTER_LOGIN_FIELD', 'password'),
        'password'    => env('NINE_ROUTER_PASSWORD'),
        'stats_path'  => env('STATS_PATH', '/api/usage/stats'),
        'chart_path'  => env('CHART_PATH', '/api/usage/chart'),
    ],

    'blitz' => [
        'mcp_url' => env('BLITZ_MCP_URL', 'https://mcp.blitz.cloud/shendyrn-dev'),
        'api_key' => env('BLITZ_API_KEY'),
        'app_id' => env('BLITZ_APP_ID'),
    ],

    'dashboard' => [
        'user'     => env('DASH_USER', 'admin'),
        'password' => env('DASH_PASSWORD'),
    ],

];
