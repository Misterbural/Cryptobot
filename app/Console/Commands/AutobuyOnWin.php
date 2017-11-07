<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessTransaction;
use App\Business\BusinessWallet;
use App\Models\Transaction;
use App\Models\Candle_1m;
use App\Models\Wallet;
use Bittrex;

class AutobuyOnWin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:autobuy_on_win';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor for autobuying when a crypto reate increase';

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
        $business_transaction = new BusinessTransaction($this->broker_name, $this->strategy_name);

        //On fait tourner le robot h24
        //while (1)
        
        $f = fopen('./storage/app/candles_1m.csv','r');
        while (($line = fgetcsv($f)) !== FALSE)
        {
            $candle = new Candle_1m;
            $candle->open_price = $line[1];
            $candle->close_price = $line[2];
            $candle->min_price = $line[3];
            $candle->max_price = $line[4];
            $candle->currencies = $line[5];
            $candle->volume = $line[6];
            $candle->open_time = $line[7];
            $candle->close_time = $line[8];
            $candle->created_at = $line[9];
            $candle->updated_at = $line[10];
            $candle->save();


            //Sleep for few second to preserve processor
            //sleep(5);

            //Do verifications for all wallets
            $wallets = Wallet::where('broker', $this->broker_name)->get();
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
                                        where('type', 'buy')->
                                        where('broker', $this->broker_name)->
                                        where('strategy', $this->strategy_name)->
                                        where('currencies', $market)->
                                        orderBy('created_at', 'desc')->
                                        first();

                if (!$last_transaction)
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
                $min_candle = $candles->first();
                foreach ($candles as $candle)
                {
                    if ($min_candle->close_price > $candle->close_price)
                    {
                        $min_candle = $candle;
                    }
                }

                $last_candle = $candles->last();

                if (!$last_candle)
                {
                    continue;
                }

                //If last candle close_price not above the historical min close price of at least $this->percent_win_to_buy, simply skip
                if ($last_candle->close_price < $min_candle->close_price * (1 + $this->percent_win_to_buy))
                {
                    continue;
                }

                //Check if close_price constantly go up compare to the open price since at least $duration_win_to_buy
                $date_since_we_win = new \DateTime($last_candle->created_at);
                $date_since_we_win->sub($this->duration_win_to_buy);
                $count_candles = 0;
                $always_go_up = true;
                foreach ($candles as $candle)
                {
                    //If candle is to old, skip
                    $candle_date = new \DateTime($candle->created_at);
                    if ($candle_date->format('Ymdhi') < $date_since_we_win->format('Ymdhi'))
                    {
                        continue;
                    }

                    //If this candle close_price inferior to open_price, then we dont have always go up
                    if ($candle->close_price < $candle->open_price)
                    {
                        //$always_go_up = false;
                    }

                    $count_candles ++;
                }

                //If we have not always go up, or if we do not have enough following candles, we skip
                if (!$always_go_up || $count_candles < $this->duration_win_to_buy->format('%i'))
                {
                    continue;
                }


                //If we are here, then there is probably a go up movement, and we must buy as quick as possible
                //$rate_to_buy = $business_transaction->broker->get_market_ask_rate($market);
                $rate_to_buy = $line[3];

                //We compute the quantity_to_buy from the maximum quantity to bitcoin we are agree to spend
                $wallet_btc = Wallet::where('broker', $this->broker_name)->where('currency', 'BTC')->first();
                $quantity_btc_to_spend = $wallet_btc->available * $this->max_btc_percent_to_spend;
                $quantity_to_buy = $quantity_btc_to_spend / $rate_to_buy;

                
                if (!$rate_to_buy)
                {
                    echo "Impossible to get the rate_to_buy for " . $market . "\n";
                }

                //$transaction_result = $business_transaction->buy($market, $quantity_to_buy, $rate_to_buy);
                
                //Save transaction
                $transaction = new Transaction;
                $transaction->strategy = $this->strategy_name;
                $transaction->currencies = $market;
                $transaction->created_at = $line[9];
                $transaction->updated_at = $line[9];
                $transaction->quantity = $quantity_to_buy;
                $transaction->rate = $rate_to_buy;
                $transaction->fees = $business_transaction->broker->compute_fees('buy', $quantity_to_buy, $rate_to_buy);
                $transaction->status = 'close';
                $transaction->type = 'buy';
                $transaction->order_id = sha1(uniqid().rand(0,1000));
                $transaction->broker = $this->broker_name;
                $transaction->save();

                //Update wallet for btc and crypto currency
                $business_wallet = new BusinessWallet($this->broker_name);
                $business_wallet->register_buy($wallet->currency, $quantity_to_buy);
                $business_wallet->register_sell($wallet_btc->currency, $quantity_btc_to_spend);

                $transaction_result = true;

                echo "Transaction of " . $quantity_to_buy . " " . $market . " for a rate of " . $rate_to_buy . " passed.\n";

                if (!$transaction_result)
                {
                    echo "Transaction of " . $quantity_to_buy . " " . $market . " for a rate of " . $rate_to_buy . " failed.\n";
                }
                
            }
        }
    }
}
