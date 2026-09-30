<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Web SDK Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi ini digunakan oleh Firebase Web SDK (JavaScript) di
    | frontend dashboard. Firebase Web API key boleh berada di sini
    | karena dikendalikan oleh Firebase Security Rules.
    |
    | Simpan nilai sensitif di .env, bukan langsung di file ini.
    |
    */

    'api_key'            => env('FIREBASE_API_KEY', ''),
    'auth_domain'        => env('FIREBASE_AUTH_DOMAIN', 'smartteam-f5e2f.firebaseapp.com'),
    'database_url'       => env('FIREBASE_DATABASE_URL', 'https://smartteam-f5e2f-default-rtdb.asia-southeast1.firebasedatabase.app'),
    'project_id'         => env('FIREBASE_PROJECT_ID', 'smartteam-f5e2f'),
    'storage_bucket'     => env('FIREBASE_STORAGE_BUCKET', 'smartteam-f5e2f.firebasestorage.app'),
    'messaging_sender_id'=> env('FIREBASE_MESSAGING_SENDER_ID', '364614372910'),
    'app_id'             => env('FIREBASE_APP_ID', '1:364614372910:web:3986a91490b27f985819bf'),
    'measurement_id'     => env('FIREBASE_MEASUREMENT_ID', 'G-CXEXLLDK6X'),

];
