<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSeckillGoodsRemindTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seckill_goods_remind';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('r_id')->comment('自增秒杀提醒ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('用户ID');
            $table->integer('sec_goods_id')->unsigned()->default(0)->index('sec_goods_id')->comment('秒杀商品ID');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '秒杀商品提醒'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('seckill_goods_remind');
    }
}
