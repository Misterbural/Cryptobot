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
            "yobit" => "ETH",
        ),
        "Bitcoin Cash" => array(
            "poloniex" => "BCH",
            "bittrex" => "BCC",
            "yobit" => "BCC",
        ),
        "Litecoin" => array(
            "poloniex" => "LTC",
            "bittrex" => "LTC",
            "yobit" => "LTC",
        ),
        "Dash" => array(
            "poloniex" => "DASH",
            "bittrex" => "DASH",
            "yobit" => "DASH",
        ),
        "NEM" => array(
            "poloniex" => "XEM",
            "bittrex" => "XEM",
            "yobit" => "XEM",
        ),
        "NEO" => array(
            "bittrex" => "NEO",
        ),
        "Ethereum Classic" => array(
            "poloniex" => "ETC",
            "bittrex" => "ETC",
            "yobit" => "ETC",
        ),
        "OmiseGO" => array(
            "poloniex" => "OMG",
            "bittrex" => "OMG",
        ),
        "Lisk" => array(
            "poloniex" => "LSK",
            "bittrex" => "LSK",
            "yobit" => "LSK",
        ),
        "ZCash" => array(
            "poloniex" => "ZEC",
            "bittrex" => "ZEC",
            "yobit" => "ZEC",
        ),
        "Waves" => array(
            "bittrex" => "WAVES",
            "yobit" => "WAVES",
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
            "yobit" => "PIVX",
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
            "yobit" => "DCR",
        ),
        "Factom" => array(
            "poloniex" => "FCT",
            "bittrex" => "FCT",
        ),
        "GameCredits" => array(
            "poloniex" => "GAME",
            "bittrex" => "GAME",
            "yobit" => "GAME",
        ),
        "BitShares" => array(
            "poloniex" => "BTS",
            "bittrex" => "BTS",
            "yobit" => "BTS",
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
            "yobit" => "DGB",
        ),
        "Nexus" => array(
            "bittrex" => "NXS",
        ),
        "Syscoin" => array(
            "poloniex" => "SYS",
            "bittrex" => "SYS",
            "yobit" => "SYS",
        ),
        "Monaco" => array(
            "bittrex" => "MCO",
            "yobit" => "MCO",
        ),
        "Vertcoin" => array(
            "poloniex" => "VTC",
            "bittrex" => "VTC",
            "yobit" => "VTC",
        ),
        "Ubiq" => array(
            "bittrex" => "UBQ",
        ),
        "NAV coin" => array(
            "poloniex" => "NAV",
            "bittrex" => "NAV",
        ),
        "SONM" => array(
            "yobit" => "SNM",
        ),
        "Viacoin" => array(
            "poloniex" => "VIA",
            "bittrex" => "VIA",
            "yobit" => "VIA",
        ),
        "Namecoin" => array(
            "poloniex" => "NMC",
            "yobit" => "NMC",
        ),
        "Humaniq" => array(
            "bittrex" => "HMQ",
            "yobit" => "HMQ",
        ),
        "Emercoin" => array(
            "bittrex" => "EMC",
            "yobit" => "EMC",
        ),
        "EverGreenCoin" => array(
            "bittrex" => "EGC",
            "yobit" => "EGC",
        ),
        "Gulden" => array(
            "bittrex" => "NLG",
            "yobit" => "NLG",
        ),
        "Syndicate" => array(
            "bittrex" => "SYNX",
            "yobit" => "SYNX",
        ),
        "Magi" => array(
            "bittrex" => "XMG",
            "yobit" => "XMG",
        ),
        "Expanse" => array(
            "poloniex" => "EXP",
            "bittrex" => "EXP",
            "yobit" => "EXP",
        ),
        "Rise" => array(
            "bittrex" => "RISE",
            "yobit" => "RISE",
        ),
    ),

];