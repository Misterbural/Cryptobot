<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessTransaction;
use App\Models\Transaction;
use Bittrex;

class CheckTransactionsStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:check_transactions_status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all open transactions in database and check on bitrex if they are still open';

    /**
    * List of currencies for trading
    * 
    * @var array
    */
    protected $currencies;

    /**
     * List of borker we trade on
     *
     * @var array
     */
    protected $brokers;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->currencies = config('constants.currencies');
        $this->brokers = config('constants.brokers');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $business_transaction = new BusinessTransaction('bittrex');

        //On fait tourner le robot h24
        while (1)
        {
            foreach ($this->brokers as $broker_name)
            {
                $open_transactions = Transaction::where('broker', $broker_name)->where('status', 'open')->get();
                
                foreach ($open_transactions as $key => $open_transaction)
                {
                    $order = $business_transaction->get_order($open_transaction->order_id);

                    if ($order['success'] != true)
                    {
                        continue;
                    }

                    if ($order['result']['IsOpen'] == true)
                    {
                        continue;
                    }

                    $business_transaction->validate_transaction($order_id);
                }
            }
        }
    }
}
