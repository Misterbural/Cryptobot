<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessTransaction;
use App\Models\Transaction;
use Pepijnolivier\Bittrex\Bittrex;

class testtamere extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:testtamere';

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
        /*
        var_dump(Bittrex::getOrderHistory());
        var_dump(Bittrex::getOpenOrders());
        die();
         */
        $business_transaction = new BusinessTransaction('bitfinex');

        $id_order_open = '2de73c55-d040-4d63-8369-b2d7e58974b4';
        $id_order_close = '10de49e1-0038-458e-850d-b5954b4870dc';

        /*$order_open = $business_transaction->get_order($id_order_open);
        $order_close = $business_transaction->get_order($id_order_close);

        echo "Order Open : \n";
        var_dump($order_open);
        echo "\n\n";

        echo "Order close : \n";
        var_dump($order_close);
        echo "\n\n";*/

        var_dump($business_transaction->get_withdraw_fees("BTC"));

    }
}
