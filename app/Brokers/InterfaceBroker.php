<?php

namespace App\Brokers;

/**
* Class Candle
* @package App\Business
*
* Broker wrapper interface
*/

interface InterfaceBroker {

    /**
     * Place an order buy
     * @param string $market : The market to buy on
     * @param float $quantity : The quantity to buy
     * @param float $rate : The rate to buy (ex : if $market = BTC-BCC & $quantity = 2.0 & $rate = 0.5, you will spend 1BTC and get 2BCC)
     * @return mixed : Order uuid if the order have been placed successfully, false if not
     */
    public function buy ($market, $quantity, $rate);

    /**
     * Place an order sell
     * @param string $market : The market to sell on
     * @param float $quantity : The quantity to sell
     * @param float $rate : The rate to sell
     * @return mixed : Order uuid if the order have been placed successfully, false if not
     */
    public function sell ($market, $quantity, $rate);

    /**
     * Cancel an order
     * @param string $order_id : The id of the order to cancel
     * @return mixed array|bool : false if fail, else array return buy get_order
     */
    public function cancel ($order_id, $market = false);

    /**
     * Get an order
     * @param string $order_id : The id of the order to get
     * @return mixed array|bool : false if fail, else array with ['quantity', 'actual_quantity', 'rate', 'actual_rate', 'fees', 'actual_fees', 'date_open', 'open']
     */
    public function get_order ($order_id);

    /**
     * Calcul fees
     * @param string $type : 'buy' or 'Sell'
     * @param float $quantity : Quantity to buy or sell
     * @param float $rate : Rate of buying
     * @return float : The fees for this transaction
     */
    public function compute_fees ($type, $quantity, $rate);
    
    /**
    * Return fees rate
    */
    public function get_fees_rate(); 
        
    /**
     * Get last transaction rate for market
     * @param string $market : The market we want rate
     * @return float : The rate for the last transaction on broker for this market
     */
    public function get_market_last_rate ($market);
    
    /**
     * Get ask transaction rate for market
     * @param string $market : The market we want rate
     * @return mixed float|bool : False if error, else the rate on ask for the market
     */
    public function get_market_ask_rate ($market);
    
    /**
     * Get bid transaction rate for market
     * @param string $market : The market we want rate
     * @return mixed float|bool : False if error, else the rate on bid for the market
     */
    public function get_market_bid_rate ($market);

    /**
    * Get deposit address for a currency
    * @param string $currency : the currency we want address
    * @return mixed bool|string : false if error, else deposit address
    */
    public function get_deposit_address ($currency);

    /**
    * send currency to another address (broker)
    * @param string $currency : the currency we want to send
    * @param float $quantity  : the quantity of currency we want to send
    * @param string $address : the address to which send the currency
    * @return mixed string|false : false if withdraw fail, id of the withdraw if success
    */
    public function withdraw ($currency, $quantity, $address);

    /**
    * get quantity of each currencies on our balances
    * @return mixed bool|array : Bool if error, else ['currency_code' => ['available' => xx, 'on_trade' => xx], ...]
    */
    public function get_balances();

    /**
    * get order book for a market
    * @param string $market : the market (BTC-ETH) we want the order book
    * @return array : order book order by rate
    */
    public function get_order_book($market);

    /**
    * ask the broker if a wallet is available or not
    * @param string $currency : code of currency
    * @return bool
    */
    public function is_wallet_available($currency);

    /**
    * ask to the broker the fees of withdraw for a currency
    * @param string $currency : code of currency
    * @return float
    */
    public function get_withdraw_fees($currency);

    /**
    * ask to the broker the minimum order size in BTC
    * @param string $market : market we want 
    * @return float
    */
    public function get_minimum_order_size($market);
}
