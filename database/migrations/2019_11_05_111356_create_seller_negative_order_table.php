<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSellerNegativeOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seller_negative_order';
        if (Schema::hasTable($name)) {
            return false;
        }

        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->integer('negative_id')->unsigned()->default(0)->index('negative_id')->comment('负账单ID');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单ID');
            $table->string('order_sn', 30)->default('')->comment('订单单号');
            $table->integer('ret_id')->unsigned()->default(0)->index('ret_id')->comment('单品退货订单ID');
            $table->string('return_sn', 30)->default('')->comment('退货订单单号');
            $table->integer('seller_id')->unsigned()->default(0)->index('seller_id')->comment('商家ID');
            $table->decimal('return_amount')->unsigned()->default('0.00')->comment('退款金额');
            $table->decimal('return_shippingfee')->unsigned()->default('0.00')->comment('退运费金额');
            $table->boolean('settle_accounts')->unsigned()->default(0)->comment('账单订单结算状态（0 未结算， 1已结算）');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_negative_order');
    }
}
