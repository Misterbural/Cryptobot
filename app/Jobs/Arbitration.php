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
        $business_transaction_buy = new BusinessTransaction($this->broker_buy);
        $business_transaction_sell = new BusinessTransaction($this->broker_sell);

        /////////////////////////////////////////////////////
        //         TODO VERIFIER MAINTENANCE WALLET        //
        /////////////////////////////////////////////////////

        //Récupération des orders books d'achat et de vente
        //Et de la balance de crypto sur la plateforme d'achat pour récupérer la quantité de BTC dispo
        while (true) {
            try
            {
                $order_book_we_buy = $business_transaction_buy->get_order_book("BTC-" . $this->currency_code_buy)['sell'];
                $order_book_we_sell = $business_transaction_sell->get_order_book("BTC-" . $this->currency_code_sell)['buy'];
                $balances = $business_transaction_buy->get_balances();
            } catch (\Exception $e) {
                sleep(1);
                continue;
            }
            break;
        }
        

        if (!array_key_exists('BTC', $balances)) {
            return false;
        }

        if ($balances['BTC']['available'] == 0 || $balances['BTC']['available'] == null) {
            return false;
        }
        
        $order_sell_index = 0;
        $order_sell_rate = $order_book_we_sell[$order_sell_index]['rate'];
        $limit_buy_quantity = $order_book_we_sell[$order_sell_index]['quantity'];
        $limit_buy_btc = $balances['BTC']['available'];

        $orders = array(
            "total_quantity" => 0,
            "total_btc_value" => 0,
            "list" => [],
        );

        $last = false;
        
        for ($i = 0; $i < count($order_book_we_buy); $i++) {

            $order_we_buy = $order_book_we_buy[$i];

            if ($order_we_buy['rate'] > $order_sell_rate * 0.975) {
                break;
            }

            $order = [];

            $order['quantity'] = $order_we_buy['quantity'];
            $order['rate'] = $order_we_buy['rate'];
            $order['btc_value'] = $order['quantity'] * $order['rate'] + $business_transaction_buy->compute_fees('buy', $order['quantity'], $order['rate']);

            if ($orders['total_quantity'] + $order['quantity'] > $limit_buy_quantity) {
                $order['quantity'] = $limit_buy_quantity - $orders['total_quantity'];
                $order['btc_value'] = $order['quantity'] * $order['rate'] + $business_transaction_buy->compute_fees('buy', $order['quantity'], $order['rate']);
                
                //update order_sell_rate & limit_buy_quantity
                $order_sell_index += 1;
                $order_sell_rate = $order_book_we_sell[$order_sell_index]['rate'];
                $limit_buy_quantity += $order_book_we_sell[$order_sell_index]['quantity'];
                
                $order_book_we_buy[$i]['quantity'] -= $order['quantity'];
                $i--;
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

        //On peut récupérer les fees de withdraw a priori Poloniex et Bittrex getCurrencies -> TxFee
        $benef = $orders['total_quantity'] * $order_book_we_sell[0]['rate'] - $orders['total_btc_value'];
        echo "Bénéfice total sur " . $this->currency_code_buy . " : " . $benef . " BTC soit " . $benef*4500 . "€\n";

        //Partie critique achat et vente

        //Passe les ordres d'achats
        /*for ($i = 0; $i < count($orders['list']); $i++) {
            $order = $orders['list'][$i];
            try
            {
                $business_transaction_buy->buy('BTC-' . $this->currency_code_buy, $order['quantity'], $order['rate']);
            } catch (\Exception $e) {
                sleep(1);
                $i--;
            }
        }

        //comment s'assurer que tout les ordres sont passés ? annuler ce qui ne sont pas passer directement, update $orders['total_value_btc']

        //get deposit address
        while (true) {
            try
            {
                $deposit_address = $business_transaction_sell->get_deposit_address($this->currency_code_sell);
            } catch (\Exception $e) {
                sleep(1);
                continue;
            }
            if (!$deposit_address) {
                sleep(5);
                continue;
            }
            break;
        }
        
        //Transaction de la plateforme d'achat vers celle de vente
        while (true) {
            try
            {
                $business_transaction_buy->withdraw($this->currency_code_buy, $orders['total_quantity'], $deposit_address);
            } catch (\Exception $e) {
                sleep(1);
                continue;
            }
            
            break;
        }

        //comment attendre et s'assurer que la transaction est terminé ? get quantity du wallet si >= quantité acheté good problème si approximation achat


        //mise a jour de l'order book de la plateforme de vente 
        while (true) {
            try
            {
                $update_order_book_we_sell = $business_transaction_sell->get_order_book("BTC-" . $this->currency_code_sell)['buy'];
            } catch (\Exception $e) {
                sleep(1);
                continue;
            }
            
            break;
        }

        //passe les ordres de vente
        for ($i = 0; $i < count($update_order_book_we_sell); $i++) {
            $order_we_sell = $update_order_book_we_sell[$i]
            try
            {
                if ($orders['total_quantity'] < $order_we_sell['quantity']) {
                    $business_transaction_sell->sell("BTC-" . $this->currency_code_sell, $orders['total_quantity'], $order_we_sell['rate']);
                    break;
                }
                $business_transaction_sell->sell("BTC-" . $this->currency_code_sell, $order_we_sell['quantity'], $order_we_sell['rate']);
            } catch (\Exception $e) {
                sleep(1);
                $i--;
            }         
        
        }*/
        
    }
}