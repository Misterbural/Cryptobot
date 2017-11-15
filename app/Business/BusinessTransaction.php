<?php

namespace App\Business;

/**
* Class Candle
* @package App\Business
*
* Make buy or sell operations for a broker and a currency
*
* Write transactions in database and call broker api wrapper to execute them
*/

use App\Business\BusinessWallet;
use App\Brokers;
use App\Models\Transaction;

class BusinessTransaction {

    public $strategy;
    public $broker_name;
    public $broker;

    /**
     * Constructeur
     * @param string $broker_name : Name of the broker, used to call the good api wrapper
     * @param string $strategy : Name of strategy used to determine transactions
     */
    public function __construct ($broker_name, $strategy = 'unknown')
    {
        $this->broker_name = $broker_name;
        $this->strategy = $strategy;
        
        $broker_wrapper_name = '\\App\\Brokers\\Broker' . str_replace(' ', '', mb_convert_case($broker_name, MB_CASE_TITLE));
        $this->broker = new $broker_wrapper_name();
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
        $business_wallet = new BusinessWallet($this->broker_name);

        $market_sell = explode('-', $market)[0];
        $market_buy = explode('-', $market)[1];

        //Compute quantity to spend and add fees
        $fees = $this->compute_fees('buy', $quantity, $rate);
        $quantity_to_spend = $quantity * $rate + $fees;
        
        //Check if we have enough to buy
        $enough_to_buy = $business_wallet->check_can_spend($market_sell, $quantity_to_spend);
        if (!$enough_to_buy)
        {
            return false;
        }

        //Update wallet in prevision of the order
        $business_wallet->trade($market_sell, $quantity_to_spend);

        //Try to pass the order
        $order_id = $this->broker->buy($market, $quantity, $rate);
        
        //If buy fail, revert wallet trade operation
        if (!$order_id)
        {
            $business_wallet->untrade($market_sell, $quantity_to_spend);
            return false;
        }

        //Save transaction in db
        $transaction = new Transaction();
        $transaction->strategy = $this->strategy;
        $transaction->currencies = $market;
        $transaction->quantity = $quantity;
        $transaction->rate = $rate;
        $transaction->fees = $fees;
        $transaction->status = 'open';
        $transaction->type = 'buy';
        $transaction->order_id = $order_id;
        $transaction->broker = $this->broker_name;

        $transaction->save(); 

        return $order_id;
    }

    /**
     * Place an order sell
     * @param string $market : The market to sell on
     * @param float $quantity : The quantity to sell
     * @param float $rate : The rate to sell
     * @param (optionnal) float $link_to_order : The uuid of the order to link to this one
     * @return mixed : Order uuid if the order have been placed successfully, false if not
     */
    public function sell ($market, $quantity, $rate, $link_to_order = false)
    {
        $business_wallet = new BusinessWallet($this->broker_name);

        $market_buy = explode('-', $market)[0];
        $market_sell = explode('-', $market)[1];

        //Compute quantity to spend and add fees
        $fees = $this->compute_fees('sell', $quantity, $rate);
        $quantity_to_spend = $quantity;
        
        //Check if we have enough to buy
        $enough_to_buy = $business_wallet->check_can_spend($market_sell, $quantity_to_spend);
        if (!$enough_to_buy)
        {
            return false;
        }

        //Update wallet in prevision of the order
        $business_wallet->trade($market_sell, $quantity_to_spend);

        //Try to pass the order
        $order_id = $this->broker->sell($market, $quantity, $rate);

        //If sell fail, revert wallet trade operation
        if (!$order_id)
        {
            $business_wallet->untrade($market_sell, $quantity_to_spend);
            return false;
        }
        
        //Save transaction in db
        $transaction = new Transaction();
        $transaction->strategy = $this->strategy;
        $transaction->currencies = $market;
        $transaction->quantity = $quantity;
        $transaction->rate = $rate;
        $transaction->fees = $fees;
        $transaction->status = 'open';
        $transaction->type = 'sell';
        $transaction->order_id = $order_id;
        $transaction->broker = $this->broker_name;

        if ($link_to_order)
        {
            $transaction->link_to_order = $link_to_order;
        }

        $transaction->save(); 

        return $order_id;
    }

