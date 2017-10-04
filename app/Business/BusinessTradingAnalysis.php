<?php

namespace App\Business;

/**
* Class TradingAnalysis
* @package App\Business
*
* Check market for analyse indicator of trading
*/


class BusinessTradingAnalysis {

	public function cci($market, $data)
    {
        $cci = trader_cci($data['high'], $data['low'], $data['close'], count($data['high']));
        $cci = array_pop($cci);
               
        return $cci;
    }
}