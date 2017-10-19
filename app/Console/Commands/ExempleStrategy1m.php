<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessTradingAnalysis;
use App\Business\BusinessWallet;
use App\Business\BusinessTransaction;
use Pepijnolivier\Bittrex\Bittrex;
use Illuminate\Support\Facades\DB;
use Log;


class ExempleStrategy1m extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:exemple_strategy_1m';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'test strategy based on candle 1m';

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
        $trading_analysis = new BusinessTradingAnalysis();
        
        $period = 20;
        $assets_status = array();

        //TODO initialiser assets_status avec transaction db
        
        foreach ($this->currencies as $currency) {
            $assets_status[$currency] = 'waiting_buy';
        }

        while(true) {

            foreach ($this->currencies as $currency) {

                $data_cci = array();
                $market = 'BTC-' . $currency;
               

                $limit_date = new \DateTime();
                $limit_date->sub(new \DateInterval("PT" . $period . "M"));
                $limit_date->setTime($limit_date->format('H'), $limit_date->format('i'), 0);

                $candles = DB::table('candles_1m')->where('currencies', $market)->where('open_time', '>=', $limit_date)->get();
                
                if (count($candles) < 10) {
                    continue;
                }

                //var_dump(count($candles));
                $prev_candle = $candles[0];
                $last_candle_theoric = new \DateTime();
                $last_candle_theoric->sub(new \DateInterval("PT1M"));
                $last_candle_theoric->setTime($last_candle_theoric->format('H'), $last_candle_theoric->format('i'), 0);

                //Generate candle begin of period if doesn't exist

                foreach ($candles as $candle) {
                    
                    $date_candle = new \DateTime($candle->open_time);
                    $date_prev_candle = new \DateTime($prev_candle->open_time);

                    $diff_date_candles = $date_candle->diff($date_prev_candle);

                    if ($diff_date_candles->i > 1) {
                        for ($i = 0; $i < $diff_date_candles->i - 1; $i++) {
                            $data_cci['high'][] = $prev_candle->max_price;
                            $data_cci['low'][] = $prev_candle->min_price;
                            $data_cci['close'][] = $prev_candle->close_price;
                        }
                    }

                    $data_cci['high'][] = $candle->max_price;
                    $data_cci['low'][] = $candle->min_price;
                    $data_cci['close'][] = $candle->close_price;

                    $prev_candle = $candle;
                }

                $date_prev_candle = new \DateTime($prev_candle->open_time);

                $nb_missing_last_candle = $date_prev_candle->diff($last_candle_theoric);

                for ($i = 0; $i < $nb_missing_last_candle->i; $i++) {
                    $data_cci['high'][] = $prev_candle->max_price;
                    $data_cci['low'][] = $prev_candle->min_price;
                    $data_cci['close'][] = $prev_candle->close_price;
                    $data_cci['volume'][] = $prev_candle->volume;
                }


                //var_dump(count($data_cci['high']));
                $cci = $trading_analysis->cci($market, $data_cci);
                $cmo = $trading_analysis->cmo($market, $data_cci);
                $mfi == $trading_analysis->mfi($market, $data_cci);

                switch ($assets_status[$currency]) {
                    case 'waiting_buy':

                        if ($cci > -100 || $cmo > -50 || $mfi >= 10) {
                            break;
                        }

                        $this->buy($currency);
                        $assets_status[$currency] = 'waiting_sell';
                        break;

                    case 'waiting_sell':

                        if ($cci < 100 || $cmo < 50 || $mfi < 80) {
                            break;
                        }

                        $this->sell($currency);
                        $assets_status[$currency] = 'waiting_buy';
                        break;
                }
            }
            sleep(20);
        }
    }

    private function buy ($currency) {
        
        $transaction = new BusinessTransaction('bittrex' ,'test cci');
        $wallet = new BusinessWallet();
        try {
            $ticker = Bittrex::getTicker('BTC-' . $currency);
        } catch (\Exception $e) {
            return false;
        }
        
        $rate = $ticker["result"]["Last"];
        $quantity_crypto = 0.05 / $rate;

        $fees = $transaction->compute_fees('buy', $quantity_crypto, $rate);
        $sum = $rate * $quantity_crypto + $fees;

        $wallet->register_sell('BTC', $sum);
        $wallet->register_buy($currency, $quantity_crypto);

        Log::info($currency . " : strategy 1m buy for 0.05 BTC plus " . $fees . " BTC");

        return true;
    }

    private function sell ($currency) {
        
        $transaction = new BusinessTransaction('bittrex' ,'test cci');
        $wallet = new BusinessWallet();
        try {
            $ticker = Bittrex::getTicker('BTC-' . $currency);
        } catch (\Exception $e) {
            return false;
        }

        $rate = $ticker["result"]["Last"];
        $quantity = DB::table('wallets')->where('currency', $currency)->first();
        
        $fees = $transaction->compute_fees('sell', $quantity->available, $rate);
        
        $value_sell = $rate * $quantity->available;
        $sum = $value_sell - $fees;
        
        $investissment = (0.05 + 0.000125);
        $benef = $sum - $investissment;
        $profit = $benef / $investissment * 100;

        $wallet->register_buy('BTC', $sum, 0);
        $wallet->register_sell($currency, $quantity->available);

        Log::info($currency . " : strategy 1m sell for " . $value_sell . " BTC - (" . $sum . " with fees [" . $fees . " BTC]) PROFIT : " . round($profit, 2) . "%");

        return true;
    }
}
