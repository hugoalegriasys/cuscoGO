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

    /*
     |--------------------------------------------------------------------------
     | Sensor IoT de aforo (CUS-06)
     |--------------------------------------------------------------------------
     | base_url se usa con Http::baseUrl(), por lo que el cliente solo debe
     | indicar la ruta relativa /sensores/{id}/aforo. El valor por defecto
     | apunta al entorno local de pruebas; en producción se sobrescribe con
     | IOT_SENSOR_BASE_URL sin tocar código.
     */
    'iot_sensor' => [
        'base_url' => env('IOT_SENSOR_BASE_URL', 'https://iot.cuscogo.local/api'),
    ],

];
