<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSeckillTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seckill';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('sec_id')->comment('秒杀活动自增ID');
            $table->integer('ru_id')->unsigned()->index('ru_id')->comment('商家ID');
            $table->string('acti_title', 50)->comment('秒杀活动标题');
            $table->integer('begin_time')->comment('秒杀活动开始时间');
            $table->boolean('is_putaway')->default(1)->comment('上下架');
            $table->integer('acti_time')->comment('秒杀活动结束日期');
            $table->integer('add_time')->comment('秒杀活动添加时间');
            $table->boolean('review_status')->default(1)->index('review_status')->comment('审核状态');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '秒杀活动'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('seckill');
    }
}
