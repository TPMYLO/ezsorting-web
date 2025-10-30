<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Drive API Configuration
    |--------------------------------------------------------------------------
    */

    'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
    'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
    'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),

    /*
    |--------------------------------------------------------------------------
    | Supported Image Formats
    |--------------------------------------------------------------------------
    */

    'supported_formats' => [
        // Standard formats
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/heic',
        'image/gif',
        'image/webp',

        // RAW formats
        'image/x-sony-arw',    // Sony ARW
        'image/x-canon-cr2',   // Canon CR2
        'image/x-canon-cr3',   // Canon CR3
        'image/x-nikon-nef',   // Nikon NEF
        'image/x-nikon-nrw',   // Nikon NRW
        'image/x-fuji-raf',    // Fujifilm RAF
        'image/x-panasonic-rw2', // Panasonic RW2
        'image/x-olympus-orf', // Olympus ORF
        'image/x-pentax-pef',  // Pentax PEF
        'image/x-adobe-dng',   // Adobe DNG
    ],

    'supported_extensions' => [
        'jpg', 'jpeg', 'png', 'heic', 'gif', 'webp',
        'arw', 'cr2', 'cr3', 'nef', 'nrw', 'raf', 'rw2', 'orf', 'pef', 'dng'
    ],
];
