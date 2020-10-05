<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTableDrpActivityRewardLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'drp_activity_reward_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('activity_id')->unsigned()->index('activity_id')->comment('活动ID');
            $table->integer('user_id')->unsigned()->index('user_id')->comment('用户ID');
            $table->integer('drp_user_id')->unsigned()->index('drp_user_id')->comment('分销商ID');
            $table->integer('order_id')->default(0)->index('order_id')->comment('订单ID');
            $table->integer('add_num')->default(1)->comment('添加数量');
            $table->tinyInteger('is_effect')->default(1)->comment('记录是否生效  0 不生效  1 生效 ');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
        });
        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '分销商活动信息记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drp_activity_reward_log');
    }
}
