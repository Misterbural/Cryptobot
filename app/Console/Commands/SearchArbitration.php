<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessTransaction;
use Illuminate\Support\Facades\DB;
use App\Jobs\Arbitration;
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
        $poloniex_transaction = new BusinessTransaction('poloniex' ,'arbitration');
        $bitfinex_transaction = new BusinessTransaction('bitfinex' ,'arbitration');

        while (true) {
            
            //Pour chaque monnaies
            foreach ($this->currencies as $currency => $code_broker) {
        
                if (count($code_broker) < 2) {
                    continue;
                }

                //verifier qu'il n'y a pas déjà dans la queue ou un worker en cours pour une transaction pour cette monnaie

                $ask = [];
                $bid = [];

                //on va chercher les prix d'achats ventes sur chaque broker
                while (true) {
                    try {
                        if (array_key_exists('bittrex', $code_broker)) {
                            $ask['bittrex'] = (float)$bittrex_transaction->get_market_ask_rate('BTC-' . $code_broker['bittrex']);
                            $bid['bittrex'] = (float)$bittrex_transaction->get_market_bid_rate('BTC-' . $code_broker['bittrex']);
                        }

                        if (array_key_exists('poloniex', $code_broker)) {
                            $ask['poloniex'] = (float)$poloniex_transaction->get_market_ask_rate('BTC-' . $code_broker['poloniex']);
                            $bid['poloniex'] = (float)$poloniex_transaction->get_market_bid_rate('BTC-' . $code_broker['poloniex']);
                        }

                        if (array_key_exists('bitfinex', $code_broker)) {
                            $ask['bitfinex'] = (float)$bitfinex_transaction->get_market_ask_rate('BTC-' . $code_broker['bitfinex']);
                            $bid['bitfinex'] = (float)$bitfinex_transaction->get_market_bid_rate('BTC-' . $code_broker['bitfinex']);
                        }
                    } catch (\Exception $e) {
                        sleep(1);
                        continue;
                    }
                    break;
                    
                }

                //On cherche le marché sur lequel on peut acheter le moins cher (min ask) et le marché sur lequel on peut vendre le plus cher (max bid)
                $broker_buy = array_keys($ask, min($ask))[0];
                $price_buy = $ask[$broker_buy];
                
                $broker_sell = array_keys($bid, max($bid))[0];
                $price_sell = $bid[$broker_sell];
                
                if (!$price_buy) {
                    continue;
                }
                $profit = ($price_sell - $price_buy) / $price_buy * 100;
                Log::info("profit : " . round($profit, 2) . "% for " . $code_broker[$broker_buy] . " on " . $broker_buy . " and sell on " . $broker_sell);


                if ($profit > 1) {
                    Log::info("arbitrage found");
                    Arbitration::dispatch($code_broker[$broker_buy], $broker_buy, $code_broker[$broker_sell], $broker_sell);
                }
                sleep(1);
            }
        }
    }
}
