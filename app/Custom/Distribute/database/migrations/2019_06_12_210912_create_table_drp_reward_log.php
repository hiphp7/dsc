<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTableDrpRewardLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'drp_reward_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('reward_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('分销商ID');
            $table->integer('activity_id')->unsigned()->default(0)->index('activity_id')->comment('活动ID');
            $table->integer('award_money')->unsigned()->default(0)->comment('奖励金额');
            $table->tinyInteger('award_type')->default(1)->comment('奖励类型。0，积分；1，余额');
            $table->tinyInteger('activity_type')->default(1)->comment('活动类型。0，奖励活动；1，升级奖励');
            $table->tinyInteger('award_status')->default(0)->comment('奖励状态。0，未完成；1，已完成');
            $table->tinyInteger('participation_status')->default(0)->comment('参与状态。0，未完成；1，已完成');
            $table->integer('completeness_share')->unsigned()->default(0)->comment('分享量统计');
            $table->integer('completeness_place')->unsigned()->default(0)->comment('下单量统计');
            $table->integer('credit_id')->unsigned()->default(0)->index('credit_id')->comment('升级奖励绑定的等级ID');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '分销商活动/升级奖励记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drp_reward_log');
    }
}
