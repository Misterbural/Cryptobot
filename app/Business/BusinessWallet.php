<?php

namespace App\Business;

/**
* Class Wallet
* @package App\Business
*
* Update Wallet
*/

use App\Models\Wallet;
use Illuminate\Support\Facades\DB;


class BusinessWallet {
    
    /**
     * Get wallet for currency
     * @param string $currency : Currency we want wallet for
     * @return mixed Wallet|bool : The wallet object of false if not found
     */
    public function get_wallet_for_currency ($currency)
    {
        if (!$wallet = Wallet::where('currency', $currency)->first())
        {
            return false;
        }

        return $wallet;
    }

    /**
     * Register a sell
     * @param string $currency : Currency concerned
     * @param float $value : Value sell
     * @return boolean : True if success or false
     */
	public function register_sell ($currency, $value)
	{
        $updateResult = DB::table('wallets')
                        ->where('currency', $currency)
                        ->decrement('available', $value);

        if (!$updateResult)
        {
            return false;
        }

        return true;
    }

    /**
     * Register a buy
     * @param string $currency : Currency concerned
     * @param float $value : value buy
     * @param float (0 à 1) optional $percent_to_keep : Percentage of value to keep (buy default 0)
     * @return boolean : True if success or false
     */
    public function register_buy ($currency, $value, $percent_to_keep = 0)
    {
        $to_keep = $value * $percent_to_keep;
        $value = $value - $to_keep;

        $updateResult = DB::table('wallets')
                        ->where('currency', $currency)
                        ->increment('available', $value);

        if (!$updateResult)
        {
            return false;
        }

        if (!$percent_to_keep)
        {
            return true;
        }

        $updateResult = DB::table('wallets')
                        ->where('currency', $currency)
                        ->increment('to_keep', $to_keep);
        
        if (!$updateResult)
        {
            return false;
        }

        return true;
    }
    
    /**
     * Move value from on available to on_trade
     * @param string $currency : Currency concerned
     * @param float $value : Value to trade
     * @return boolean: True if success or false
     */
    public function trade ($currency, $value)
    {
        //On decrement available
        $updateResult = DB::table('wallets')
                        ->where('currency', $currency)
                        ->decrement('available', $value);

        if (!$updateResult)
        {
            return false;
        }

        //On increment on_trade
        $updateResult = DB::table('wallets')
                        ->where('currency', $currency)
                        ->increment('on_trade', $value);
 
        if (!$updateResult)
        {
            return false;
        }

        return true;
    }
    
    /**
     * Move value from on_trade to available
     * @param string $currency : Currency concerned
     * @param float $value : Value to trade
     * @return boolean: True if success or false
     */
    public function untrade ($currency, $value)
    {
        //On decrement on_trade
        $updateResult = DB::table('wallets')
                        ->where('currency', $currency)
                        ->decrement('on_trade', $value);

        if (!$updateResult)
        {
            return false;
        }

        //On increment available
        $updateResult = DB::table('wallets')
                        ->where('currency', $currency)
                        ->increment('available', $value);
 
        if (!$updateResult)
        {
            return false;
        }

        return true;
    }
    
    /**
     * Check if we have enough currency to spend a certain value
     * @param string $currency : Currency concerned
     * @param float $amount : amount to spend
     * @return boolean : True if possible, false else
     */
    public function check_can_spend ($currency, $amount)
    {
        if (!$wallet = Wallet::where('currency', $currency)->first())
        {
            return false;
        }

        //if spend $amount make available go under to_keep, return false
        if ( ($wallet->available - $amount) < $wallet->to_keep || $amount > $wallet->available)
        {
            return false;
        }

        return true;
    }

    /**
     * Increment to keep
     * @param string $currency : Currency concerned
     * @param float $value : Value to add to to_keep
     * @param boolean : True if success, false else
     */
    public function increment_to_keep ($currency, $value)
    {
        $updateResult = DB::table('wallets')
                        ->where('currency', $currency)
                        ->increment('to_keep', $value);
        
        if (!$updateResult)
        {
            return false;
        }

        return true;
    }
    
    /**
     * Decrement to keep
     * @param string $currency : Currency concerned
     * @param float $value : Value to substract to to_keep
     * @param boolean : True if success, false else
     */
    public function decrement_to_keep ($currency, $value)
    {
        $updateResult = DB::table('wallets')
                        ->where('currency', $currency)
                        ->decrement('to_keep', $value);
        
        if (!$updateResult)
        {
            return false;
        }

        return true;
    }

}
