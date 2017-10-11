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
        'key' => env('POLONIEX_KEY', 'OOAI67JZ-F6WG5YUM-C2AKEN2Y-9XOKY79D'),
        'secret' => env('POLONIEX_SECRET', '4e84156ae23a7dd90df1c0a0de123fb6fe9d86c1122278c4249914abb0d5645494d8f173bd184136bf30b5ffeac073cc9d0eba986cc5c35f7ffa5edad91cb7d1'),
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