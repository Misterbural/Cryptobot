<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessTransaction;
use App\Business\BusinessWallet;
use App\Models\Transaction;
use App\Models\Candle_5m;
use App\Models\Wallet;
use Bittrex;

class AutobuyOnCCI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:autobuy_on_cci {max_date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor for autobuying when cci indicate buy';

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
        $this->strategy_name = 'autobuy_on_cci';
        $this->sell_strategy_name = 'autosell_on_lost';

        //How max percent of our bitcoin wallet we can spend
        $this->max_btc_percent_to_spend = 0.1;
        
        //Do we want to use at least the minimum order size to buy
        $this->force_minimum_order_size = true;

        //How much win need to be to take decision
        $this->percent_win_to_buy = 0.05;

        //How long must be the period of loosing 
        $this->duration_win_to_buy = new \DateInterval('PT2M'); //2 min

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
                    $last_transaction_date = new \DateTime($last_transaction->created_at);

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
                $candles = Candle_5m::where('close_time', '>', $last_transaction_date)->where('close_time', '<', $max_date)->where('currencies', $market)->orderBy('close_time')->limit(100)->get();

                //We get the candle with the max close price for the period
                var_dump($candles);die();


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
