## A propos de CryptoBot

CryptoBot est un un projet développé sur Laravel qui permet de développer des stratégies automatisé de trading de cryptomonnaires. Il est en l'état possible de trader sur les exchanges Bittrex, Poloniex et Bitfinex mais il est très facile d'ajouter des plateforme d'exchanges de cryptomonnaies dans le dossier app/Brokers/ un wapper d'API qui étend l'interface InterfaceBroker.php

La liste des cyptos sur lesquelles travailler se trouve dans le fichier config/constants.php. Dans ce fichier vous devez renseigner votre cle api bitfinex. Pour les clé API de bittrex et poloniex, Cela se trouve dans les fichier du nom de l'exchange dans ce même dossier config.

Avant de commencer à mettre en place des stragies, vous devez effectuer les migrations de db et éxecuter le script console (système commande de Laravel) CollectMarketData.php qui va récupérer les candles sur Bittrex mais que vous pouvez modifier pour les récupérer sur l'éxchange de votre choix. 

Pour développer des stratégies, cela se trouve dans app/Console/Commands/ et cela utilise donc le système de commande Laraval.

Bon trading :)
