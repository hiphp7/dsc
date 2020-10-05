<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGoodsInventoryLogsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'goods_inventory_logs';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('goods_id')->default(0)->index('goods_id')->comment('商品id');
            $table->integer('order_id')->default(0)->index('order_id')->comment('订单id');
            $table->boolean('use_storage')->default(0)->comment('操作订单类型');
            $table->integer('admin_id')->default(0)->index('admin_id')->comment('操作管理人员');
            $table->string('number', 160)->comment('库存数量');
            $table->boolean('model_inventory')->default(0)->index('model_inventory')->comment('商品库存模式');
            $table->boolean('model_attr')->default(0)->comment('商品属性模式');
            $table->integer('product_id')->unsigned()->default(0)->index('product_id')->comment('商品货品ID');
            $table->integer('warehouse_id')->unsigned()->default(0)->index('warehouse_id')->comment('仓库ID');
            $table->integer('area_id')->unsigned()->default(0)->index('area_id')->comment('仓库地区ID');
            $table->integer('suppliers_id')->unsigned()->default(0)->comment('供货商id');
            $table->integer('add_time')->comment('添加时间');
            $table->string('batch_number', 50)->comment('批次号码');
            $table->string('remark')->comment('备注');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商品库存日志'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_inventory_logs');
    }
}
