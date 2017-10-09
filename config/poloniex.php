<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Poloniex authentication
    |--------------------------------------------------------------------------
    |
    | Authentication key and secret for poloniex API.
    |
    */

    'auth' => [
        'key' => env('POLONIEX_KEY', 'K48OK1U3-CI4AAJ7Y-PKLW1Q5J-U5Z0MX3W'),
        'secret' => env('POLONIEX_SECRET', '544403c1a546b9b1a1397a62730cdf34da21461506f3b48a7a1d8a84386378ac83a1a9614919111b75f588efeabf75bf715f19e341931c0a6dddafc4119dbf6d
'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Api URLS
    |--------------------------------------------------------------------------
    |
    | Urls for Poloniex public and trading API
    |
    */

    'urls' => [
        'trading' => 'https://poloniex.com/tradingApi',
        'public' => 'https://poloniex.com/public',
    ],

];