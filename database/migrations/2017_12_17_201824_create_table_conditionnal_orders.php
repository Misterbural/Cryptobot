<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableConditionnalOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conditionnal_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('condition');
            $table->string('broker');
            $table->enum('type', ['buy', 'sell']);
            $table->string('currencies');
            $table->double('quantity', 16, 8);
            $table->double('rate', 16, 8);
            $table->string('order_to_link')->nullable();
            $table->string('strategy');
            $table->enum('status', ['wait', 'open', 'close', 'cancel']);
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
        Schema::dropIfExists('conditionnal_order');
    }
}
