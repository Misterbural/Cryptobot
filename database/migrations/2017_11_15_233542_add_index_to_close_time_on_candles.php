<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexToCloseTimeOnCandles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('candles_1m', function (Blueprint $table) {
                $table->index(['close_time']);
        });
        Schema::table('candles_5m', function (Blueprint $table) {
                $table->index(['close_time']);
        });
        Schema::table('candles_15m', function (Blueprint $table) {
                $table->index(['close_time']);
        });
        Schema::table('candles_30m', function (Blueprint $table) {
                $table->index(['close_time']);
        });
        Schema::table('candles_60m', function (Blueprint $table) {
                $table->index(['close_time']);
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
                $table->dropIndex(['close_time']); // Drops index 'geo_state_index'
        });
        Schema::table('candles_5m', function (Blueprint $table) {
                $table->dropIndex(['close_time']); // Drops index 'geo_state_index'
        });
        Schema::table('candles_15m', function (Blueprint $table) {
                $table->dropIndex(['close_time']); // Drops index 'geo_state_index'
        });
        Schema::table('candles_30m', function (Blueprint $table) {
                $table->dropIndex(['close_time']); // Drops index 'geo_state_index'
        });
        Schema::table('candles_60m', function (Blueprint $table) {
                $table->dropIndex(['close_time']); // Drops index 'geo_state_index'
        });
    }
}
