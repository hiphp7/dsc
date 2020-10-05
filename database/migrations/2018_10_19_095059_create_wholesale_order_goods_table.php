<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesaleOrderGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_order_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('rec_id')->comment('自增ID');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单id');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品id');
            $table->string('goods_name', 120)->default('')->comment('商品名称');
            $table->string('goods_sn', 60)->default('')->comment('商品货号');
            $table->integer('product_id')->unsigned()->default(0)->comment('商品货品id');
            $table->integer('goods_number')->unsigned()->default(1)->comment('商品数量');
            $table->decimal('market_price', 10)->default(0.00)->comment('市场价格');
            $table->decimal('goods_price', 10)->default(0.00)->comment('商品价格');
            $table->text('goods_attr')->nullable()->comment('商品属性');
            $table->integer('send_number')->unsigned()->default(0)->comment('发货数量');
            $table->boolean('is_real')->default(0)->comment('是否实物商品');
            $table->string('extension_code', 30)->default('')->comment('活动标识');
            $table->string('goods_attr_id')->default('')->comment('商品属性id');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家id');
            $table->decimal('shipping_fee', 10)->unsigned()->default(0.00)->comment('商品固定运费金额');
            $table->boolean('freight')->default(0)->comment('商品运费模板类型');
            $table->integer('tid')->unsigned()->default(0)->comment('商品运费模板ID');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '批发订单商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_order_goods');
    }
}
