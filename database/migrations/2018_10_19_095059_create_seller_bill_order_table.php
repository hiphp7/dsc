<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSellerBillOrderTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seller_bill_order';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('bill_id')->unsigned()->default(0)->index('bill_id')->comment('商家账单id');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('订单会员id');
            $table->integer('seller_id')->unsigned()->default(0)->index('seller_id')->comment('商家id');
            $table->integer('order_id')->unsigned()->default(0)->unique('order_id')->comment('订单id');
            $table->string('order_sn', 100)->default('')->unique('order_sn')->comment('订单编号');
            $table->boolean('order_status')->default(0)->index('order_status')->comment('订单状态');
            $table->boolean('shipping_status')->default(0)->index('shipping_status')->comment('配送状态');
            $table->boolean('pay_status')->default(0)->comment('支付状态');
            $table->decimal('order_amount', 10)->unsigned()->default(0.00)->comment('订单总额');
            $table->decimal('return_amount', 10)->unsigned()->default(0.00)->comment('退款总额');
            $table->decimal('return_shippingfee', 10)->unsigned()->default(0.00)->comment('订单退货运费');
            $table->decimal('goods_amount', 10)->unsigned()->default(0.00)->comment('商品总额');
            $table->decimal('tax', 10)->unsigned()->default(0.00)->comment('税额');
            $table->decimal('shipping_fee', 10)->unsigned()->default(0.00)->comment('运费金额');
            $table->decimal('insure_fee', 10)->unsigned()->default(0.00)->comment('保价费用');
            $table->decimal('pay_fee', 10)->unsigned()->default(0.00)->comment('支付费用');
            $table->decimal('pack_fee', 10)->unsigned()->default(0.00)->comment('包装费用');
            $table->decimal('card_fee', 10)->unsigned()->default(0.00)->comment('贺卡费用');
            $table->decimal('bonus', 10)->unsigned()->default(0.00)->comment('红包金额');
            $table->decimal('integral_money', 10)->unsigned()->default(0.00)->comment('积分金额');
            $table->decimal('coupons', 10)->unsigned()->default(0.00)->comment('优惠券');
            $table->decimal('discount', 10)->unsigned()->default(0.00)->comment('优惠金额');
            $table->decimal('value_card', 10)->unsigned()->default(0.00)->comment('储值卡');
            $table->decimal('money_paid', 10)->unsigned()->default(0.00)->comment('已支付金额');
            $table->decimal('surplus', 10)->unsigned()->default(0.00)->comment('余额支付金额');
            $table->decimal('drp_money', 10)->unsigned()->default(0.00)->comment('分销金额');
            $table->integer('confirm_take_time')->unsigned()->default(0)->index('confirm_take_time')->comment('确认收货时间');
            $table->boolean('chargeoff_status')->default(0)->index('chargeoff_status')->comment('账单 (0:未结账 1:已出账 2:已结账单)');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商家账单订单'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('seller_bill_order');
    }
}
