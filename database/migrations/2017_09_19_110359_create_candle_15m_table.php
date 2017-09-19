<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCandle15mTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('candles_15m', function (Blueprint $table) {
            $table->increments('id');
            $table->double('open_price', 14, 8);
            $table->double('close_price', 14, 8);
            $table->double('min_price', 14, 8);
            $table->double('max_price', 14, 8);
            $table->string('currencies');
            $table->double('volume', 9, 4);
            $table->dateTime('open_time');
            $table->dateTime('close_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('candle_15m');
    }
}
