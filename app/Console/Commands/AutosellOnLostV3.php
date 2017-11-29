<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessTransaction;
use App\Business\BusinessWallet;
use App\Models\Transaction;
use App\Models\Candle_1m;
use App\Models\Wallet;
use Bittrex;

class AutosellOnLostV3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:autosell_on_lost_v3 {max_date}';

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
        $this->strategy_name = 'autosell_on_lost_v3';
        $this->buy_strategy_name = 'autobuy_on_win_v3';
        
        //How much the lost or win need to be to take decision
        $this->percent_lost_to_sell = '0.05';

        //How long must be the period of loosing 
        $this->duration_lost_to_sell = new \DateInterval('PT2M'); //5 min
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $max_date = new \DateTime($this->argument('max_date'));
        $business_transaction = new BusinessTransaction($this->broker_name, $this->strategy_name);

        //On fait tourner le robot h24
        //while (1)
        if(1)
        {
            //Sleep for few second to preserve processor
            //sleep(5);

            //Do verifications for all wallets
            $wallets = Wallet::where('broker', $this->broker_name)->where('available', '>', 0)->get();
            foreach ($wallets as $wallet)
            {
                
                //We skip BTC who dont work the same way
                if ($wallet->currency == 'BTC')
                {
                    continue;
                }

                $market = 'BTC-' . $wallet->currency;
                echo "SELL - Search transactions for market " . $market . "\n";

                //Get the last complete sell or buy transaction for this strategy and wallet
                $last_transaction = Transaction::where('currencies', $market)->
                                    where('broker', $this->broker_name)->
                                    where('currencies', $market)->
                                    where(
                                        function ($q)
                                        {
                                            $q->where('strategy', $this->strategy_name)->
                                                orWhere('strategy', $this->buy_strategy_name);
                                        }
                                    )->
                                    orderBy('created_at', 'desc')->
                                    first();

                
                $buy_transaction_to_link = false;

                if (!$last_transaction)
                {
                    $last_transaction_date = new \DateTime('2000-01-01'); //Initialize to 2000 to take all transaction history
                }
                else
                {
                    $last_transaction_date = new \DateTime($last_transaction->created_at);

                    //Check if we must link with a buy transaction
                    if ($last_transaction->strategy == $this->buy_strategy_name && $last_transaction->type == 'buy')
                    {
                        $buy_transaction_to_link = $last_transaction->order_id;
                    }
                }

                //We get all candles on 1m since this date
                //$candles = Candle_1m::where('close_time', '>', $last_transaction_date)->where('currencies', $market)->orderBy('close_time')->get();
                $candles = Candle_1m::where('close_time', '>', $last_transaction_date)->where('close_time', '<', $max_date)->where('currencies', $market)->orderBy('close_time')->get();

                //We get the candle with the max close price for the period
                $max_price = false;
                foreach ($candles as $candle)
                {
                    $compare_price = $candle->open_price < $candle->close_price ? $candle->close_price : $candle->open_price;
                    $max_price = $max_price < $compare_price ? $compare_price : $max_price;
                }
    
                if (!$max_price)
                {
                    continue;
                }
                
                echo ($market . ' - ' . $max_price) . "\n";

                $last_candle = $candles->last();

                if (!$last_candle)
                {
                    continue;
                }

                //If last candle close_price not under the historical max close price of at least $this->percent_lost_to_sell, simply skip
                if ($last_candle->close_price > $max_price * (1 - $this->percent_lost_to_sell))
                {
                    echo "PASS - Diff between max and last = " . (($last_candle->close_price - $max_price) / $max_price * 100) . "%\n" ;
                    continue;
                }
                else
                {
                    echo "SELL - Diff between max and last = " . (($last_candle->close_price - $max_price) / $max_price * 100) . "%\n" ;
                }

                //Check if close_price constantly drop compare to the open price since at least $duration_lost_to_sell
                $date_since_we_lost = new \DateTime($last_candle->created_at);
                $date_since_we_lost->sub($this->duration_lost_to_sell);
                $count_candles = 0;
                $always_drop = true;
                

                echo "Since we lost " . $date_since_we_lost->format('Y-m-d H:i:s') . "\n";
                

                foreach ($candles as $candle)
                {
                    //If candle is to old, skip
                    $candle_date = new \DateTime($candle->created_at);
                    if ($candle_date < $date_since_we_lost)
                    {
                        continue;
                    }
                    
                    echo $candle_date->format('Y-m-d h:i:s') . "\n";

                    //If this candle close_price superior to open_price, then we dont have always drop
                    if ($candle->close_price > $candle->open_price)
                    {
                        $always_drop = false;
                    }

                    $count_candles ++;
                }
                echo "Find " . $count_candles . " candles\n";
                
                echo "Always drop = " . ($always_drop ? 'true' : 'false') . "\n";

                //If we have not always drop, or if we do not have enough following candles, we skip
                if (!$always_drop || $count_candles < $this->duration_lost_to_sell->format('%i'))
                {
                    continue;
                }


                //If we are here, then there is probably a panic movement, and we must sell as quick as possible for bitcoin
                //$rate_to_sell = $business_transaction->broker->get_market_bid_rate($market); //Using bid rate for very quick sell
                $rate_to_sell = $last_candle->close_price;
                $quantity_to_sell = $wallet->available;
                
                if (!$rate_to_sell)
                {
                    echo "Impossible to get the rate_to_sell for " . $market . "\n";
                    continue;
                }

                //$transaction_result = $business_transaction->sell($market, $quantity_to_sell, $rate_to_sell, $buy_transaction_to_link);
                
                $transaction_fees = $business_transaction->broker->compute_fees('sell', $quantity_to_sell, $rate_to_sell);
                $transaction = new Transaction;
                $transaction->strategy = $this->strategy_name;
                $transaction->currencies = $market;
                $transaction->created_at = $last_candle->close_time;
                $transaction->updated_at = $last_candle->close_time;
                $transaction->quantity = $quantity_to_sell;
                $transaction->rate = $rate_to_sell;
                $transaction->fees = $transaction_fees;
                $transaction->status = 'close';
                $transaction->type = 'sell';
                $transaction->order_id = sha1(uniqid().rand(0,1000));
                $transaction->broker = $this->broker_name;

                $transaction->save();
                
                $wallet_btc = Wallet::where('broker', $this->broker_name)->where('currency', 'BTC')->first();
                $business_wallet = new BusinessWallet($this->broker_name);
                $business_wallet->register_buy($wallet_btc['currency'], $quantity_to_sell * $rate_to_sell - $transaction_fees);
                $business_wallet->register_sell($wallet['currency'], $quantity_to_sell);

                echo "Transaction of " . $quantity_to_sell . " " . $market . " for a rate of " . $rate_to_sell . " passed.\n";

                $transaction_result = true;
                if (!$transaction_result)
                {
                    echo "Transaction of " . $quantity_to_sell . " " . $market . " for a rate of " . $rate_to_sell . " failed.\n";
                }
                
            }
        }
    }
}
