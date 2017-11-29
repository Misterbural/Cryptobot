<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Business\BusinessTransaction;
use App\Business\BusinessWallet;
use App\Models\Transaction;
use App\Models\Candle_1m;
use App\Models\Wallet;
use Bittrex;

class TestAutoStrategies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:test_auto_strategies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test strategies of auto sell and buy';

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

        //On fixe les différents reglages
        $this->broker_name = 'bittrex';
        $this->sell_strategy_name = 'autosell_on_lost';
        $this->buy_strategy_name = 'autobuy_on_win';
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $wallets = Wallet::where('broker', $this->broker_name)->where('available', '>', 0)->get();
        $total_bitcoin_value = 0;
        foreach ($wallets as $wallet)
        {
            //Specific for btc
            if ($wallet->currency == 'BTC')
            {
                $total_bitcoin_value += $wallet->available;
                continue;
            }

            $candle = Candle_1m::where('currencies', 'BTC-' . $wallet->currency)->orderBy('created_at')->first();

            if (!$candle)
            {
                continue;
            }
            
            $total_bitcoin_value += $wallet->available * $candle->close_price;
        }

        echo "Before start we have " . $total_bitcoin_value . " Bitcoins !\n";

        $candles = Candle_1m::select('close_time')->groupBy('close_time')->orderBy('close_time')->get();
        $start_time = new \DateTime();
        echo "
        #############################\n
        # START AT " . $start_time->format('Y-m-d H:i:s') . " #\n
        #############################\n";
        foreach ($candles as $candle)
        {
            $d = new \DateTime($candle->close_time);
            echo "Try buy before " . $d->format('Y-m-d H:i:s') . ".\n";
            Artisan::call('cryptobot:' . $this->buy_strategy_name, [
                'max_date' => $candle->close_time,
            ]);

            echo "Try sell before " . $d->format('Y-m-d H:i:s') . ".\n";
            Artisan::call('cryptobot:' . $this->sell_strategy_name, [
                'max_date' => $candle->close_time,
            ]);
            
            echo "--------------\n";
        }
        $end_time = new \DateTime();
        echo "
        #############################\n
        # END AT " . $end_time->format('Y-m-d H:i:s') . " #\n
        #############################\n";
        
        $wallets = Wallet::where('broker', $this->broker_name)->where('available', '>', 0)->get();
        $total_bitcoin_value = 0;
        foreach ($wallets as $wallet)
        {
            //Specific for btc
            if ($wallet->currency == 'BTC')
            {
                $total_bitcoin_value += $wallet->available;
                continue;
            }
            
            $candle = Candle_1m::where('currencies', 'BTC-' . $wallet->currency)->orderBy('created_at', 'desc')->first();

            if (!$candle)
            {
                continue;
            }
            
            $total_bitcoin_value += $wallet->available * $candle->close_price;
        }

        echo "At the end we have " . $total_bitcoin_value . " Bitcoins !\n";
    }
}
