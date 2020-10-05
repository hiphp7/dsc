<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTradeSnapshotTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'trade_snapshot';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('trade_id')->comment('自增ID');
            $table->string('order_sn', 100)->index('order_sn')->comment('订单号');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('会员id');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品id');
            $table->string('goods_name', 120)->default('')->comment('商品名称');
            $table->string('goods_sn', 60)->default('')->comment('商品货号');
            $table->decimal('shop_price', 10)->default(0.00)->comment('商城价格');
            $table->integer('goods_number')->unsigned()->default(1)->comment('商品数量');
            $table->decimal('shipping_fee', 10)->default(0.00)->comment('运费');
            $table->string('rz_shopName', 60)->default('')->comment('商家名称');
            $table->decimal('goods_weight', 10, 3)->default(0.000)->comment('商品重量');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->string('goods_attr')->default('')->comment('商品属性');
            $table->string('goods_attr_id')->default('')->comment('商品属性id');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家id');
            $table->text('goods_desc')->nullable()->comment('商品描述');
            $table->string('goods_img')->default('')->comment('商品图片');
            $table->integer('snapshot_time')->unsigned()->default(0)->comment('快照新增时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '交易快照'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('trade_snapshot');
    }
}
