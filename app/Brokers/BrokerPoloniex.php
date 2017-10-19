<?php

namespace App\Brokers;

/**
* Class Candle
* @package App\Business
*
* Wrapper to bittrex api
* Place or cancel orders and insert datas in cryptobot tables
*/

use App\Brokers\InterfaceBroker;
use Pepijnolivier\Poloniex\Poloniex;

class BrokerPoloniex implements InterfaceBroker {

	/**
     * Place an order buy
     * @param string $market : The market to buy on
     * @param float $quantity : The quantity to buy
     * @param float $rate : The rate to buy (ex : if $market = BTC-BCC & $quantity = 2.0 & $rate = 0.5, you will spend 1BTC and get 2BCC)
     * @return mixed : Order order id if the order have been placed successfully, false if not
     */
    public function buy ($market, $quantity, $rate)
    {
    	$market = str_replace("-", "_", $market);

        $result = Poloniex::buy($market, $rate, $quantity);

        if (array_key_exists('error',$result))
        {
            return false;
        }

        return $result['orderNumber'];
    }

    /**
     * Place an order sell
     * @param string $market : The market to sell on
     * @param float $quantity : The quantity to sell
     * @param float $rate : The rate to sell
     * @return mixed : Order uuid if the order have been placed successfully, false if not
     */
    public function sell ($market, $quantity, $rate)
    {
    	$market = str_replace("-", "_", $market);

        $result = Poloniex::sell($market, $rate, $quantity);

        if (array_key_exists('error',$result))
        {
            return false;
        }

        return $result['orderNumber'];
    }

    /**
     * Cancel an order
     * @param string $order_id : The id of the order to cancel
     * @return mixed array|bool : false if fail, else array return by get_order
     */
    public function cancel ($order_id, $market = false)
    {
        if (!$market) {
            return false;
        }

        $order = $this->get_order($order_id);
        $cancel = Poloniex::cancelOrder($market, $order_id);

        if (array_key_exists('error',$result))
        {
            return false;
        }

        return $order;
    }

    /**
     * Get an order
     * @param string $order_id : The id of the order to get
     * @return mixed array|bool : false if fail, else array with ['quantity', 'actual_remaining', 'rate', 'actual_rate', 'fees', 'fees_paid', 'date_open', 'open']
     */
    public function get_order ($order_id)
    {
        $result = Poloniex::getOrderTrades($order_id);

        if (array_key_exists('error',$result))
        {
            return false;
        }

        $order = [];
        
        $order['quantity'] = $result[0]['amount'];
        $order['actual_quantity'] = $result[0]['amount'];
        $order['rate'] = $result[0]['rate'];
        $order['actual_rate'] = $result[0]['rate'];
        $order['actual_fees'] = $order['actual_quantity'] * 0.25 /100;
        $order['date_open'] = new \DateTime($result[0]['date']);


        $order['open'] = false;



        $open_orders = Poloniex::getOpenOrders($result[0]['currencyPair']);

        foreach ($open_orders as $open_order) {
            if ($order_id == $open_order['orderNumber']) {
                $order['open'] = true;
            }
        }
        
        if (!$order['open']) {
            return $order;
        }
        
        $order['actual_quantity'] = 0;
        $order['actual_fees'] = 0;

        return $order;


        
    }

    /**
     * Calcul fees
     * @param string $type : 'buy' or 'Sell'
     * @param float $quantity : Quantity to buy or sell
     * @param float $rate : Rate of buying
     */
    public function compute_fees ($type, $quantity, $rate)
    {
        return $quantity * $rate * 0.25 / 100;
    }

    /**
     * Get last transaction rate for market
     * @param string $market : The market we want rate
     */
    public function get_market_last_rate ($market)
    {
    	$market = str_replace("-", "_", $market);

        $result = Poloniex::getTicker($market);

        return $result['last'];
    }
    
    /**
     * Get ask transaction rate for market
     * @param string $market : The market we want rate
     */
    public function get_market_ask_rate ($market)
    {
    	$market = str_replace("-", "_", $market);

        $result = Poloniex::getTicker($market);

        return $result['lowestAsk'];
    }
    
    /**
     * Get bid transaction rate for market
     * @param string $market : The market we want rate
     */
    public function get_market_bid_rate ($market)
    {
    	$market = str_replace("-", "_", $market);

        $result = Poloniex::getTicker($market);

        return $result['highestBid'];
    }

    /**
     * Get deposit address for a currency
     * @param string $currency : the currency we want address
     */
    public function get_deposit_address ($currency)
    {
        $result = Poloniex::getDepositAddresses();

        if (array_key_exists('error',$result))
        {
            return false;
        }

        if (!array_key_exists($currency, $result))
        {
            $address = $this->generate_new_deposit_address ($currency);

            if (!$address) 
            {
                return false;
            }

            return $address;
        }

        return $result[$currency];
    }

    /**
    * send currency to another address (broker)
    * @param string $currency : the currency we want to send
    * @param float $quantity  : the quantity of currency we want to send
    * @param string $address : the address to which send the currency
    */
    public function withdraw ($currency, $quantity, $address)
    {
        $result = Poloniex::withdraw($currency, $quantity, $address);

        if (array_key_exists('error',$result))
        {
            return false;
        }

        return true;
    }


    private function generate_new_deposit_address ($currency)
    {
        $result = Poloniex::generateNewAddress($currency);

        if (array_key_exists('error',$result))
        {
            return false;
        }

        return $result['response'];
    }

    /**
    * get quantity of currencies
    */
    public function get_balances()
    {
        $result = Poloniex::getCompleteBalances();

        if (array_key_exists('error',$result))
        {
            return false;
        }

        $balances = [];

        foreach ($result as $currency => $balance) {
            $balances[$currency]['available'] = $balance['available'];
            $balances[$currency]['on_trade'] = $balance['onOrders'];
        }
        
        return $balances;
    }

    /**
    * get order book for a market
    * @param string $market : the market (BTC-ETH) we want the order book
    */
    public function get_order_book($market)
    {
        $market = str_replace("-", "_", $market);

        $result = Poloniex::getOrderBook($market);

        if (array_key_exists('error',$result))
        {
            return false;
        }

        $book = [];

        if (array_key_exists('bids', $result)) {
            foreach ($result['bids'] as $order) {
                $add_to_book = [];
                $add_to_book['quantity'] = $order[1];
                $add_to_book['rate'] = $order[0];
                $book['buy'][] = $add_to_book;
            }
        }

        if (array_key_exists('asks',$result)) {
            foreach ($result['asks'] as $order) {
                $add_to_book = [];
                $add_to_book['quantity'] = $order[1];
                $add_to_book['rate'] = $order[0];
                $book['sell'][] = $add_to_book;
            }
        }

        return $book;
    }

    /**
    * ask the broker if a wallet is available or not
    * @param string $currency : code of currency
    * @return bool
    */
    public function is_wallet_available($currency)
    {
        $result = Poloniex::getCurrencies();

        if (array_key_exists('error',$result))
        {
            return false;
        }

        if ($result[$currency]['disabled'] == 1 || $result[$currency]['delisted'] == 1) {
            return false;
        }

        return true;
    }
}