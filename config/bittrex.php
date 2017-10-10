<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Bittrex authentication
    |--------------------------------------------------------------------------
    |
    | Authentication key and secret for bittrex API.
    |
     */

    'auth' => [
        'key'    => env('BITTREX_KEY', 'a0e7e97f3e104bfd9686e6d614939573'),
        'secret' => env('BITTREX_SECRET', '9249d27e71ff4bdcb4cde42d9506d8bc'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Api URLS
    |--------------------------------------------------------------------------
    |
    | Urls for Bittrex public, market and account API
    |
     */

    'urls' => [
        'public'  => 'https://bittrex.com/api/v1.1/public/',
        'publicv2'  => 'https://bittrex.com/Api/v2.0/pub/',
        'market'  => 'https://bittrex.com/api/v1.1/market/',
        'account' => 'https://bittrex.com/api/v1.1/account/',
    ],

];
