<?php

namespace App\Business;

/**
* Class Candle
* @package App\Business
*
* Compute candle from collect market data or analyse candle from db
*/

use App\Models\Candle_1m;
use App\Models\Candle_5m;
use App\Models\Candle_15m;
use App\Models\Candle_30m;
use App\Models\Candle_60m;

class BusinessCandle {

	/**
	 * @param string $market : currency of candle
     * @param array $transactions : list of transaction
     *
     * @return boolean
     * 
     */
	public function compute_candle_1m ($market, $transactions = array())
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

		return true;
	}

	/**
	 * @param string $market : currency of candle
	 * @param datetime $close_time : date end of candle
     * @param int $intervel : duration of candle
     *
     * @return boolean
     * 
     */
	public function compute_candles_with_interval ($market, $close_time, $interval)
	{
		//condition mauvaise pas obligatoirement 5 candles dans les 5 dernieres minutes
		$open_time = clone($close_time);
		$submin = $interval - 1;
		$open_time->sub(new \DateInterval("PT" . $submin . "M"));
		$open_time->setTime($open_time->format('H'), $open_time->format('i'), 0);

		$candles = Candle_1m::where('currencies', $market)
		->where('open_time', '>=', $open_time)
		->where('close_time', '<=', $close_time)
		->get();

		if (!count($candles)) {
			return false;
		}
		
		$volume = 0;
		$min_price = $candles[0]->min_price;
		$max_price = $candles[0]->max_price;
		$open_price = $candles[0]->open_price;
		$close_price = $candles[0]->close_price;
		$open_time_tmp = \DateTime::createFromFormat('Y-m-d H:i:s', $candles[0]->open_time);
		$close_time_tmp = \DateTime::createFromFormat('Y-m-d H:i:s', $candles[0]->close_time);

		foreach ($candles as $candle) {
			
			$volume += $candle->volume;

			if ($candle->max_price > $max_price) {
				$max_price = $candle->max_price;
			}

			if ($candle->min_price < $min_price) {
				$min_price = $candle->min_price;
			}

			$candle_open_datetime = \DateTime::createFromFormat('Y-m-d H:i:s', $candle->open_time);
			if ($candle_open_datetime->format("Y-m-d H:i:s") < $open_time_tmp->format("Y-m-d H:i:s")) {
				$open_time_tmp = $candle_open_datetime;
				$open_price = $candle->open_price;
			}

			$candle_close_datetime = \DateTime::createFromFormat('Y-m-d H:i:s', $candle->close_time);
			if ($candle_close_datetime->format("Y-m-d H:i:s") > $close_time_tmp->format("Y-m-d H:i:s")) {
				$close_time_tmp = $candle_close_datetime;
				$close_price = $candle->close_price;
			}

		}

		$class = "App\Candle_" . $interval . "m";

		$candle = new $class;
		$candle->open_price = $open_price;
		$candle->close_price = $close_price;
		$candle->min_price = $min_price;
		$candle->max_price = $max_price;
		$candle->currencies = $market;
		$candle->volume = $volume;
		$candle->open_time = $open_time;
		$candle->close_time = $close_time;

		$candle->save();

		if ($interval == 5 && $close_time->format('i') % 15 == 14) {
			$this->compute_candles_with_interval($market, $close_time, 15);
		}

		if ($interval == 15 && $close_time->format('i') % 30 == 29) {
			$this->compute_candles_with_interval($market, $close_time, 30);
		}

		if ($interval == 30 && $close_time->format('i') % 60 == 59) {
			$this->compute_candles_with_interval($market, $close_time, 60);
		}

		return true;
	}
}
