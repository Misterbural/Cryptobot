<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Business\BusinessTransaction;
use App\Business\BusinessWallet;
use App\Models\Transaction;
use App\Models\Candle_60m;

class SearchSupportAndResistance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptobot:search_support_and_resistance {currency}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate support and resistance for currency';

    /**
    * List of currencies for trading
    * 
    * @var array
    */
    protected $currencies;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $currencies = 'BTC-' . strtoupper($this->argument('currency'));

        $nb_candles_db = Candle_60m::where('currencies', $currencies)->count();
        $nb_candles_for_computing = 100;

        $candles = Candle_60m::where('currencies', $currencies)->orderBy('open_time')->skip($nb_candles_db - $nb_candles_for_computing)->take($nb_candles_for_computing)->get();

        foreach ($candles as $candle) {
            var_dump($candle->open_time);
        }
    }
}
