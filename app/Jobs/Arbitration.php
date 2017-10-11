<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Business\BusinessTransaction;

class Arbitration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $currency_code_buy;
    protected $broker_buy;
    protected $currency_code_sell;
    protected $broker_sell;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($currency_code_buy, $broker_buy, $currency_code_sell, $broker_sell)
    {
        $this->currency_code_buy = $currency_code_buy;
        $this->broker_buy = $broker_buy;
        $this->currency_code_sell = $currency_code_sell;
        $this->broker_sell = $broker_sell;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /////////////////////////////////////////////////////
        //         TODO VERIFIER MAINTENANCE WALLET        //
        /////////////////////////////////////////////////////

        $business_transaction_buy = new BusinessTransaction($this->broker_buy);
        $business_transaction_sell = new BusinessTransaction($this->broker_sell);

        $order_book_we_buy = $business_transaction_buy->getOrderBook("BTC-" . $this->currency_code_buy)['sell'];
        $order_book_we_sell = $business_transaction_sell->getOrderBook("BTC-" . $this->currency_code_sell)['buy'];

        $limit_buy_rate = $order_book_we_sell[0]['rate'] - $order_book_we_sell[0]['rate'] * 4 / 100;
        $limit_buy_quantity = $order_book_we_sell[0]['quantity'];
        //Gérer portefeuil vide (yobit retrun array empty)
        $limit_buy_btc = $business_transaction_buy->getBalances()['BTC']['available'];

        $orders = array(
            "total_quantity" => 0,
            "total_btc_value" => 0,
            "list" => [],
        );

        $last = false;

        foreach ($order_book_we_buy as $order_we_buy) {
            
            if ($order_we_buy['rate'] > $limit_buy_rate) {
                break;
            }

            $order = [];

            $order['quantity'] = $order_we_buy['quantity'];
            $order['rate'] = $order_we_buy['rate'];
            $order['btc_value'] = $order['quantity'] * $order['rate'] + $business_transaction_buy->compute_fees('buy', $order['quantity'], $order['rate']);

            if ($orders['total_quantity'] + $order['quantity'] > $limit_buy_quantity) {
                $order['quantity'] = $limit_buy_quantity - $orders['total_quantity'];
                $order['btc_value'] = $order['quantity'] * $order['rate'] + $business_transaction_buy->compute_fees('buy', $order['quantity'], $order['rate']);
                $last = true;
            }

            if ($orders['total_btc_value'] + $order['btc_value'] > $limit_buy_btc) {
                $btc_available = $limit_buy_btc - $orders['total_btc_value'];
                $quantity = $btc_available / $order['rate'];
                $order['quantity'] = $quantity - $business_transaction_buy->compute_fees('buy', $quantity, $order['rate']) / $order['rate'];
                $order['btc_value'] = $order['quantity'] * $order['rate'] + $business_transaction_buy->compute_fees('buy', $order['quantity'], $order['rate']);
                $last = true;
            }
            
            $orders['total_quantity'] += $order['quantity'];
            $orders['total_btc_value'] += $order['btc_value'];
            $orders['list'][] = $order;

            if ($last) {
                break;
            }
        }

        $benef = $orders['total_quantity'] * $order_book_we_sell[0]['rate'] - $orders['total_btc_value'];
        echo "Bénéfice total sur " . $this->currency_code_buy . " : " . $benef . " BTC\n";

        //Partie critique achat et vente
        /*foreach ($orders['list'] as $order) {
            $business_transaction_buy->buy('BTC-' . $this->currency_code_buy, $order['quantity'], $order['rate']);
        }

        //comment s'assurer que tout les ordres sont passés ? annuler ce qui ne sont pas passer directement, update $orders['total_value_btc']
        //si existe pas dans bittrex renvoie false le temps de la créer comment gérer cela ?
        $deposit_address = $business_transaction_sell->get_deposit_address($this->currency_code_sell);

        $business_transaction_buy->withdraw($this->currency_code_buy, $orders['total_quantity'], $deposit_address);

        //comment attendre et s'assurer que la transaction est terminé ?
        $update_order_book_we_sell = $business_transaction_sell->getOrderBook("BTC-" . $this->currency_code_sell)['buy'];

        foreach ($update_order_book_we_sell as $order_we_sell) {
            
            if ($orders['total_quantity'] < $order_we_sell['quantity']) {
                $business_transaction_sell->sell("BTC-" . $this->currency_code_sell, $orders['total_quantity'], $order_we_sell['rate']);
                break;
            }
            $business_transaction_sell->sell("BTC-" . $this->currency_code_sell, $order_we_sell['quantity'], $order_we_sell['rate']);
        
        }*/
        
    }
}
