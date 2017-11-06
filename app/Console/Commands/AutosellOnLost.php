<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessTransaction;
use App\Business\BusinessWallet;
use App\Models\Transaction;
use App\Models\Candle_1m;
use App\Models\Wallet;
use Bittrex;

class AutosellOnLost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:autosell_on_lost';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor for autoselling when a crypto drop';

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
        $this->strategy_name = 'autosell_on_lost';
        
        //How much the lost or win need to be to take decision
        $this->percent_lost_to_sell = '0.05';
        $this->percent_win_to_buy = '0.05';

        //How long must be the period of loosing 
        $this->duration_lost_to_sell = new \DateInterval('PT5M'); //5 min
        $this->duration_win_to_sell = new \DateInterval('PT5M'); //5 min
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $business_transaction = new BusinessTransaction($this->broker_name, $this->strategy_name);

        //On fait tourner le robot h24
        while (1)
        {
            //Sleep for few second to preserve processor
            sleep(5);

            //Do verifications for all wallets
            $wallets = Wallet::where('available', '>', 0)->get();
            foreach ($wallets as $wallet)
            {
                
                //We skip BTC who dont work the same way
                if ($wallet->currency == 'BTC')
                {
                    continue;
                }

                $market = 'BTC-' . $wallet->currency;

                //Get the last complete sell transaction for this strategy and wallet
                $last_transaction = Transaction::where('currencies', $market)->
                                        where('status', 'close')->
                                        where('type', 'sell')->
                                        where('strategy', $this->strategy_name)->
                                        where('currencies', $market)->
                                        orderBy('created_at')->
                                        limit(1)->
                                        get();

                if (!$last_transaction->count())
                {
                    $last_transaction_date = new \DateTime('2000-01-01'); //Initialize to 2000 to take all transaction history
                }
                else
                {
                    $last_transaction_date = new \DateTime($last_transaction->created_at);
                }

                //We get all candles on 1m since this date
                $candles = Candle_1m::where('created_at', '>', $last_transaction_date)->where('currencies', $market)->get();

                //We get the candle with the max close price for the period
                $max_candle = $candles->first();
                foreach ($candles as $candle)
                {
                    if ($max_candle->close_price < $candle->close_price)
                    {
                        $max_candle = $candle;
                    }
                }

                $last_candle = $candles->last();

                if (!$last_candle)
                {
                    continue;
                }

                //If last candle close_price not under the historical max close price of at least $this->percent_lost_to_sell, simply skip
                if ($last_candle->close_price > $max_candle->close_price * (1 - $this->percent_lost_to_sell))
                {
                    continue;
                }

                //Check if close_price constantly drop compare to the open price since at least $duration_lost_to_sell
                $date_since_we_lost = new \DateTime($last_candle->created_at);
                $date_since_we_lost->sub($this->duration_lost_to_sell);
                $count_candles = 0;
                $always_drop = true;
                foreach ($candles as $candle)
                {
                    //If candle is to old, skip
                    $candle_date = new \DateTime($candle->created_at);
                    if ($candle_date->format('Ymdhi') < $date_since_we_lost->format('Ymdhi'))
                    {
                        continue;
                    }

                    //If this candle close_price superior to open_price, then we dont have always drop
                    if ($candle->close_price > $candle->open_price)
                    {
                        $always_drop = false;
                    }

                    $count_candles ++;
                }

                //If we have not always drop, or if we do not have enough following candles, we skip
                if (!$always_drop || $count_candles < $this->duration_lost_to_sell->format('%i'))
                {
                    continue;
                }


                //If we are here, then there is probably a panic movement, and we must sell as quick as possible for bitcoin
                $rate_to_sell = $business_transaction->broker->get_market_bid_rate($market);
                $quantity_to_sell = $wallet->available;
                
                if (!$rate_to_sell)
                {
                    echo "Impossible to get the rate_to_sell for " . $market . "\n";
                }

                $transaction_result = $business_transaction->sell($market, $quantity_to_sell, $rate_to_sell);
                $transaction_result = true;

                if (!$transaction_result)
                {
                    echo "Transaction of " . $quantity_to_sell . " " . $market . " for a rate of " . $rate_to_sell . " failed.\n";
                }
                
            }
        }
    }
}
