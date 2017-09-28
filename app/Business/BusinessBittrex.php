<?php

namespace App\Business;

/**
* Class Candle
* @package App\Business
*
* Wrapper to bittrex api
* Place or cancel orders and insert datas in cryptobot tables
*/

use Pepijnolivier\Bittrex\Bittrex;

class BusinessBittrex {

    public $strategy;

    /**
     * Constructeur
     * @param string $strategy : Name of strategy invoking class
     */
    public _construct ($strategy = 'unknown')
    {
        $this->strategy = $strategy;
    }

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

        $transaction = new Transaction();
        $transaction->strategy = $this->strategy;
        $transaction->currencies = $market;
        $transaction->quantity = $quantity;
        $transaction->rate = $rate;
        $transaction->fees = $this->compute_fees('buy', $quantity, $rate);
        $transaction->status = 'open';
        $transaction->type = 'buy';
        $transaction->order_id = $result['result']['uuid'];
        $transaction->remaining = $quantity;

        $transaction->save();

        return $result['result']['uuid'];
    }

    /**
     * Calcul fees
     * @param string $type : 'buy' or 'Sell'
     * @param float $quantity : Quantity to buy or sell
     * @param float $rate : Rate of buying
     */
    private function compute_fees ($type, $quantity, $rate)
    {
        return $quantity * $rate * 0.25 / 100;
    }
}
