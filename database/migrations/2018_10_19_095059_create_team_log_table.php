<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTeamLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'team_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('team_id')->comment('自增ID');
            $table->integer('goods_id')->unsigned()->default(0)->comment('拼团商品id');
            $table->integer('start_time')->unsigned()->default(0)->comment('开团时间');
            $table->boolean('status')->default(0)->comment('拼团状态（1成功，2失败）');
            $table->boolean('is_show')->default(1)->comment('是否显示');
            $table->integer('t_id')->unsigned()->default(0)->comment('拼团活动id');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '拼团日志'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('team_log');
    }
}
