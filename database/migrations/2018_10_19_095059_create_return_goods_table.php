<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateReturnGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'return_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('rg_id')->comment('自增ID号');
            $table->integer('rec_id')->unsigned()->index('rec_id')->comment('订单商品ID');
            $table->integer('ret_id')->unsigned()->default(0)->index('ret_id')->comment('退换货订单ID');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品ID');
            $table->integer('product_id')->unsigned()->default(0)->index('product_id')->comment('商品货品ID');
            $table->string('product_sn', 60)->nullable()->comment('商品货品货号');
            $table->string('goods_name', 120)->nullable()->comment('商品名称');
            $table->string('brand_name', 60)->nullable()->comment('品牌名称');
            $table->string('goods_sn', 60)->nullable()->comment('商品货号');
            $table->boolean('is_real')->nullable()->default(0)->comment('是否是实物，0，否；1，是；取值dsc_goods');
            $table->text('goods_attr')->nullable()->comment('商品属性值');
            $table->string('attr_id')->comment('属性ID');
            $table->boolean('return_type')->default(0)->comment('退换货标识：0：维修，1：退货，2：换货，3：仅退款');
            $table->integer('return_number')->unsigned()->default(0)->comment('退换数量');
            $table->text('out_attr')->comment('退换商品属性名称');
            $table->string('return_attr_id')->comment('退换货商品属性ID');
            $table->decimal('refound', 10)->comment('退款金额');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '单品退换货商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('return_goods');
    }
}
