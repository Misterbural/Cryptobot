<?php

namespace App\Business;

/**
* Class Candle
* @package App\Business
*
* Compute candle from collect market data or analyse candle from db
*/

use App\Candle_1m;
use App\Candle_5m;
use App\Candle_15m;
use App\Candle_30m;
use App\Candle_1h;

class BusinessCandle {

	/**
     * @param array list of transaction
     *
     * @return boolean
     * 
     */
	public function compute_candle_1m($market, $transactions = array())
	{
		$volume = 0;

		$min_price = $transactions[0]['Price'];
		$max_price = $transactions[0]['Price'];
		$open_price = $transactions[0]['Price'];
		$close_price = $transactions[0]['Price'];
		$open_time = \DateTime::createFromFormat('Y-m-d\TH:i:s', substr($transactions[0]['TimeStamp'], 0, 19));
		$close_time = \DateTime::createFromFormat('Y-m-d\TH:i:s', substr($transactions[0]['TimeStamp'], 0, 19));

		foreach ($transactions as $transaction) {

			$volume += $transaction['Quantity'] * $transaction['Price'];

			if ($transaction['Price'] > $max_price) {

				$max_price = $transaction['Price'];

			} else if ($transaction['Price'] < $min_price) {

				$min_price = $transaction['Price'];

			}

			$transaction_datetime = \DateTime::createFromFormat('Y-m-d\TH:i:s', substr($transaction['TimeStamp'], 0, 19));

			if ($transaction_datetime->format("Y-m-d H:i:s") < $open_time->format("Y-m-d H:i:s")) {

				$open_price = $transaction['Price'];
				$open_time = $transaction_datetime;

			} elseif ($transaction_datetime->format("Y-m-d H:i:s") > $close_time->format("Y-m-d H:i:s")) {

				$close_price = $transaction['Price'];
				$close_time = $transaction_datetime;

			}

		}
		

		$open_time->setTime($open_time->format('H'), $open_time->format('i'), 0);
		$close_time->setTime($close_time->format('H'), $close_time->format('i'), 59);

		$candle = new Candle_1m;
		$candle->open_price = $open_price;
		$candle->close_price = $close_price;
		$candle->min_price = $min_price;
		$candle->max_price = $max_price;
		$candle->currencies = $market;
		$candle->volume = $volume;
		$candle->open_time = $open_time;
		$candle->close_time = $close_time;

		$candle->save();

		//si on fini un bloc de 5 minutes, on génère le candle de 5 minutes
		//Méthode bancale si pas de transaction sur la dernieres minutes
		if ($close_time->format('i') % 5 == 4) {
			$this->compute_candle_5m($market);
		}

		return true;
	}

	private function compute_candle_5m($market)
	{
		$candles_1m = Candle_1m::where('currencies', $market)->orderBy('id', 'desc')->take(5)->get();
		
		$volume = 0;
		$min_price = $candles_1m[0]->min_price;
		$max_price = $candles_1m[0]->max_price;
		$open_price = $candles_1m[0]->open_price;
		$close_price = $candles_1m[0]->close_price;
		$open_time = \DateTime::createFromFormat('Y-m-d H:i:s', $candles_1m[0]->open_time);
		$close_time = \DateTime::createFromFormat('Y-m-d H:i:s',$candles_1m[0]->close_time);

		foreach ($candles_1m as $candle) {
			
			$volume += $candle->volume;

			if ($candle->max_price > $max_price) {
				$max_price = $candle->max_price;
			}

			if ($candle->min_price < $min_price) {
				$min_price = $candle->min_price;
			}

			$candle_open_datetime = \DateTime::createFromFormat('Y-m-d H:i:s', $candle->open_time);
			if ($candle_open_datetime->format("Y-m-d H:i:s") < $open_time->format("Y-m-d H:i:s")) {
				$open_time = $candle_open_datetime;
				$open_price = $candle->open_price;
			}

			$candle_close_datetime = \DateTime::createFromFormat('Y-m-d H:i:s', $candle->close_time);
			if ($candle_close_datetime->format("Y-m-d H:i:s") > $close_time->format("Y-m-d H:i:s")) {
				$close_time = $candle_close_datetime;
				$close_price = $candle->close_price;
			}

		}

		$candle = new Candle_5m;
		$candle->open_price = $open_price;
		$candle->close_price = $close_price;
		$candle->min_price = $min_price;
		$candle->max_price = $max_price;
		$candle->currencies = $market;
		$candle->volume = $volume;
		$candle->open_time = $open_time;
		$candle->close_time = $close_time;

		$candle->save();

		return true;
	}
}