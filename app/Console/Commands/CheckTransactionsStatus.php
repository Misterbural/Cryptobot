<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessBittrex;
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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->currencies = config('constants.currencies');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $business_bittrex = new BusinessBittrex();

        //On fait tourner le robot h24
        while (1)
        {
            $open_transactions = Transaction::where('status', 'open')->get();
            
            foreach ($open_transactions as $key => $open_transaction)
            {
                $order = $business_bittrex->get_order($open_transaction->order_id);

                if ($order['success'] != true)
                {
                    continue;
                }

                if ($order['result']['IsOpen'] == true)
                {
                    continue;
                }

                $business_bittrex->validate_transaction($order_id);
            }
        }
    }
}
