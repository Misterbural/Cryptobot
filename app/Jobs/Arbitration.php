<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Business\BusinessTransaction;
use Log;

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
        $timestamp = time();

        Log::info("[" . $timestamp . "] buy " . $this->currency_code_buy . " on " . $this->broker_buy . " and sell on " . $this->broker_sell);

        while (true) {
            try
            {
                if (!$business_transaction_buy->is_wallet_available($this->currency_code_buy) || !$business_transaction_sell->is_wallet_available($this->currency_code_sell)) {
                    Log::info("Wallet is offline");
                    return false;
                }
                
            } catch (\Exception $e) {
                sleep(1);
                continue;
            }
            break;
        }

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

        while(true) {
            try {
                $withdraw_fees = $this->broker_buy->get_withdraw_fees($this->currency_code_buy);
            } catch (\Exception $e) {
                sleep(1);
                continue;
            }
            break;
        }
        
        $order_sell_index = 0;
        $order_sell_rate = $order_book_we_sell[$order_sell_index]['rate'];
        $limit_buy_quantity = $order_book_we_sell[$order_sell_index]['quantity'];
        $limit_buy_btc = $balances['BTC']['available'];

        $orders = array(
            "total_quantity" => 0,
            "total_btc_value" => 0,
            "profit" => 0,
            "list" => [],
        );

        $last = false;
        
        for ($i = 0; $i < count($order_book_we_buy); $i++) {

            $order_we_buy = $order_book_we_buy[$i];

            if ($order_we_buy['rate'] > $order_sell_rate * 0.985) {
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
            $orders['profit'] += ($order['quantity'] * $order_sell_rate['rate']) - ($order['quantity'] * $order_we_buy['rate']);
            $orders['list'][] = $order;

            if ($last) {
                break;
            }
        }

        $orders['total_quantity'] -= $withdraw_fees;
        $orders['btc_value'] -= $withdraw_fees * $order_sell_rate;
        $orders['profit'] -= $withdraw_fees * $order_sell_rate;

        Log::info("[" . $timestamp . "] array achat : " . print_r($orders));

        //verifier que meme avec les fees de withdraw on reste rentable
        if ($orders['total_quantity'] < 0 || $orders['profit'] < 0) {
            return false;
        }

        $minimum_order_size_buy = $business_transaction_buy->get_minimum_order_size('BTC-' . $currency_code_buy);
        $minimum_order_size_sell = $business_transaction_sell->get_minimum_order_size('BTC-' . $currency_code_sell);

        if ($orders['btc_value'] < max($minimum_order_size_buy, $minimum_order_size_sell)) {
            Log::info("[" . $timestamp . "] quantité insuffisante a acheté");
            return false;
        }

        //Partie critique achat et vente
        //Passe les ordres d'achats
        $last = count($orders['list']) - 1;
        while(true) {
            try
            {
                $order_id = $business_transaction_buy->buy('BTC-' . $this->currency_code_buy, $orders['total_quantity'], $orders['list'][$last]['rate']);
            } catch (\Exception $e) {
                sleep(1);
                continue;
            }
            break;
        }


        sleep(5);
        //get order, cancel if open, get units filled
        while (true) {
            try
            {
                $order_id = $business_transaction_buy->cancel($order_id, 'BTC-' . $this->currency_code_buy);
            } catch (\Exception $e) {
                sleep(1);
                continue;
            }
            break;
        }

        while (true) {
            try
            {
                $get_order = $business_transaction_buy->get_order($order_id);
            } catch (\Exception $e) {
                sleep(1);
                continue;
            }
            break;
        }
        
        $orders['total_quantity'] = $get_order['actual_quantity'];

        Log::info("[" . $timestamp . "] quantité acheté : " . $orders['total_quantity']);


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
                $business_transaction_buy->withdraw($this->currency_code_buy, $orders['total_quantity'], $deposit_address, $this->broker_sell, $withdraw_fees);
            } catch (\Exception $e) {
                sleep(1);
                continue;
            }
            
            break;
        }

        //attendre fin transfert broker, problème si approximation achat
        while (true) {
            try
            {
                $balance_broker_sell = $business_transaction_sell->get_balances();
            } catch (\Exception $e) {
                sleep(1);
                continue;
            }
            if (!array_key_exists($currency_code_sell, $balances)) {
                sleep(5);
                continue;
            }

            if ($balances[$currency_code_sell]['available'] <= $orders['total_quantity']) {
                sleep(5);
                continue;
            }

            break;
        }

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

        
        $quantity = 0;

        foreach ($update_order_book_we_sell as $order_we_sell) {
            if ($quantity + $order_we_sell['quantity'] > $orders['total_quantity']) {
                $min_rate = $order_we_sell['rate'];
                break;
            }
        } 

        //passe les ordres de vente
        while (true) {
            try
            {
                $order_sell_id = $business_transaction_sell->sell("BTC-" . $this->currency_code_sell, $orders['total_quantity'], $min_rate, $order_id);
            } catch (\Exception $e) {
                sleep(1);
                continue;
            }         
            break
        }
        
        sleep(5);

        Log::info("[" . $timestamp . "] information vente : " print_r($business_transaction_sell->get_order($order_sell_id)));

    }
}
