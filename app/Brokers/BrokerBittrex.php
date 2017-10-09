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
use Pepijnolivier\Bittrex\Bittrex;

class BrokerBittrex implements InterfaceBroker {

    /**
     * Place an order buy
     * @param string $market : The market to buy on
     * @param float $quantity : The quantity to buy
     * @param float $rate : The rate to buy (ex : if $market = BTC-BCC & $quantity = 2.0 & $rate = 0.5, you will spend 1BTC and get 2BCC)
     * @return mixed : Order uuid if the order have been placed successfully, false if not
     */
    public function buy ($market, $quantity, $rate)
    {
        $result = Bittrex::buyLimit($market, $quantity, $rate);

        if ($result['success'] == false)
        {
            return false;
        }

        return $result['result']['uuid'];
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
        $result = Bittrex::sellLimit($market, $quantity, $rate);

        if ($result['success'] == false)
        {
            return false;
        }

        return $result['result']['uuid'];
    }

    /**
     * Cancel an order
     * @param string $order_id : The id of the order to cancel
     * @return mixed array|bool : false if fail, else array return by get_order
     */
    public function cancel ($order_id, $market = false)
    {
        $order = $this->get_order($order_id);
        $cancel = Bittrex::cancelOrder($order_id);

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
        $result = Bittrex::getOrder($order_id);

        if ($result['success'] == false)
        {
            return false;
        }

        $order = [];
        
        $order['quantity'] = $result['result']['Quantity'];
        $order['actual_quantity'] = $result['result']['Quantity'] - $result['result']['QuantityRemaining'];
        $order['rate'] = $result['result']['Limit'];
        $order['actual_rate'] = $result['result']['PricePerUnit'];

        if (!$result['result']['CommissionReserved'])
        {
            $type = ($result['result']['Type'] == 'LIMIT_BUY' ? 'buy' : 'sell');
            $order['fees'] = $this->compute_fees($type, $result['result']['Quantity'], $result['result']['Limit']);
        }
        else
        {
            $order['fees'] = $result['result']['CommissionReserved'];
        }

        $order['actual_fees'] = $result['result']['CommissionPaid'];
        $order['date_open'] = $result['result']['Opened'];
        $order['open'] = $result['result']['IsOpen'] ? true : false;

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
        $result = Bittrex::getTicker($market);

        if ($result['success'] == false)
        {
            return false;
        }

        return $result['result']['Last'];
    }
    
    /**
     * Get ask transaction rate for market
     * @param string $market : The market we want rate
     */
    public function get_market_ask_rate ($market)
    {
        $result = Bittrex::getTicker($market);

        if ($result['success'] == false)
        {
            return false;
        }

        return $result['result']['Ask'];
    }
    
    /**
     * Get bid transaction rate for market
     * @param string $market : The market we want rate
     */
    public function get_market_bid_rate ($market)
    {
        $result = Bittrex::getTicker($market);

        if ($result['success'] == false)
        {
            return false;
        }

        return $result['result']['Bid'];
    }

    /**
     * Get deposit address for a currency
     * @param string $currency : the currency we want address
     * @return string address : address to deposit, if not exist, create but respond ADDRESS_GENERATING until one is available
     */
    public function get_deposit_address ($currency)
    {
        $result = Bittrex::getDepositAddress($currency);

        if ($result['success'] == false)
        {
            return false;
        }

        return $result['result']['Address'];
    }

    /**
    * send currency to another address (broker)
    * @param string $currency : the currency we want to send
    * @param float $quantity  : the quantity of currency we want to send
    * @param string $address : the address to which send the currency
    */
    public function withdraw ($currency, $quantity, $address)
    {
        $result = Bittrex::withdraw($currency, $quantity, $address);

        if ($result['success'] == false)
        {
            return false;
        }

        return $result['result']['uuid'];
    }
}
