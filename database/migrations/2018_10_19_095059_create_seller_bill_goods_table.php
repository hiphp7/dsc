<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSellerBillGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seller_bill_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('rec_id')->unsigned()->default(0)->index('rec_id')->comment('商品订单id');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单id');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品id');
            $table->integer('cat_id')->unsigned()->default(0)->index('cat_id')->comment('分类id');
            $table->string('proportion', 20)->default('')->comment('分类佣金百分比');
            $table->decimal('goods_price', 10)->unsigned()->default(0.00)->comment('商品价格');
            $table->decimal('dis_amount', 10)->unsigned()->default(0.00)->comment('商品单品满减优惠金额');
            $table->integer('goods_number')->unsigned()->default(0)->comment('商品数量');
            $table->text('goods_attr')->nullable()->comment('商品属性');
            $table->decimal('drp_money', 10)->unsigned()->default(0.00)->comment('分销价额');
            $table->string('commission_rate', 10)->default('')->comment('佣金比例');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商家账单订单商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('seller_bill_goods');
    }
}
