<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesaleReturnGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_return_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('rg_id')->comment('自增ID');
            $table->integer('rec_id')->unsigned()->index('rec_id')->comment('退换订单商品ID');
            $table->integer('ret_id')->unsigned()->default(0)->index('ret_id')->comment('退换订单ID');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品ID');
            $table->integer('product_id')->unsigned()->default(0)->comment('商品货品ID');
            $table->string('product_sn', 60)->nullable()->comment('商品货品编码');
            $table->string('goods_name', 120)->nullable()->comment('商品名称');
            $table->string('brand_name', 60)->nullable()->comment('品牌名称');
            $table->string('goods_sn', 60)->nullable()->comment('商品编码');
            $table->boolean('is_real')->nullable()->default(0)->comment('是否是实物，1，是；0，否；比如虚拟卡就为0，不是实物');
            $table->text('goods_attr')->nullable()->comment('属性值');
            $table->string('attr_id')->comment('属性ID');
            $table->boolean('return_type')->default(0)->comment('退换货标识：0：维修，1：退货，2：换货，3：仅退款');
            $table->integer('return_number')->unsigned()->default(0)->comment('退换商品数量');
            $table->text('out_attr')->comment('换成商品属性');
            $table->string('return_attr_id')->comment('换成商品属性ID');
            $table->decimal('refound', 10)->comment('退款金额');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '供应链退换货商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_return_goods');
    }

}
