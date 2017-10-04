<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessTradingAnalysis;
use App\Business\BusinessWallet;
use App\Business\BusinessTransaction;
use Pepijnolivier\Bittrex\Bittrex;
use Illuminate\Support\Facades\DB;
use Log;


class ExempleStrategyCCI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:exemple_strategy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'test strategy based on cci';

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
        
        $period = 14;
        $assets_status = array();

        //TODO initialiser assets_status avec transaction db
        
        foreach ($this->currencies as $currency) {
            $assets_status[$currency] = 'waiting_buy';
        }

        while(true) {

            foreach ($this->currencies as $currency) {

                $data_cci = array();
                $market = 'BTC-' . $currency;

                $nb_candles = DB::table('candles_1m')->where('currencies', $market)->count();
                $offset = $nb_candles - $period;

                $candles = DB::table('candles_1m')->where('currencies', $market)->orderBy('close_time')->skip($offset)->take($period)->get();
                
                foreach ($candles as $candle) {
                    $data_cci['high'][] = $candle->max_price;
                    $data_cci['low'][] = $candle->min_price;
                    $data_cci['close'][] = $candle->close_price;
                }

                switch ($assets_status[$currency]) {
                    case 'waiting_buy':

                        if ($trading_analysis->cci($market, $data_cci) > -100) {
                            break;
                        }

                        Log::info($currency . " is under 100");
                        $assets_status[$currency] = 'under_neg_100';
                        break;

                    case 'under_neg_100':

                        if ($trading_analysis->cci($market, $data_cci) < -100) {
                            break;
                        }
                        
                        if ($this->buy($currency)) {
                            $assets_status[$currency] = 'waiting_sell';
                        }
                        break;

                    case 'waiting_sell':

                        if ($trading_analysis->cci($market, $data_cci) < 100) {
                            break;
                        }

                        Log::info($currency . " is over 100");
                        $assets_status[$currency] = 'over_pos_100';
                        break;
                    case 'over_pos_100':
                        
                        if ($trading_analysis->cci($market, $data_cci) > 100) {
                            break;
                        }

                        if ($this->sell($currency)) {
                            $assets_status[$currency] = 'waiting_buy';
                        }
                        break;
                    
                }
            }
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
        
        $rate = $ticker["result"]["Ask"];
        $quantity_crypto = 0.05 * $rate;

        $fees = $transaction->compute_fees('buy', $quantity_crypto, $rate);
        $sum = $rate * $quantity_crypto + $fees;

        $wallet->register_sell('BTC', $sum);
        $wallet->register_buy($currency, $quantity_crypto);

        Log::info($currency . " : break -100 -> buy " . $quantity_crypto . " for 0.05 BTC plus " . $fees . " BTC");

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

        $rate = $ticker["result"]["Bid"];
        $quantity = DB::table('wallets')->where('currency', $currency)->first();
        
        $fees = $transaction->compute_fees('sell', $quantity->available, $rate);
        
        $sum = $rate * $quantity - $fees;

        $wallet->register_buy('BTC', $sum, 0.05);
        $wallet->register_sell($currency, $quantity);

        Log::info($currency . " : break +100 -> sell " . $quantity . " for " . $sum . " BTC fees already paid (" . $fees . " BTC)");

        return true;
    }
}
