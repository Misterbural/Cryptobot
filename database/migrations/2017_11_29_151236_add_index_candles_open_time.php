<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexCandlesOpenTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('candles_1m', function (Blueprint $table) {
                $table->index(['open_time']);
        });
        Schema::table('candles_5m', function (Blueprint $table) {
                $table->index(['open_time']);
        });
        Schema::table('candles_15m', function (Blueprint $table) {
                $table->index(['open_time']);
        });
        Schema::table('candles_30m', function (Blueprint $table) {
                $table->index(['open_time']);
        });
        Schema::table('candles_60m', function (Blueprint $table) {
                $table->index(['open_time']);
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
                $table->dropIndex(['open_time']); // Drops index 'geo_state_index'
        });
        Schema::table('candles_5m', function (Blueprint $table) {
                $table->dropIndex(['open_time']); // Drops index 'geo_state_index'
        });
        Schema::table('candles_15m', function (Blueprint $table) {
                $table->dropIndex(['open_time']); // Drops index 'geo_state_index'
        });
        Schema::table('candles_30m', function (Blueprint $table) {
                $table->dropIndex(['open_time']); // Drops index 'geo_state_index'
        });
        Schema::table('candles_60m', function (Blueprint $table) {
                $table->dropIndex(['open_time']); // Drops index 'geo_state_index'
        });
    }
}
