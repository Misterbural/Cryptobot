<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBrookerToCandles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('candles_1m', function (Blueprint $table) {
            $table->string('broker', 100);
        });
        
        Schema::table('candles_5m', function (Blueprint $table) {
            $table->string('broker', 100);
        });
        
        Schema::table('candles_15m', function (Blueprint $table) {
            $table->string('broker', 100);
        });
        
        Schema::table('candles_30m', function (Blueprint $table) {
            $table->string('broker', 100);
        });
        
        Schema::table('candles_60m', function (Blueprint $table) {
            $table->string('broker', 100);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('candles_1m', function (Blueprint $table) {
            $table->dropColumn('broker');
        });
        
        Schema::table('candles_5m', function (Blueprint $table) {
            $table->dropColumn('broker');
        });
        
        Schema::table('candles_15m', function (Blueprint $table) {
            $table->dropColumn('broker');
        });
        
        Schema::table('candles_30m', function (Blueprint $table) {
            $table->dropColumn('broker');
        });
        
        Schema::table('candles_60m', function (Blueprint $table) {
            $table->dropColumn('broker');
        });
    }
}
