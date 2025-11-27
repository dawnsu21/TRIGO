<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['*'],

    'allowed_methods' => ['*'],

    // Allow frontend origins - defaults to common dev ports
    'allowed_origins' => env('FRONTEND_ORIGINS') 
        ? array_filter(array_map('trim', explode(',', env('FRONTEND_ORIGINS'))))
        : ['http://localhost:5173', 'http://localhost:3000', 'http://127.0.0.1:5173'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization', 'Content-Type', 'X-Requested-With'],

    'max_age' => 86400, // Cache preflight for 24 hours

    'supports_credentials' => true,

];
