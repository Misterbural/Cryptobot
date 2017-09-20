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
        'key'    => env('BITTREX_KEY', 'd60709a834c84ea0a98f40ee392904ef'),
        'secret' => env('BITTREX_SECRET', 'c8fa661c4e2d4ed0b91594649161eeeb'),
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
