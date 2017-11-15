<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessTransaction;
use App\Business\BusinessWallet;
use App\Models\Transaction;
use App\Models\Candle_1m;
use App\Models\Wallet;
use Bittrex;

class FixAllWallets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:fix_all_wallets';

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
        $this->strategy_name = 'fix_all_wallets';
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $business_transaction = new BusinessTransaction($this->broker_name, $this->strategy_name);

        $wallets = Wallet::where('broker', $this->broker_name)->get();
        $balances = $business_transaction->broker->get_balances();

        if (!$balances)
        {
            echo "Cannot get balances";
            exit(1);
        }

        foreach ($wallets as $wallet)
        {
            foreach ($balances as $currency => $balance)
            {
                if ($currency != $wallet->currency)
                {
                    continue;
                }

                echo "Update wallet " . $wallet->currency . "\n";
                $wallet->available = $balance['available'];
                $wallet->on_trade = $balance['on_trade'];
                $wallet->save();
            }
        }
    
    }
}
