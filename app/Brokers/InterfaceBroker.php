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
     * @param (optionnal) float $link_to_order : The uuid of the order to link to this one
     * @return mixed : Order uuid if the order have been placed successfully, false if not
     */
    public function sell ($market, $quantity, $rate);

    /**
     * Cancel an order
     * @param string $order_id : The id of the order to cancel
     * @return mixed array|bool : false if fail, else array return buy get_order
     */
    public function cancel ($order_id);

    /**
     * Get an order
     * @param string $order_id : The id of the order to get
     * @return mixed array|bool : false if fail, else array with ['quantity', 'actual_quantity', 'rate', 'actual_rate', 'fees', 'actual_fees', 'date_open', 'open']
     */
    public function get_order ($order_id, $market = false);

    /**
     * Calcul fees
     * @param string $type : 'buy' or 'Sell'
     * @param float $quantity : Quantity to buy or sell
     * @param float $rate : Rate of buying
     */
    public function compute_fees ($type, $quantity, $rate);
    
    /**
     * Get last transaction rate for market
     * @param string $market : The market we want rate
     */
    public function get_market_last_rate ($market);
    
    /**
     * Get ask transaction rate for market
     * @param string $market : The market we want rate
     */
    public function get_market_ask_rate ($market);
    
    /**
     * Get bid transaction rate for market
     * @param string $market : The market we want rate
     */
    public function get_market_bid_rate ($market);

    /**
    * Get deposit address for a currency
    * @param string $currency : the currency we want address
    */
    public function get_deposit_address ($currency);

    /**
    * send currency to another address (broker)
    * @param string $currency : the currency we want to send
    * @param float $quantity  : the quantity of currency we want to send
    * @param string $address : the address to which send the currency
    */
    public function withdraw ($currency, $quantity, $address);
}
