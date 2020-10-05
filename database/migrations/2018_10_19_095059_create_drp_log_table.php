<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDrpLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'drp_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('log_id')->comment('自增ID');
            $table->integer('order_id')->unsigned()->default(0)->index()->comment('订单id');
            $table->integer('time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('user_id')->unsigned()->default(0)->index()->comment('会员ID');
            $table->string('user_name')->default('')->comment('会员名称');
            $table->decimal('money', 10)->default(0.00)->comment('分成佣金');
            $table->integer('point')->unsigned()->default(0)->comment('分成积分');
            $table->boolean('drp_level')->default(0)->comment('分成等级');
            $table->boolean('is_separate')->default(0)->comment('是否分销');
            $table->boolean('separate_type')->default(0)->comment('记录状态');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '分销记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('drp_log');
    }
}
