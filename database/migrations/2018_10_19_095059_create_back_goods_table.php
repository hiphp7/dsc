<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBackGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'back_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('rec_id')->comment('自增ID号');
            $table->integer('back_id')->unsigned()->default(0)->index('back_id')->comment('退回订单ID，同back_order 的back_id');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品ID');
            $table->integer('product_id')->unsigned()->default(0)->index('product_id')->comment('货品ID');
            $table->string('product_sn', 60)->default('')->comment('货品编号');
            $table->string('goods_name', 120)->default('')->comment('商品名称');
            $table->string('brand_name', 60)->default('')->comment('品牌名称');
            $table->string('goods_sn', 60)->default('')->comment('商品货号');
            $table->boolean('is_real')->default(0)->comment('是否是实物，1，是；0，否；比如虚拟卡就为0，不是实物');
            $table->integer('send_number')->unsigned()->default(0)->comment('当不是实物时，是否已发货，0，否；1，是');
            $table->text('goods_attr')->nullable()->comment('属性值');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '退货订单商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('back_goods');
    }
}
