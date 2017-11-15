<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessTransaction;
use App\Business\BusinessWallet;
use App\Models\Transaction;
use App\Models\Candle_1m;
use App\Models\Wallet;
use Bittrex;

class SummaryTransaction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:summary_transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Summary of all transactions passed';

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
        $this->strategy_name = 'cancel_all';
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $business_transaction = new BusinessTransaction($this->broker_name, $this->strategy_name);

        //$transactions = Transaction::where('status', 'open')->get();
        $transactions = Transaction::where('status', 'close')->get();

        foreach ($transactions as $transaction)
        {
            $actual_order = $business_transaction->broker->get_order($transaction->order_id);

            if (!$actual_order)
            {
                continue;
            }

            echo "For transaction " . $transaction->currencies . " : " . $transaction->order_id . "\n";
            echo "  Type : " . $transaction->type . "\n";
            echo "  Actual Rate : " . $actual_order['actual_rate'] . "\n";
            echo "  Actual Quantity : " . $actual_order['actual_quantity'] . "\n";
            echo "  Actual Fees : " . $actual_order['actual_fees'] . "\n";
      }
    
    }
}