    /**
     * Cancel an order
     * @param string $order_id : The id of the order to cancel
     * @return mixed : false if fail, true else
     */
    public function cancel ($order_id, $market = false)
    {
        //Get transaction from db
        $transaction = Transaction::where('broker', $this->broker_name)->where('order_id', $order_id)->first();
        if (!$transaction)
        {
            return false;
        }

        //Try to cancel the order
        $order = $this->broker->cancel($order_id, $market);
        if (!$order)
        {
            return false;
        }

        //Maintenant faut faire tout le traitement sur les wallet et les transactions
        if ($transaction['type'] == 'buy')
        {
            //On update la transaction
            $transaction->quantity = $order['actual_quantity'];
            $transaction->rate = $order['actual_rate'];
            $transaction->fees = $order['actual_fees'];
            $transaction->status = 'cancel';

            $transaction->save();


            //On update le wallet
            $business_wallet = new BusinessWallet($this->broker_name);
            
            $market_sell = explode('-', $transaction->currencies)[0];
            $market_buy = explode('-', $transaction->currencies)[1];

            //Compute sell amount from real quantity, rate and fees
            $actual_sell_amount = $order['actual_quantity'] * $order['actual_rate'] + $order['actual_fees'];
            $previsionnal_sell_amount = $order['quantity'] * $order['rate'] + $order['fees'];

            //On update le wallet
            $business_wallet->register_sell($market_sell, $actual_sell_amount);
            $business_wallet->untrade($market_sell, $previsionnal_sell_amount);
            $business_wallet->register_buy($market_buy, $order['actual_quantity']);
        }
        elseif ($transaction['type'] == 'sell')
        {
            //On update la transaction
            $transaction->quantity = $order['actual_quantity'];
            $transaction->rate = $order['actual_rate'];
            $transaction->fees = $order['actual_fees'];
            $transaction->status = 'cancel';

            $transaction->save();


            //On update le wallet
            $business_wallet = new BusinessWallet($this->broker_name);
            
            $market_buy = explode('-', $transaction->currencies)[0];
            $market_sell = explode('-', $transaction->currencies)[1];

            //Compute sell amount from real quantity, rate
            $actual_sell_amount = $order['actual_quantity'] * $order['actual_rate'];
            $previsionnal_sell_amount = $order['quantity'] * $order['rate'];

            //On update le wallet
            $business_wallet->register_sell($market_sell, $actual_sell_amount);
            $business_wallet->untrade($market_sell, $previsionnal_sell_amount);
            $business_wallet->register_buy($market_buy, $order['actual_quantity'] - $order['actual_fees']);
        }

        return $order;
    }

    /**
     * Get an order
     * @param string $order_id : The id of the order to get
     * @return mixed array|bool : false if fail, else array with ['quantity', 'actual_remaining', 'rate', 'actual_rate', 'fees', 'actual_fees', 'date_open', 'open']
     */
    public function get_order ($order_id)
    {
        return $this->broker->get_order($order_id);
    }

    /**
     * Calcul fees
     * @param string $type : 'buy' or 'Sell'
     * @param float $quantity : Quantity to buy or sell
     * @param float $rate : Rate of buying
     */
    public function compute_fees ($type, $quantity, $rate)
    {
        return $this->broker->compute_fees($type, $quantity, $rate);
    }

    /**
     * Return fees rate
     */ 
    public function get_fees_rate()
    {
        return $this->broker->get_fees_rate();
    }

    /**
     * Get last transaction rate for market
     * @param string $market : The market we want rate
     */
    public function get_market_last_rate ($market)
    {
        return $this->broker->get_market_last_rate($market);
    }
    
    /**
     * Get ask transaction rate for market
     * @param string $market : The market we want rate
     */
    public function get_market_ask_rate ($market)
    {
        return $this->broker->get_market_ask_rate($market);
    }
    
    /**
     * Get bid transaction rate for market
     * @param string $market : The market we want rate
     */
    public function get_market_bid_rate ($market)
    {
        return $this->broker->get_market_bid_rate($market);
    }

