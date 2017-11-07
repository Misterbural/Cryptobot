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
        $currencies = config('constants.currencies');

        DB::table('wallets')->insert([
            'currency' => 'BTC',
            'available' => 1,
            'on_trade' => 0,
            'to_keep' => 0,
            'broker' => 'bittrex',
        ]);

        foreach ($currencies as $currency) {
                DB::table('wallets')->insert([
                'currency' => $currency,
                'available' => 0,
                'on_trade' => 0,
                'to_keep' => 0,
                'broker' => 'bittrex',
            ]);
        }
    }
}
