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
        // OAuth App credentials (registradas una vez en api.slack.com/apps para TaskLab)
        'client_id'      => env('SLACK_CLIENT_ID'),
        'client_secret'  => env('SLACK_CLIENT_SECRET'),
        'signing_secret' => env('SLACK_SIGNING_SECRET'), // misma para todos los workspaces
        'redirect'       => env('APP_URL', 'http://localhost') . '/settings/slack/callback',
        // Scopes que pide el bot al instalarse
        'scopes'         => 'chat:write,im:write,channels:history,channels:read,users:read,users:read.email',
    ],

    'teams' => [
        'token' => env('SERVICES_TEAMS_TOKEN'),
    ],

    'openai' => [
        'api_key'      => env('OPENAI_API_KEY'),
        // Modelo por defecto para el refinamiento de tareas. Puedes sobreescribirlo en .env
        'tasklab_model'=> env('OPENAI_TASKLAB_MODEL', 'gpt-4.1-mini'),
    ],

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key'    => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
    ],

    'discord' => [
        'bot_token' => env('DISCORD_BOT_TOKEN'),
    ],

    'github' => [
        'client_id'     => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect'      => env('APP_URL') . '/settings/github/callback',
    ],

];
