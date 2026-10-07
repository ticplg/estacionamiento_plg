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

    'facturacion_code100' => [
        'url' => env('FACTURACION_CODE100_API_URL'),
        'key' => env('FACTURACION_CODE100_API_KEY'),
    ],

    'consulta_ruc' => [
        'url' => env('CONSULTA_RUC_API_URL', 'https://api.consulta-ruc.com.py/api/v1'),
        'email' => env('CONSULTA_RUC_EMAIL'),
        'password' => env('CONSULTA_RUC_PASSWORD'),
    ],

];
