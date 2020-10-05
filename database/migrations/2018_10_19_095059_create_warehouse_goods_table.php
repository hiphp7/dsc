<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWarehouseGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'warehouse_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('w_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('商家ID（同dsc_users表user_id）');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品ID');
            $table->integer('region_id')->unsigned()->default(0)->index('region_id')->comment('仓库ID');
            $table->string('region_sn', 60)->default('')->comment('仓库商品货号');
            $table->integer('region_number')->unsigned()->default(0)->comment('仓库数量');
            $table->decimal('warehouse_price', 10)->unsigned()->default(0.00)->comment('仓库价格');
            $table->decimal('warehouse_promote_price', 10)->unsigned()->default(0.00)->comment('仓库促销价');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('last_update')->unsigned()->default(0)->comment('更新时间');
            $table->integer('give_integral')->default(0)->comment('赠送积分');
            $table->integer('rank_integral')->default(0)->comment('等级积分');
            $table->integer('pay_integral')->unsigned()->default(0)->comment('支付积分');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '仓库商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('warehouse_goods');
    }
}
