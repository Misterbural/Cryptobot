<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessCandle;
use App\Candle_5m;
use Bittrex;

class CollectMarketData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:collect_market_data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Connect to Bittrex API to collect market datas';

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
        $this->currencies = config('constants.currencies');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $businessCandle = new BusinessCandle();
        $market_data = array();

        $last_candle = new \DateTime();
        $last_candle = $last_candle->sub(new \DateInterval("PT2M"));
        
        while(true) {

            $now = new \DateTime();
            $last_full_min = $now->sub(new \DateInterval("PT1M"));

            //VERIFIER AUCUN DELAIS RECEPTION DATA SINON DECLER DE QUELQUES SECONDES LA VERIFICATION

            if ($last_full_min->format("Y-m-d H:i") <= $last_candle->format("Y-m-d H:i")) {
                continue;
            }

            foreach ($this->currencies as $currency) {
                
                $market = "BTC-" . $currency;
                $historic = Bittrex::getMarketHistory($market);

                foreach ($historic['result'] as $trade) {

                    $trade_date = \DateTime::createFromFormat('Y-m-d\TH:i:s', substr($trade['TimeStamp'], 0, 19));

                    if ($trade_date->format("Y-m-d H:i") > $last_full_min->format("Y-m-d H:i") || $trade_date->format("Y-m-d H:i") <= $last_candle->format("Y-m-d H:i")) {
                        continue;
                    }

                    $marketData[$market][$trade_date->format("Y-m-d H:i")][] = $trade;
                }
            }
            
            foreach ($marketData as $market => $candles) {

                foreach ($candles as $date => $transactions) {
                    
                    $businessCandle->compute_candle_1m($market, $transactions);
                }
            }
            

            foreach ($marketData as $market) {
                
                foreach ($market as $key => $value) {
                    $date_candle = \DateTime::createFromFormat("Y-m-d H:i", $key);
                    if ($date_candle->format("Y-m-d H:i") > $last_candle->format("Y-m-d H:i")) {
                        $last_candle = $date_candle;
                    }
                }
                
            }
            
            $marketData = array();

            if ($last_candle->format('i') % 5 != 4) {
                continue;
            }

            $last_candle->setTime($last_candle->format('H'), $last_candle->format('i'), 59);

            foreach ($this->currencies as $market) {
                $businessCandle->compute_candles_with_interval("BTC-" . $market, $last_candle, 5);
            }

        }
    }

    /**
    *public function handle()
    *{
    *    $market_data = array();
    *    
    *    while(true) {
    *
    *        foreach ($this->currencies as $currency) {
    *            
    *            $market = "BTC-" . $currency;
    *            $tickers = Bittrex::getChartData($market, 'oneMin');
    *            var_dump($tickers);die();
    *
    *            foreach ($tickers['result'] as $ticker) {
    *                
    *            }
    *        }
    *    }
    *}
    */
}
