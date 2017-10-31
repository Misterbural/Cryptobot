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
use Pavlyshyn\Bitfinex\Client;

class BrokerBitfinex implements InterfaceBroker {

    private $bitfinex;

    public function __construct()
    {
        $this->bitfinex = new Client(config('constants.bitfinex')['key'], config('constants.bitfinex')['secret']);
    }


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
        $market = $this->convert_currency_code_to_code_pair($currencies[1]) .  $this->convert_currency_code_to_code_pair($currencies[0]);

        $result = $this->bitfinex->new_order($market, (string)$quantity, (string)$rate, 'bitfinex', 'buy', 'limit');

        if (array_key_exists('error',$result) || $result["result"] == 'error')
        {
            return false;
        }

        return $result['order_id'];
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
        $market = $this->convert_currency_code_to_code_pair($currencies[1]) .  $this->convert_currency_code_to_code_pair($currencies[0]);

        $result = $this->bitfinex->new_order($market, (string)$quantity, (string)$rate, 'bitfinex', 'sell', 'limit');

        if (array_key_exists('error',$result) || $result["result"] == 'error')
        {
            return false;
        }

        return $result['order_id'];
    }

    /**
     * Cancel an order
     * @param string $order_id : The id of the order to cancel
     * @return mixed array|bool : false if fail, else array return by get_order
     */
    public function cancel ($order_id, $market = false)
    {
        $order = $this->get_order($order_id);
        $result = $this->bitfinex->cancel_order($order_id);

        if (array_key_exists('error',$result) || $result["result"] == 'error')
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
        $result = $this->bitfinex->get_order($order_id);

        if (array_key_exists('error',$result) || $result["result"] == 'error')
        {
            return false;
        }

        $order = [];
        
        $order['quantity'] = $result['original_amount'];
        $order['actual_quantity'] = $result['executed_amount'];
        $order['rate'] = $result['price'];
        $order['actual_rate'] = $result['price'];
        $order['fees'] = $result['original_amount'] * $result['price'] * 0.2 / 100;
        $order['actual_fees'] = $result['executed_amount'] * $result['price'] * 0.2 / 100;
        $order_open = new \DateTime();
        $order_open->setTimestamp($result['timestamp']);
        $order['date_open'] = $order_open;
        $order['open'] = $result['is_live'] ? true : false;

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
        $market = $this->convert_currency_code_to_code_pair($currencies[1]) .  $this->convert_currency_code_to_code_pair($currencies[0]);

        $result = $this->bitfinex->get_ticker($market);

        if (array_key_exists('error',$result))
        {
            return false;
        }

        return $result['last_price'];
    }
    
    /**
     * Get ask transaction rate for market
     * @param string $market : The market we want rate
     */
    public function get_market_ask_rate ($market)
    {
        $currencies = explode("-", $market);
        $market = $this->convert_currency_code_to_code_pair($currencies[1]) .  $this->convert_currency_code_to_code_pair($currencies[0]);

        $result = $this->bitfinex->get_ticker($market);

        if (array_key_exists('error',$result))
        {
            return false;
        }

        return $result['ask'];
    }
    
    /**
     * Get bid transaction rate for market
     * @param string $market : The market we want rate
     */
    public function get_market_bid_rate ($market)
    {
        $currencies = explode("-", $market);
        $market = $this->convert_currency_code_to_code_pair($currencies[1]) .  $this->convert_currency_code_to_code_pair($currencies[0]);

        $result = $this->bitfinex->get_ticker($market);

        if (array_key_exists('error',$result))
        {
            return false;
        }

        return $result['bid'];
    }

    /**
     * Get deposit address for a currency
     * @param string $currency : the currency we want address
     * @return string address : address to deposit, if not exist, create but respond ADDRESS_GENERATING until one is available
     */
    public function get_deposit_address ($currency)
    {
        $currency = $this->convert_currency_code_to_currency_name($currency);

        if (!$currency) {
            return false;
        }

        $result = $this->bitfinex->new_deposit($currency, 'exchange');

        if (array_key_exists('error',$result) || $result["result"] == 'error')
        {
            return false;
        }

        return $result['address'];
    }

    /**
    * send currency to another address (broker)
    * @param string $currency : the currency we want to send
    * @param float $quantity  : the quantity of currency we want to send
    * @param string $address : the address to which send the currency
    */
    public function withdraw ($currency, $quantity, $address)
    {
        $currency = $this->convert_currency_code_to_currency_name($currency);

        if (!$currency) {
            return false;
        }

        $result = $this->bitfinex->withdraw($currency, 'exchange', (string)$quantity, $address);

        if ($result[0]['status'] == "error")
        {
            return false;
        }

        return $result[0]['withdrawal_id'];
    }

    /**
    * get quantity of currencies
    */
    public function get_balances()
    {
        $result = $this->bitfinex->get_balances();

        if (array_key_exists('error',$result) || $result["result"] == 'error')
        {
            return false;
        }

        $balances = [];

        foreach ($result['result'] as $balance) {
            if ($balance["type"] != "exchange") {
                continue;
            }
            $balances[$balance['currency']]['available'] = $balance['available'];
            $balances[$balance['currency']]['on_trade'] = $balance['amount'] - $balance['available'];
        }

        return $balances;
    }

    /**
    * get order book for a market
    * @param string $market : the market (BTC-ETH) we want the order book
    */
    public function get_order_book($market)
    {
        $currencies = explode("-", $market);
        $market = $this->convert_currency_code_to_code_pair($currencies[1]) .  $this->convert_currency_code_to_code_pair($currencies[0]);

        $result = $this->bitfinex->get_book($market);
        
        if (array_key_exists('error',$result) || $result["result"] == 'error')
        {
            return false;
        }

        $book = [];

        foreach ($result['bids'] as $order) {
            $add_to_book = [];
            $add_to_book['quantity'] = $order["amount"];
            $add_to_book['rate'] = $order["price"];
            $book['buy'][] = $add_to_book;
        }

        foreach ($result['asks'] as $order) {
            $add_to_book = [];
            $add_to_book['quantity'] = $order["amount"];
            $add_to_book['rate'] = $order["price"];
            $book['sell'][] = $add_to_book;
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
        $currency = $this->convert_currency_code_to_currency_name($currency);

        if (!$currency) {
            return false;
        }

        $result = $this->bitfinex->new_deposit($currency, 'exchange');

        if (array_key_exists('error',$result) || $result["result"] == 'error')
        {
            return false;
        }

        return true;
    }

    /**
    * ask to the broker the fees of withdraw for a currency
    * @param string $currency : code of currency
    * @return floar
    */
    public function get_withdraw_fees($currency)
    {
        switch($currency) {
            case 'BTC':
                return 0.0005;
            case 'LTC':
                return 0.001;
            case 'ETH':
                return 0.01;
            case 'ETC':
                return 0.01;
            case 'ZEC':
                return 0.001;
            case 'DASH':
                return 0.01;
            case 'IOTA':
                return 0;
            case 'EOS':
                return 0.1;
            case 'SAN':
                return 0.1;
            case 'OMG':
                return 0.1;
            case 'BCH':
                return 0.0005;
            case 'NEO':
                return 0;
            case 'QTM':
                return 0.01;
            case 'AVT':
                return 0.1;
            default :
                return false;
        }
    }

    /**
    * ask to the broker the minimum order size in BTC
    * @param string $market : market we want 
    * @return float
    */
    public function get_minimum_order_size($market)
    {
        $currencies = explode("-", $market);
        $pair = strtolower($this->convert_currency_code_to_code_pair($currencies[1]) .  $this->convert_currency_code_to_code_pair($currencies[0]));

        $result = $this->bitfinex->get_symbols_details();
        
        if (array_key_exists('error',$result))
        {
            return false;
        }

        foreach ($result as $result_pair) {
            if ($pair != $result_pair['pair']) {
                continue;
            }

            while (true) {
                try {
                    $price = $this->get_market_ask_rate($market);
                } catch (\Exception $e) {
                    sleep(1);
                    continue;
                }
                break;
            }
            
            return $result_pair['minimum_order_size'] * $price;
        }
        return false;
    }

    private function convert_currency_code_to_currency_name($currency_code)
    {
        switch ($currency_code) {
            case 'BTC':
                return 'bitcoin';
            case 'LTC':
                return 'litecoin';
            case 'ETH':
                return 'ethereum';
            case 'ETC':
                return 'ethereumc';
            case 'ZEC':
                return 'zcash';
            case 'DASH':
                return 'dash';
            case 'IOTA':
                return 'iota';
            case 'EOS':
                return 'eos';
            case 'SAN':
                return 'santiment';
            case 'OMG':
                return 'omisego';
            case 'BCH':
                return 'bcash';
            case 'NEO':
                return 'neo';
            case 'QTM':
                return 'qtum';
            case 'AVT':
                return 'aventus';
            default :
                return false;
        }
    }

    private function convert_currency_code_to_code_pair($currency_code)
    {
        switch ($currency_code) {
            case 'DASH':
                return 'DSH';
            case 'IOTA':
                return 'IOT';
            default:
                return $currency_code;
        }   
    }


    
}