<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSeckillGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seckill_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('sec_id')->unsigned()->default(0)->index('sec_id')->comment('秒杀ID（关联seckill表sec_id）');
            $table->integer('tb_id')->unsigned()->default(0)->index('tb_id')->comment('秒杀时段ID（关联seckill_time_bucket表id）');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品ID');
            $table->decimal('sec_price', 10, 2)->comment('秒杀商品价格');
            $table->smallInteger('sec_num')->comment('秒杀商品数量');
            $table->boolean('sec_limit')->comment('秒杀限购数量');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '秒杀商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('seckill_goods');
    }
}
