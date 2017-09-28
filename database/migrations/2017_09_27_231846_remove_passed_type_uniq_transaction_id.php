<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemovePassedTypeUniqTransactionId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function($table) {
            $table->dropColumn('passed');
            $table->dropColumn('type');
            $table->dropColumn('uniq_transaction_id');
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
            $table->boolean('passed');
            $table->enum('type', ['Buy', 'Sell']);
            $table->string('uniq_transaction_id');
        });
    }
}
