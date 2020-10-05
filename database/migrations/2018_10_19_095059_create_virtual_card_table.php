<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateVirtualCardTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'virtual_card';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('card_id')->comment('自增ID');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品ID');
            $table->string('card_sn', 60)->default('')->index('car_sn')->comment('卡编号');
            $table->string('card_password', 60)->default('')->comment('卡密码');
            $table->integer('add_date')->default(0)->index('add_date')->comment('添加时间');
            $table->integer('end_date')->default(0)->index('end_date')->comment('结束时间');
            $table->boolean('is_saled')->default(0)->index('is_saled')->comment('是否卖出，0，否；1，是');
            $table->string('order_sn', 20)->default('')->index('order_sn')->comment('卖出该卡号的交易订单号，取值表dsc_order_info');
            $table->string('crc32', 12)->default('')->comment('crc32后的key');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '虚拟卡'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('virtual_card');
    }
}
