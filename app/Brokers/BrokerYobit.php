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
use Pepijnolivier\Yobit\Yobit;

class BrokerYobit implements InterfaceBroker {

	/**
     * Place an order buy
     * @param string $market : The market to buy on
     * @param float $quantity : The quantity to buy
     * @param float $rate : The rate to buy (ex : if $market = BTC-BCC & $quantity = 2.0 & $rate = 0.5, you will spend 1BTC and get 2BCC)
     * @return mixed : Order order id if the order have been placed successfully, false if not
     */
    public function buy ($market, $quantity, $rate)
    {
    	$currencies = explode("-", $market);
    	$market = strtolower($currencies[1] . "_" . $currencies[0]);

        $result = Yobit::buy($market, $rate, $quantity);

        if ($result['success'] == false)
        {
            return false;
        }

        return $result['return']['order_id'];
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
    	$currencies = explode("-", $market);
    	$market = strtolower($currencies[1] . "_" . $currencies[0]);

        $result = Yobit::sell($market, $rate, $quantity);

        if ($result['success'] == false)
        {
            return false;
        }

        return $result['return']['order_id'];
    }

    /**
     * Cancel an order
     * @param string $order_id : The id of the order to cancel
     * @return mixed array|bool : false if fail, else array return by get_order
     */
    public function cancel ($order_id, $market = false)
    {
        $order = $this->get_order($order_id);
        $cancel = Yobit::cancelOrder($order_id);

        if ($result['success'] == false)
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
        $result = Yobit::getOrderInfo($order_id);

        if ($result['success'] == false)
        {
            return false;
        }

        $order = [];
        
        $order['quantity'] = $result['return']['start_amount'];
        $order['actual_quantity'] = $result['return']['Quantity'] - $result['return']['amount'];
        $order['rate'] = $result['return']['rate'];
        $order['actual_rate'] = $result['return']['rate'];

        $type = $result['return']['Type'];
        $order['fees'] = $this->compute_fees($type, $result['return']['start_amount'], $result['return']['rate'];
 

        $order['actual_fees'] = ($result['return']['start_amount'] - $result['return']['amount']) * $rate * 0.2 / 100;
        
        $date_open = new \DateTime();
        $date_open->setTimestamp($result['return']['timestamp_created']);
        $order['date_open'] = $date_open->format('Y-m-d i:m:s.u');
        
        $order['open'] =  false;
        if ($result['return']['status'] == 0) {
        	$order['open'] =  true;
    	}

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
        return $quantity * $rate * 0.2 / 100;
    }

    /**
     * Get last transaction rate for market
     * @param string $market : The market we want rate
     */
    public function get_market_last_rate ($market)
    {
    	$currencies = explode("-", $market);
    	$market = strtolower($currencies[1] . "_" . $currencies[0]);

        $result = Yobit::getTicker($market);

        return $result[$market]['last'];
    }
    
    /**
     * Get ask transaction rate for market
     * @param string $market : The market we want rate
     */
    public function get_market_ask_rate ($market)
    {
    	$currencies = explode("-", $market);
    	$market = strtolower($currencies[1] . "_" . $currencies[0]);

        $result = Yobit::getTicker($market);

        return $result[$market]['sell'];
    }
    
    /**
     * Get bid transaction rate for market
     * @param string $market : The market we want rate
     */
    public function get_market_bid_rate ($market)
    {
    	$currencies = explode("-", $market);
    	$market = strtolower($currencies[1] . "_" . $currencies[0]);

        $result = Yobit::getTicker($market);

        return $result[$market]['buy'];
    }

    /**
     * Get deposit address for a currency
     * @param string $currency : the currency we want address
     */
    public function get_deposit_address ($currency)
    {
        $result = Yobit::getDepositAddress($currency);

        if ($result['success'] == false)
        {
            return false;
        }

        return $result['return']['address'];
    }

    /**
    * send currency to another address (broker)
    * @param string $currency : the currency we want to send
    * @param float $quantity  : the quantity of currency we want to send
    * @param string $address : the address to which send the currency
    */
    public function withdraw ($currency, $quantity, $address)
    {
        $result = Yobit::withdraw($currency, $quantity, $address);

        if ($result['success'] == false)
        {
            return false;
        }

        return true;
    }

    /**
    * get quantity of currencies
    */
    public function getBalances()
    {
        $result = Yobit::getTradeInfo();

        if ($result['success'] == false)
        {
            return false;
        }

        $balances = [];

        foreach ($result['result']['funds'] as $currency => $balance) {
            $balances[$currency]]['available'] = $balance;
        }

        foreach ($result['result']['funds_incl_orders'] as $currency => $balance) {
            $balances[$currency]]['on_trade'] = $balance - $balances[$currency]]['available'];
        }
        
        return $balances;
    }

    /**
    * get order book for a market
    * @param string $market : the market (BTC-ETH) we want the order book
    */
    public function getOrderBook($market)
    {
        $currencies = explode("-", $market);
        $market = strtolower($currencies[1] . "_" . $currencies[0]);

        $result = Yobit::getDepth($market);

        if ($result['success'] == false)
        {
            return false;
        }

        $book = [];

        if (array_key_exists('bids', $result[$market])) {
            foreach ($result[$market]['bids'] as $order) {
                $add_to_book = [];
                $add_to_book['quantity'] = $order[1];
                $add_to_book['rate'] = $order[0];
                $book['buy'][] = $order;
            }
        }

        if (array_key_exists('asks',$result[$market])) {
            foreach ($result[$market]['asks'] as $order) {
                $add_to_book = [];
                $add_to_book['quantity'] = $order[1];
                $add_to_book['rate'] = $order[0];
                $book['sell'][] = $order;
            }
        }

        return $book;
    }

}