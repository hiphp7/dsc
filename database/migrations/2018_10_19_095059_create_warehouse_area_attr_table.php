<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWarehouseAreaAttrTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'warehouse_area_attr';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品ID');
            $table->string('goods_attr_id', 50)->index('goods_attr_id')->comment('商品属性ID');
            $table->integer('area_id')->unsigned()->default(0)->index('area_id')->comment('地区ID');
            $table->decimal('attr_price', 10)->default(0.00)->comment('地区属性价格');
            $table->integer('admin_id')->unsigned()->default(0)->index('admin_id')->comment('管理员id');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '商品仓库地区属性价格库存信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('warehouse_area_attr');
    }
}
