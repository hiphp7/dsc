<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'delivery_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('rec_id')->comment('自增ID');
            $table->integer('delivery_id')->unsigned()->default(0)->comment('发货单ID同dsc_delivery_order的delivery_id');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品ID');
            $table->integer('product_id')->unsigned()->nullable()->default(0)->index('product_id')->comment('商品货品ID');
            $table->string('product_sn', 60)->nullable()->comment('商品货品货号');
            $table->string('goods_name', 120)->nullable()->comment('商品名称');
            $table->string('brand_name', 60)->nullable()->comment('品牌名称');
            $table->string('goods_sn', 60)->nullable()->comment('商品货号');
            $table->boolean('is_real')->nullable()->default(0)->index('is_real')->comment('取自dsc_goods的is_real');
            $table->string('extension_code', 30)->nullable()->comment('商品的扩展属性，取自dsc_goods的extension_code');
            $table->integer('parent_id')->unsigned()->nullable()->default(0)->index('parent_id')->comment('能获得推荐分成的用户id，id取值于表dsc_users');
            $table->integer('send_number')->unsigned()->nullable()->default(0)->comment('当不是实物时，是否已发货，0，否；1，是');
            $table->text('goods_attr')->nullable()->comment('商品属性值');
            $table->index(['delivery_id', 'goods_id'], 'delivery_id');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '订单发货单商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('delivery_goods');
    }
}