    /**
     * Validate a transaction and 
     * @param string $order_id : The id of the order to validate
     * @return bool : True if success, false else
     */
    public function validate_transaction ($order_id)
    {
        //Get transaction from db
        $transaction = Transaction::where('broker', $this->broker_name)->where('order_id', $order_id)->first();
        if (!$transaction)
        {
            return false;
        }
        
        //Try to get the order
        $order = $this->broker->get_order($order_id);
        if (!$order)
        {
            return false;
        }

        //Maintenant faut faire tout le traitement sur les wallet et les transactions
        if ($transaction['type'] == 'buy')
        {
            //On update la transaction
            $transaction->quantity = $order['actual_quantity'];
            $transaction->rate = $order['actual_rate'];
            $transaction->fees = $order['actual_fees'];
            $transaction->status = 'close';

            echo "Transaction buy actual_rate = " . $order['actual_rate'] . " vs " . $transaction->rate . " actual fees = " . $order['actual_fees'] . " vs " . $transaction->fees . "\n";

            $transaction->save();


            //On update le wallet
            $business_wallet = new BusinessWallet($this->broker_name);
            
            $market_sell = explode('-', $transaction->currencies)[0];
            $market_buy = explode('-', $transaction->currencies)[1];

            //Compute sell amount from real quantity, rate and fees
            $actual_sell_amount = $order['actual_quantity'] * $order['actual_rate'] + $order['actual_fees'];
            $previsionnal_sell_amount = $order['quantity'] * $order['rate'] + $order['fees'];
            $actual_buy_quantity = $order['actual_quantity'];

            echo "Actual sell amount = " . $actual_sell_amount . " vs " . $previsionnal_sell_amount . " actual buy = " . $actual_buy_quantity . "\n";

            //On update le wallet
            $business_wallet->register_sell($market_sell, $actual_sell_amount);
            $business_wallet->untrade($market_sell, $previsionnal_sell_amount);
            $business_wallet->register_buy($market_buy, $actual_buy_quantity);
        }
        elseif ($transaction['type'] == 'sell')
        {
            //On update la transaction
            $transaction->quantity = $order['actual_quantity'];
            $transaction->rate = $order['actual_rate'];
            $transaction->fees = $order['actual_fees'];
            $transaction->status = 'close';

            $transaction->save();
            
            echo "Transaction sell actual_rate = " . $order['actual_rate'] . " vs " . $transaction->rate . " actual fees = " . $order['actual_fees'] . " vs " . $transaction->fees . "\n";


            //On update le wallet
            $business_wallet = new BusinessWallet($this->broker_name);
            
            $market_buy = explode('-', $transaction->currencies)[0];
            $market_sell = explode('-', $transaction->currencies)[1];

            //Compute sell amount from real quantity, rate
            $actual_sell_amount = $order['actual_quantity'];
            $previsionnal_sell_amount = $order['quantity'];
            $actual_buy_quantity = $order['actual_quantity'] * $order['actual_rate'] - $order['actual_fees'];

            echo "Actual sell amout = " . $actual_sell_amount . " vs " . $previsionnal_sell_amount . " actual buy = " . $actual_buy_quantity . "\n";

            //On update le wallet
            $business_wallet->register_sell($market_sell, $actual_sell_amount);
            $business_wallet->untrade($market_sell, $previsionnal_sell_amount);
            $business_wallet->register_buy($market_buy, $actual_buy_quantity); 
        }

        return true;
    }

    /**
    * Get deposit address for a currency
    * @param string $currency : the currency we want address
    */
    public function get_deposit_address ($currency)
    {
        return $this->broker->get_deposit_address($currency);
    }

    /**
    * send currency to another address (broker)
    * @param string $currency : the currency we want to send
    * @param float $quantity  : the quantity of currency we want to send
    * @param string $address : the address to which send the currency
    */
    public function withdraw ($currency, $quantity, $address, $to_broker, $fees)
    {
        $return = $this->broker->withdraw($currency, $quantity, $address);

        $business_wallet_send = new BusinessWallet($this->broker_name);
        $business_wallet_send->register_sell ($currency, $quantity);

        $to_broker = strtolower($to_broker);
        $business_wallet_receive = new BusinessWallet($to_broker);
        $business_wallet_receive->register_buy ($currency, $quantity - $fees);

        return $return;
    }

    /**
    * get quantity of currencies
    */
    public function get_balances()
    {
        return $this->broker->get_balances();
    }

    /**
    * get order book for a market
    * @param string $market : the market (BTC-ETH) we want the order book
    * @return array : order book order by rate
    */
    public function get_order_book($market)
    {
        return $this->broker->get_order_book($market);
    }

    /**
    * ask the broker if a wallet is available or not
    * @param string $currency : code of currency
    * @return bool
    */
    public function is_wallet_available($currency)
    {
        return $this->broker->is_wallet_available($currency);
    }

    /**
    * ask to the broker the fees of withdraw for a currency
    * @param string $currency : code of currency
    * @return floar
    */
    public function get_withdraw_fees($currency)
    {
        return $this->broker->get_withdraw_fees($currency);
    }

    /**
    * ask to the broker the minimum order size in BTC
    * @param string $market : market we want 
    * @return float
    */
    public function get_minimum_order_size($market)
    {
        return $this->broker->get_minimum_order_size($market);
    }

}
