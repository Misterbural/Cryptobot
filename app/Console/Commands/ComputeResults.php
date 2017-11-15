<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessTransaction;
use App\Business\BusinessWallet;
use App\Models\Transaction;
use App\Models\Candle_1m;
use App\Models\Wallet;
use Bittrex;

class ComputeResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:compute_results {at_date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compute the result';

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
        $this->strategy_name = 'autobuy_on_win';
        $this->sell_strategy_name = 'autosell_on_lost';

        //How max percent of our bitcoin wallet we can spend
        $this->max_btc_percent_to_spend = 0.05;

        //How much win need to be to take decision
        $this->percent_win_to_buy = 0.05;

        //How long must be the period of loosing 
        $this->duration_win_to_buy = new \DateInterval('PT5M'); //5 min
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //Get max date
        $at_date = $this->argument('at_date');
        $at_date = new \DateTime($at_date);

        $total_bitcoin_value = 0;
        $wallets = Wallet::where('broker', $this->broker_name)->get();
        foreach ($wallets as $wallet)
        {
            //Specific for btc
            if ($wallet->currency == 'BTC')
            {
                $total_bitcoin_value += $wallet->available;
                continue;
            }

            var_dump($at_date);

            $candle = Candle_1m::where('currencies', 'BTC-' . $wallet->currency)->where('close_time', '<', $at_date)->orderBy('close_time', 'desc')->first();

            if (!$candle)
            {
                continue;
            }
            
            //$total_bitcoin_value += $wallet->available * $candle->close_price;
            $total_bitcoin_value += $wallet->available * $candle->close_price;
        }

        echo "Before start we have " . $total_bitcoin_value . " Bitcoins !\n";

    }
}
