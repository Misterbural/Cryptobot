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
    
    'brokers' => array(
        'bittrex',
        'poloniex',
        'bitfinex',
    ),

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
        "Ethereum" => array( // ~ 20 secondes
            "poloniex" => "ETH",
            "bittrex" => "ETH",
            "bitfinex" => "ETH",
        ),
        "Bitcoin Cash" => array( // ~ 5 minutes
            "poloniex" => "BCH",
            "bittrex" => "BCC",
            "bitfinex" => "BCH",
        ),
        "Litecoin" => array( // ~ 4 minutes
            "poloniex" => "LTC",
            "bittrex" => "LTC",
            "bitfinex" => "LTC",
        ),
        "Dash" => array( // ~ 5 minutes (très irrégulier)
            "poloniex" => "DASH",
            "bittrex" => "DASH",
            "bitfinex" => "DASH",
        ),
        "NEM" => array( // ~ moins d'une minute
            "poloniex" => "XEM",
            "bittrex" => "XEM",
        ),
        "NEO" => array( // ~ moins d'une minute
            "bittrex" => "NEO",
            "bitfinex" => "NEO",
        ),
        "Ethereum Classic" => array( // ~ 20 secondes
            "poloniex" => "ETC",
            "bittrex" => "ETC",
            "bitfinex" => "ETC",
        ),
        "OmiseGO" => array( // ~ 20 secondes
            "poloniex" => "OMG",
            "bittrex" => "OMG",
            "bitfinex" => "OMG",
        ),
        "Lisk" => array( // = 10 secondes
            "poloniex" => "LSK",
            "bittrex" => "LSK",
        ),
        "ZCash" => array( // ~ 3 minutes (très irrégulier)
            "poloniex" => "ZEC",
            "bittrex" => "ZEC",
            "bitfinex" => "ZEC",
        ),
        "Waves" => array( // moins d'une minute
            "bittrex" => "WAVES",
        ),
        "Stratis" => array( // ~ 1 minute
            "poloniex" => "STRAT",
            "bittrex" => "STRAT",
        ),
        "Stellar Lumens" => array( // ~ 10 secondes
           // "poloniex" => "STR",
            "bittrex" => "XLM",
        ),
        "Ark" => array( // ~ 10 secondes
            "bittrex" => "ARK",
        ),
        "Steem" => array( // ~ 10 secondes
            "poloniex" => "STEEM",
            "bittrex" => "STEEM",
        ),
        "TenX" => array( // ~ 20 secondes
            "bittrex" => "PAY",
        ),
        "Augur" => array( // ~ 20 secondes
            "poloniex" => "REP",
            "bittrex" => "REP",
        ),
        "Ardor" => array( // ~ 1/2 minutess
            "poloniex" => "ARDR",
            "bittrex" => "ARDR",
        ),
        "Basic Attention Token" => array( // ~ 20 secondes
            "bittrex" => "BAT",
        ),
        "PIVX" => array( // ??
            "bittrex" => "PIVX",
        ),
        "MaidSafeCoin" => array( // ??
            "poloniex" => "MAID",
            "bittrex" => "MAID",
        ),
        "Golem" => array( // ~ 20 secondes
            "poloniex" => "GNT",
            "bittrex" => "GNT",
        ),
        "Komodo" => array( // ~ 1 minute
            "bittrex" => "KMD",
        ),
        "Decred" => array( // ~ 10 minutes
            "poloniex" => "DCR",
            "bittrex" => "DCR",
        ),
        "Factom" => array( // = 10 minutes
            "poloniex" => "FCT",
            "bittrex" => "FCT",
        ),
        "GameCredits" => array( // très très irrégulier
            "poloniex" => "GAME",
            "bittrex" => "GAME",
        ),
        "BitShares" => array( // ??
            "poloniex" => "BTS",
        ),
        "Siacoin" => array( // ??
            "poloniex" => "SC",
            "bittrex" => "SC",
        ),
        "Civic" => array( // ~ 20 secondes
            "poloniex" => "CVC",
            "bittrex" => "CVC",
        ),
        "Gnosis" => array( // ~ 20 secondes
            "poloniex" => "GNO",
            "bittrex" => "GNO",
        ),
        "BitcoinDark" => array( // ??
            "poloniex" => "BTCD",
            "bittrex" => "BTCD",
        ),
        "DigiByte" => array( // moins d'une minute
            "poloniex" => "DGB",
            "bittrex" => "DGB",
        ),
        "Nexus" => array( // moins d'une minute
            "bittrex" => "NXS",
        ),
        "Syscoin" => array( // ~ 1 minute
            "poloniex" => "SYS",
            "bittrex" => "SYS",
        ),
        "Monaco" => array( // ~ 20 secondes
            "bittrex" => "MCO",
        ),
        "Vertcoin" => array( // très très irrégulier
            "poloniex" => "VTC",
            "bittrex" => "VTC",
        ),
        "Ubiq" => array( // ??
            "bittrex" => "UBQ",
        ),
        "NAV coin" => array( // ~ 30 secondes
            "poloniex" => "NAV",
            "bittrex" => "NAV",
        ),
        "SONM" => array( // ~ 20 secondes
        ),
        "Viacoin" => array( // ~ 10 secondes
            "poloniex" => "VIA",
            "bittrex" => "VIA",
        ),
        "Namecoin" => array( // ~ 10 minutes
            "poloniex" => "NMC",
        ),
        "Humaniq" => array( // ~ 20 secondes
            "bittrex" => "HMQ",
        ),
        "Emercoin" => array( // ~ 4 minutes (très irrégulier)
            "bittrex" => "EMC",
        ),
        "EverGreenCoin" => array( // ??
            "bittrex" => "EGC",
        ),
        "Gulden" => array( // irrégulier
            "bittrex" => "NLG",
        ),
        "Syndicate" => array( // ??
            "bittrex" => "SYNX",
        ),
        "Magi" => array( // moins d'une minutes (des fois irrégulier)
            "bittrex" => "XMG",
        ),
        "Expanse" => array( // moins d'une minutes (des fois irrégulier)
            "poloniex" => "EXP",
            "bittrex" => "EXP",
        ),
        "Rise" => array( // = 30 secondes
            "bittrex" => "RISE",
        ),
        "EOS" => array( // ~ 20 secondes
            "bitfinex" => "EOS",
        ),
        "Santiment" => array( // ~ 20 secondes
            "bitfinex" => "SAN",
        ),
        "Aventus" => array( // ~ 20 secondes
            "bitfinex" => "AVT",
        ),
        "Qtum" => array( // irrégulier
            "bittrex" => "QTUM",
            "bitfinex" => "QTM",
        ),
        "Verge" => array(
            "bittrex" => "XVG",
        ),
        "Patientory" => array(
            "bittrex" => "PTOY",
        ),
        "Status" => array(
            "bittrex" => "SNT",
        ),
        "Nexium" => array(
            "bittrex" => "NXC",
        ),
        "Monero" => array(
            "bittrex" => "XMR",
        ),
        "Red coin" => array(
            "bittrex" => "RDD",
        ),
        "Verge" => array(
            "bittrex" => "XVG",
        ),
        "Bitbay" => array(
            "bittrex" => "BAY",
        ),
        "TRUST" => array(
            "bittrex" => "TRUST",
        ),
        "Eboost" => array(
            "bittrex" => "EBST",
        ),
        "Ripple" => array(
            "bittrex" => "XRP",
        ),
        "Edgeless" => array(
            "bittrex" => "EDG",
        ),
        "Coval" => array(
            "bittrex" => "COVAL",
        ),

    ),

    'bitfinex' => array(
        "key" => "mxnMqTN7rmaYC19pYUTwtKHftpk3fvbDlMJaaLCWu9d",
        "secret" => "r3G7EVzxjREqeusTWKwPe52LhBhDsGkDvAZmLVlURJR"
    ),

];
