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
        $order['date_open'] = new \DateTime($result['result']['Opened']);
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
     * Return fees rate
     */ 
    public function get_fees_rate()
    {
        return 0.25;
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
     * @return mixed bool|string : false if error, else deposit address
     */
    public function get_deposit_address ($currency)
    {
        $result = Bittrex::getDepositAddress($currency);

        if ($result['success'] == false)
        {
            return false;
        }

        //If address not already create, return 'ADDRESS_GENERATING', so we retry
        $retry = 0;
        while ($result['message'] == 'ADDRESS_GENERATING' && $retry < 10)
        {
            sleep(1);
            $result = Bittrex::getDepositAddress($currency);
            $retry ++;
        }

        if ($result['message'] == 'ADDRESS_GENERATING')
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

    /**
    * get quantity of currencies
    */
    public function get_balances()
    {
        $result = Bittrex::getBalances();

        if ($result['success'] == false)
        {
            return false;
        }

        $balances = [];

        foreach ($result['result'] as $balance) {
            $balances[strtoupper($balance['Currency'])]['available'] = $balance['Available'];
            $balances[strtoupper($balance['Currency'])]['on_trade'] = $balance['Balance'] - $balance['Available'];
        }

        return $balances;
    }

    /**
    * get order book for a market
    * @param string $market : the market (BTC-ETH) we want the order book
    */
    public function get_order_book($market)
    {
        $result = Bittrex::getOrderBook($market, 'both');

        if ($result['success'] == false)
        {
            return false;
        }

        $book = [];

        if (array_key_exists('buy',$result['result'])) {
            foreach ($result['result']['buy'] as $order) {
                $add_to_book = [];
                $add_to_book['quantity'] = $order["Quantity"];
                $add_to_book['rate'] = $order["Rate"];
                $book['buy'][] = $add_to_book;
            }
        }

        if (array_key_exists('sell',$result['result'])) {
            foreach ($result['result']['sell'] as $order) {
                $add_to_book = [];
                $add_to_book['quantity'] = $order["Quantity"];
                $add_to_book['rate'] = $order["Rate"];
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
        $result = Bittrex::getCurrencies();

        if ($result['success'] == false)
        {
            return false;
        }

        foreach ($result['result'] as $result_currency) {
            if ($result_currency['Currency'] != $currency) {
                continue;
            }

           return $result_currency['IsActive'];
        }
        return false;
    }

    /**
    * ask to the broker the fees of withdraw for a currency
    * @param string $currency : code of currency
    * @return floar
    */
    public function get_withdraw_fees($currency)
    {
        $result = Bittrex::getCurrencies();

        if ($result['success'] == false)
        {
            return false;
        }
        foreach ($result['result'] as $result_currency) {
            if ($result_currency['Currency'] != $currency) {
                continue;
            }

           return $result_currency['TxFee'];
        }
        return false;
    }

    /**
    * ask to the broker the minimum order size in BTC
    * @param string $market : market we want 
    * @return float
    */
    public function get_minimum_order_size($market)
    {
        return 0.0005;
    }
}
