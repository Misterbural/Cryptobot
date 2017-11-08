<?php

use Illuminate\Database\Seeder;

class InitWalletsTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currencies = config('constants.currencies_arbitration');

        DB::table('wallets')->insert([
            'currency' => 'BTC',
            'available' => 0.00710367,
            'on_trade' => 0,
            'to_keep' => 0,
            'broker' => 'bittrex',
        ]);

        DB::table('wallets')->insert([
            'currency' => 'BTC',
            'available' => 0.00353947,
            'on_trade' => 0,
            'to_keep' => 0,
            'broker' => 'poloniex',
        ]);

        DB::table('wallets')->insert([
            'currency' => 'BTC',
            'available' => 0,
            'on_trade' => 0,
            'to_keep' => 0,
            'broker' => 'bitfinex',
        ]);

        foreach ($currencies as $currency) {
            foreach ($currency as $broker => $code) {
                DB::table('wallets')->insert([
                    'currency' => $code,
                    'available' => 0,
                    'on_trade' => 0,
                    'to_keep' => 0,
                    'broker' => $broker,
                ]);
            }
        }
    }
}
