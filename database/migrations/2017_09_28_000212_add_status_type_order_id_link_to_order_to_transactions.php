<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusTypeOrderIdLinkToOrderToTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		Schema::table('transactions', function($table) {
            $table->enum('status', ['open', 'close', 'cancel']);
            $table->enum('type', ['buy', 'sell']);
            $table->string('order_id');
            $table->string('link_to_order')->nullable();
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
            $table->dropColumn('status');
            $table->dropColumn('type');
            $table->dropColumn('order_id');
            $table->dropColumn('link_to_order');
        });

    }
}
