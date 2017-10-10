<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Yobit authentication
    |--------------------------------------------------------------------------
    |
    | Authentication key and secret for Yobit API.
    |
     */

    'auth' => [
        'key'    => env('YOBIT_KEY', 'B140FB76CED9D340FAE19E484F0F6251'),
        'secret' => env('YOBIT_SECRET', 'f011fec7dea954b2dd9bedb179fd7acd'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Api URLS
    |--------------------------------------------------------------------------
    |
    | Urls for Yobit public, and trading api's
    |
     */

    'urls' => [
        'publicv2'  => 'https://yobit.net/api/2/',
        'publicv3'  => 'https://yobit.net/api/3/',
        'trade' => 'https://yobit.net/tapi/',
    ],

];
