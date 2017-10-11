<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessTransaction;
use Illuminate\Support\Facades\DB;
use Log;


class SearchArbitration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:search_arbitration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'search differnces in price between two broker';

    /**
    * List of currencies for trading
    * 
    * @var array
    */
    protected $currencies;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->currencies = config('constants.currencies_arbitration');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $bittrex_transaction = new BusinessTransaction('bittrex' ,'arbitration');
        $yobit_transaction = new BusinessTransaction('yobit' ,'arbitration');
        $poloniex_transaction = new BusinessTransaction('poloniex' ,'arbitration');

        while (true) {

            //Pour chaque monnaies
            foreach ($this->currencies as $currency => $brokers) {
                
                if (count($brokers) < 2) {
                    continue;
                }

                $ask = [];
                $bid = [];

                //on va chercher les prix d'achats ventes sur chaque broker
                if (array_key_exists('bittrex',$brokers)) {
                    $ask['bittrex'] = $bittrex_transaction->get_market_ask_rate('BTC-' . $brokers['bittrex']);
                    $bid['bittrex'] = $bittrex_transaction->get_market_bid_rate('BTC-' . $brokers['bittrex']);
                }

                if (array_key_exists('yobit',$brokers)) {
                    $ask['yobit'] = $yobit_transaction->get_market_ask_rate('BTC-' . $brokers['yobit']);
                    $bid['yobit'] = $yobit_transaction->get_market_bid_rate('BTC-' . $brokers['yobit']);
                }

                if (array_key_exists('poloniex',$brokers)) {
                    $ask['poloniex'] = $poloniex_transaction->get_market_ask_rate('BTC-' . $brokers['poloniex']);
                    $bid['poloniex'] = $poloniex_transaction->get_market_bid_rate('BTC-' . $brokers['poloniex']);
                }

                //On cherche le marché sur lequel on peut acheter le moins cher (min ask) et le marché sur lequel on peut vendre le plus cher (max bid)
                $broker_buy = array_keys($ask, min($ask))[0];
                $price_buy = $ask[$broker_buy];
                
                $broker_sell = array_keys($bid, max($bid))[0];
                $price_sell = $bid[$broker_sell];

                $profit = ($price_sell - $price_buy) / $price_buy * 100;

                //calcul the quantity to buy with order book
                //limit buy all over x% profit but max quantity selling in profit and limited buy bitcoin available

                if ($profit > 0) {
                    echo $currency . " buy on " . $broker_buy . " for " . $price_buy . " sell on " . $broker_sell . " for " . $price_sell .  " Profit : " . $profit . "%\n";
                }
                
            }

            die();

        }
    }
}
