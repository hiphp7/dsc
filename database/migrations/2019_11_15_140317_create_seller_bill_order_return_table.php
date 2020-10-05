<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSellerBillOrderReturnTable extends Migration
{
    private $table = 'seller_bill_order_return';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->table)) {
            return false;
        }

        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('order_id')->index('order_id')->default(0)->comment('账单订单ID');
            $table->integer('ret_id')->index('ret_id')->default(0)->comment('单品退单ID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->table);
    }
}
