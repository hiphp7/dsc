<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOrderCloudTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'order_cloud';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('apiordersn')->default('')->comment('api订单号');
            $table->integer('goods_id')->unsigned()->default(0)->comment('商品ID');
            $table->integer('user_id')->unsigned()->default(0)->comment('会员ID');
            $table->decimal('totalprice', 10)->unsigned()->default(0.00)->comment('订单金额');
            $table->integer('rec_id')->unsigned()->default(0)->comment('记录ID');
            $table->string('parentordersn')->default('')->comment('主订单号');
            $table->integer('cloud_orderid')->unsigned()->default(0)->comment('云订单ID');
            $table->integer('cloud_detailed_id')->unsigned()->default(0)->comment('贡云订单明细id');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '贡云订单信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('order_cloud');
    }
}
