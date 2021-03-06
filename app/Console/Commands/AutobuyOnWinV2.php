<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessTransaction;
use App\Business\BusinessWallet;
use App\Models\Transaction;
use App\Models\Candle_1m;
use App\Models\Wallet;
use Bittrex;

class AutobuyOnWinV2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:autobuy_on_win_v2 {max_date}';

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
        $this->strategy_name = 'autobuy_on_win_v2';
        $this->sell_strategy_name = 'autosell_on_lost_v2';

        //How max percent of our bitcoin wallet we can spend
        $this->max_btc_percent_to_spend = 0.1;
        
        //Do we want to use at least the minimum order size to buy
        $this->force_minimum_order_size = true;

        //How much win need to be to take decision
        $this->percent_win_to_buy = 0.03;

        //How long must be the period of loosing 
        $this->duration_win_to_buy = new \DateInterval('PT3M'); //2 min

        //How is the minimum unit coin value in satoshi to buy
        $this->minimum_unit_coin_value = 500;
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
            $wallets = Wallet::where('broker', $this->broker_name)->get();
            foreach ($wallets as $wallet)
            {
                //We skip BTC who dont work the same way
                if ($wallet->currency == 'BTC')
                {
                    continue;
                }

                $market = 'BTC-' . $wallet->currency;

                echo "BUY - Search transactions for market " . $market . "\n";

                //Get the last complete sell or buy transaction for this strategy and wallet
                $last_transaction = Transaction::where('currencies', $market)->
                                    where('broker', $this->broker_name)->
                                    where(
                                        function ($q)
                                        {
                                            $q->where('strategy', $this->strategy_name)->
                                                orWhere('strategy', $this->sell_strategy_name);
                                        }
                                    )->
                                    orderBy('created_at', 'desc')->
                                    first();
                
                $already_buy = false;

                if (!$last_transaction)
                {
                    $last_transaction_date = new \DateTime('2000-01-01'); //Initialize to 2000 to take all transaction history
                }
                else
                {
                    $last_transaction_date = new \DateTime($last_transaction->close_time);

                    //If the last transaction for this money and strategy is a buy, then we have not sell it for now, and we must not buy it again
                    if ($last_transaction->strategy == $this->strategy_name && $last_transaction->type == 'buy')
                    {
                        $already_buy = true;
                    }
                }

                //If we already buy this money, skip it until we sell it
                if ($already_buy)
                {
                    continue;
                }

                //We get all candles on 1m since this date
                //$candles = Candle_1m::where('close_time', '>', $last_transaction_date)->where('currencies', $market)->orderBy('close_time')->get();
                $candles = Candle_1m::where('close_time', '>', $last_transaction_date)
                                    ->where('close_time', '<', $max_date)
                                    ->where('currencies', $market)
                                    ->orderBy('close_time', 'desc')
                                    ->limit(100)
                                    ->get();

                if (!$candles->count())
                {
                    echo "No candles\n";
                    continue;
                }

                //We get the last loosing candles
                $last_low_price = false;
                foreach ($candles as $candle)
                {
                    if ($candle->close_price >= $candle->open_price)
                    {
                        continue;
                    }
                    
                    $last_low_price = $candle->close_price;
                    break;
                }

                if (!$last_low_price)
                {
                    echo "no last low price\n";
                    continue;
                }

                echo ($market . ' - ' . $last_low_price) . "\n";

                $last_candle = $candles->first();

                //If last candle close_price not above the historical low_price price of at least $this->percent_win_to_buy, simply skip
                if ($last_candle->close_price < $last_low_price * (1 + $this->percent_win_to_buy))
                {
                    echo "PASS - Diff between min and last = " . (($last_candle->close_price - $last_low_price) / $last_low_price * 100) . "%\n" ;
                    continue;
                }
                else
                {
                    echo "BUY - Diff between min and last = " . (($last_candle->close_price - $last_low_price) / $last_low_price * 100) . "%\n" ;
                }

                //Check if close_price constantly go up compare to the open price since at least $duration_win_to_buy
                $date_start_up = new \DateTime($last_candle->created_at);
                $date_start_up->sub($this->duration_win_to_buy);
                $count_candles = 0;
                $always_go_up = true;

                echo "We win since " . $date_start_up->format('Y-m-d H:i:s') . "\n";

                foreach ($candles as $candle)
                {
                    //If candle is to old, skip
                    $candle_date = new \DateTime($candle->close_time);
                    if ($candle_date < $date_start_up)
                    {
                        break;
                    }

                    echo $candle_date->format('Y-m-d h:i:s') . "\n";

                    //If this candle close_price inferior to open_price, then we dont have always go up
                    if ($candle->close_price < $candle->open_price)
                    {
                        $always_go_up = false;
                    }

                    $count_candles ++;
                }
                echo "Find " . $count_candles . " candles\n";
                
                echo "Always go up = " . ($always_go_up ? 'true' : 'false') . "\n";

                //If we have not always go up, or if we do not have enough following candles, we skip
                if (!$always_go_up || $count_candles < $this->duration_win_to_buy->format('%i'))
                {
                    continue;
                }


                //If we are here, then there is probably a go up movement, and we must buy as quick as possible
                //$rate_to_buy = $business_transaction->broker->get_market_ask_rate($market);
                $rate_to_buy = $last_candle->close_price;

                //We compute the quantity_to_buy from the maximum quantity to bitcoin we are agree to spend
                $wallet_btc = Wallet::where('broker', $this->broker_name)->where('currency', 'BTC')->first();
                $quantity_btc_to_spend = $wallet_btc->available * $this->max_btc_percent_to_spend;
                $minimum_order_size = $business_transaction->broker->get_minimum_order_size($market) * 1.1; //+10% to ensure we can sell if go down

                //If we dont have enough for minimal order, use minimal_size instead of $this->max_btc_percent_to_spend
                if ($this->force_minimum_order_size == true && $quantity_btc_to_spend < $minimum_order_size && $wallet_btc->available > $minimum_order_size)
                {
                    $quantity_btc_to_spend = $minimum_order_size;
                }

                $quantity_to_buy = $quantity_btc_to_spend / $rate_to_buy;

                
                if (!$rate_to_buy)
                {
                    echo "Impossible to get the rate_to_buy for " . $market . "\n";
                    continue;
                }

                if ($rate_to_buy * 100000000 < $this->minimum_unit_coin_value)
                {
                    echo "Money under the minimum unit value of " . $this->minimum_unit_coin_value . " Satoshi\n";
                    continue;
                }

                //$transaction_result = $business_transaction->buy($market, $quantity_to_buy, $rate_to_buy);

                $transaction_fees = $business_transaction->broker->compute_fees('buy', $quantity_to_buy, $rate_to_buy);
                $transaction = new Transaction;
                $transaction->strategy = $this->strategy_name;
                $transaction->currencies = $market;
                $transaction->created_at = $last_candle->close_time;
                $transaction->updated_at = $last_candle->close_time;
                $transaction->quantity = $quantity_to_buy;
                $transaction->rate = $rate_to_buy;
                $transaction->fees = $transaction_fees;
                $transaction->status = 'close';
                $transaction->type = 'buy';
                $transaction->order_id = sha1(uniqid().rand(0,1000));
                $transaction->broker = $this->broker_name;

                $transaction->save();
                
                $business_wallet = new BusinessWallet($this->broker_name);
                $business_wallet->register_buy($wallet['currency'], $quantity_to_buy);
                $business_wallet->register_sell($wallet_btc['currency'], $quantity_btc_to_spend + $transaction_fees);

                echo "Transaction of " . $quantity_to_buy . " " . $market . " for a rate of " . $rate_to_buy . " passed.\n";

                $transaction_result=true;
                if (!$transaction_result)
                {
                    echo "Transaction of " . $quantity_to_buy . " " . $market . " for a rate of " . $rate_to_buy . " failed.\n";
                }
                
            }
        }
    }
}
