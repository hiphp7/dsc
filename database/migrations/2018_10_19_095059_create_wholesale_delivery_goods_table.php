<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesaleDeliveryGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_delivery_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('rec_id')->comment('自增ID');
            $table->integer('delivery_id')->unsigned()->default(0)->comment('发货单ID');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品ID');
            $table->integer('product_id')->unsigned()->nullable()->default(0)->comment('货品ID');
            $table->string('product_sn', 60)->nullable()->comment('货品编码');
            $table->string('goods_name', 120)->nullable()->comment('商品名称');
            $table->string('brand_name', 60)->nullable()->comment('品牌名称');
            $table->string('goods_sn', 60)->nullable()->comment('商品编码');
            $table->boolean('is_real')->nullable()->default(0)->comment('是否是实物，1，是；0，否；比如虚拟卡就为0，不是实物');
            $table->string('extension_code', 30)->nullable()->comment('商品的扩展属性，比如像虚拟卡');
            $table->integer('parent_id')->unsigned()->nullable()->default(0)->comment('能获得推荐分成的用户id，id取值于表dsc_users');
            $table->smallInteger('send_number')->unsigned()->nullable()->default(0)->comment('发货数量');
            $table->text('goods_attr')->nullable()->comment('属性值');
            $table->index(['delivery_id', 'goods_id'], 'delivery_id');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '供应链订单发货单商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_delivery_goods');
    }

}
