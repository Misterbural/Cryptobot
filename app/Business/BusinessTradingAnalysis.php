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

    public function cmo($market, $data)
    {
        $cmo = trader_cmo($data['close'], count($data['close']));
        $cmo = array_pop($cmo);

        return $cmo;
    }

    public function mfi($market, $data)
    {
        $mfi = trader_mfi($data['high'], $data['low'], $data['close'], $data['volume'], count($data['high']));
        $mfi = array_pop($mfi);

        return $mfi;
    }
}