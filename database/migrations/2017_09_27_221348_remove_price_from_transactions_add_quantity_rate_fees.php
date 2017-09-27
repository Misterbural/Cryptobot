<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class removePriceFromTransactionsAddQuantityRateFees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function($table) {
            $table->dropColumn('price');
            $table->double('quantity', 16, 8);
            $table->double('rate', 16, 8);
            $table->double('fees', 16, 8);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function($table) {
            $table->double('price', 10, 8);
            $table->dropColumn('quantity');
            $table->dropColumn('rate');
            $table->dropColumn('fees');
        });
    }
}
