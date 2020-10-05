<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBargainStatisticsLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'bargain_statistics_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('bargain_id')->unsigned()->default(0)->comment('活动id');
            $table->string('goods_attr_id')->default('')->comment('属性id');
            $table->integer('user_id')->unsigned()->default(0)->comment('会员id');
            $table->decimal('final_price', 10)->default(0.00)->comment('砍后最终购买价');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('count_num')->unsigned()->default(0)->comment('参与人次');
            $table->boolean('status')->default(0)->comment('状态（1完成）');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '砍价活动日志'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('bargain_statistics_log');
    }
}
