<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTableDrpUpgradeValue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'drp_upgrade_values';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('value', 60)->comment('条件字段的值');
            $table->integer('condition_id')->unsigned()->default(0)->index('condition_id')->comment('绑定的条件字段ID');
            $table->integer('credit_id')->unsigned()->default(0)->index('credit_id')->comment('绑定的等级表id');
            $table->integer('award_num')->default(0)->comment('奖励金额');
            $table->boolean('type')->default(0)->comment('奖励类型 0 积分  1 余额');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '分销商升级条件值记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drp_upgrade_values');
    }
}
