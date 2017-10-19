<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User Defined Variables
    |--------------------------------------------------------------------------
    |
    | This is a set of variables that are made specific to this application
    | that are better placed here rather than in .env file.
    | Use config('your_key') to get the values.
    |
    */

    'currencies' => array(
        'LTC',
        'XVG',
        'NXS',
        'PTOY',
        'OMG',
        'ETH',
        'ETC',
        'NEO',
        'STRAT',
        'PAY',
        'ZEC',
        'QTUM',
        'SNT',
        'NXC',
        'LSK',
        'XMR',
        'DASH',
        'BAT',
        'MCO',
        'BCC',
        'WAVES',
        'RDD',
        'GAME',
        'ARK',
        'BAY',
        'TRUST',
        'EBST',
        'XRP',
        'ADX',
        'EDG',
        'NAV',
        'SC',
        'PIVX',
        'UBQ',
        'GNT',
        'COVAL'
    ),

    'currencies_arbitration' => array(
        "Ethereum" => array(
            "poloniex" => "ETH",
            "bittrex" => "ETH",
            "bitfinex" => "ETH",
        ),
        "Bitcoin Cash" => array(
            "poloniex" => "BCH",
            "bittrex" => "BCC",
            "bitfinex" => "BCH",
        ),
        "Litecoin" => array(
            "poloniex" => "LTC",
            "bittrex" => "LTC",
            "bitfinex" => "LTC",
        ),
        "Dash" => array(
            "poloniex" => "DASH",
            "bittrex" => "DASH",
            "bitfinex" => "DASH",
        ),
        "NEM" => array(
            "poloniex" => "XEM",
            "bittrex" => "XEM",
        ),
        "NEO" => array(
            "bittrex" => "NEO",
            "bitfinex" => "NEO",
        ),
        "Ethereum Classic" => array(
            "poloniex" => "ETC",
            "bittrex" => "ETC",
            "bitfinex" => "ETC",
        ),
        "OmiseGO" => array(
            "poloniex" => "OMG",
            "bittrex" => "OMG",
            "bitfinex" => "OMG",
        ),
        "Lisk" => array(
            "poloniex" => "LSK",
            "bittrex" => "LSK",
        ),
        "ZCash" => array(
            "poloniex" => "ZEC",
            "bittrex" => "ZEC",
            "bitfinex" => "ZEC",
        ),
        "Waves" => array(
            "bittrex" => "WAVES",
        ),
        "Stratis" => array(
            "poloniex" => "STRAT",
            "bittrex" => "STRAT",
        ),
        "Stellar Lumens" => array(
            "poloniex" => "STR",
            "bittrex" => "XLM",
        ),
        "Ark" => array(
            "bittrex" => "ARK",
        ),
        "Steem" => array(
            "poloniex" => "STEEM",
            "bittrex" => "STEEM",
        ),
        "TenX" => array(
            "bittrex" => "PAY",
        ),
        "Augur" => array(
            "poloniex" => "REP",
            "bittrex" => "REP",
        ),
        "Ardor" => array(
            "poloniex" => "ARDR",
            "bittrex" => "ARDR",
        ),
        "Basic Attention Token" => array(
            "bittrex" => "BAT",
        ),
        "PIVX" => array(
            "bittrex" => "PIVX",
        ),
        "MaidSafeCoin" => array(
            "poloniex" => "MAID",
            "bittrex" => "MAID",
        ),
        "Golem" => array(
            "poloniex" => "GNT",
            "bittrex" => "GNT",
        ),
        "Komodo" => array(
            "bittrex" => "KMD",
        ),
        "Decred" => array(
            "poloniex" => "DCR",
            "bittrex" => "DCR",
        ),
        "Factom" => array(
            "poloniex" => "FCT",
            "bittrex" => "FCT",
        ),
        "GameCredits" => array(
            "poloniex" => "GAME",
            "bittrex" => "GAME",
        ),
        "BitShares" => array(
            "poloniex" => "BTS",
        ),
        "Siacoin" => array(
            "poloniex" => "SC",
            "bittrex" => "SC",
        ),
        "Civic" => array(
            "poloniex" => "CVC",
            "bittrex" => "CVC",
        ),
        "Gnosis" => array(
            "poloniex" => "GNO",
            "bittrex" => "GNO",
        ),
        "BitcoinDark" => array(
            "poloniex" => "BTCD",
            "bittrex" => "BTCD",
        ),
        "DigiByte" => array(
            "poloniex" => "DGB",
            "bittrex" => "DGB",
        ),
        "Nexus" => array(
            "bittrex" => "NXS",
        ),
        "Syscoin" => array(
            "poloniex" => "SYS",
            "bittrex" => "SYS",
        ),
        "Monaco" => array(
            "bittrex" => "MCO",
        ),
        "Vertcoin" => array(
            "poloniex" => "VTC",
            "bittrex" => "VTC",
        ),
        "Ubiq" => array(
            "bittrex" => "UBQ",
        ),
        "NAV coin" => array(
            "poloniex" => "NAV",
            "bittrex" => "NAV",
        ),
        "SONM" => array(
        ),
        "Viacoin" => array(
            "poloniex" => "VIA",
            "bittrex" => "VIA",
        ),
        "Namecoin" => array(
            "poloniex" => "NMC",
        ),
        "Humaniq" => array(
            "bittrex" => "HMQ",
        ),
        "Emercoin" => array(
            "bittrex" => "EMC",
        ),
        "EverGreenCoin" => array(
            "bittrex" => "EGC",
        ),
        "Gulden" => array(
            "bittrex" => "NLG",
        ),
        "Syndicate" => array(
            "bittrex" => "SYNX",
        ),
        "Magi" => array(
            "bittrex" => "XMG",
        ),
        "Expanse" => array(
            "poloniex" => "EXP",
            "bittrex" => "EXP",
        ),
        "Rise" => array(
            "bittrex" => "RISE",
        ),
        "EOS" => array(
            "bitfinex" => "EOS",
        ),
        "IOTA" => array(
            "bitfinex" => "EOS",
        ),
        "Santiment" => array(
            "bitfinex" => "SAN",
        ),
        "Aventus" => array(
            "bitfinex" => "AVT",
        ),
        "Qtum" => array(
            "bittrex" => "QTUM",
            "bitfinex" => "QTM",
        ),
    ),

    'bitfinex' => array(
        "key" => "mxnMqTN7rmaYC19pYUTwtKHftpk3fvbDlMJaaLCWu9d",
        "secret" => "r3G7EVzxjREqeusTWKwPe52LhBhDsGkDvAZmLVlURJR"
    ),

];