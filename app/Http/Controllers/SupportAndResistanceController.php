<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Candle_5m;
use App\Business\BusinessSupportAndResistance;

class SupportAndResistanceController extends Controller
{
    public function peaks ($market)
    {
        $currencies = config('constants.currencies');
        $market_parts = explode('-', $market);
        
        if ($market_parts[0] != 'BTC' || !in_array($market_parts[1], $currencies))
        {
            return abort(404);
        }

        //we get candles for last 7 days
        $nb_candles_to_get = (60 / 5) * 24 * 7;
        $candles = Candle_5m::where('currencies', $market)->orderBy('open_time', 'desc')->take($nb_candles_to_get)->get()->reverse(false)->values();
        
        
        $business_support_and_resistance = new BusinessSupportAndResistance();

        //$peaks = $business_support_and_resistance->get_supports($candles, 3, 0.5);
        $minimas = $business_support_and_resistance->get_minimas($candles);
    
        if (!$minimas)
        {
            return abort(404);
        }

        $minimas = $business_support_and_resistance->filter_minimas($minimas, new \DateInterval('PT60M'));

        //Add bullet point to signal all minimas
        foreach ($candles as &$candle)
        {
            foreach ($minimas as $minima)
            {
                if ($candle->open_time == $minima->open_time)
                {
                    $candle->bullet = 'round';
                }
            }
        }

        $json_candles = json_encode($candles->toArray());

        return view('SupportAndResistance.peaks')->with([
            'market' => $market,
            'minimas' => $minimas,
            'json_candles' => $json_candles,
        ]);
    }
}
