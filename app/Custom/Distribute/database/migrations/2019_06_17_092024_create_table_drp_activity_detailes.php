<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTableDrpActivityDetailes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'drp_activity_detailes';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->text('act_name')->comment('活动名称');
            $table->text('act_dsc')->comment('活动描述');
            $table->tinyInteger('act_type')->default(0)->comment('活动类型。 0 指定商品分享点击次数  1 指定商品分享下单付款次数');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品ID');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID');
            $table->integer('start_time')->unsigned()->default(0)->comment('活动开始时间');
            $table->integer('end_time')->unsigned()->default(0)->comment('活动结束时间');
            $table->text('text_info')->comment('活动要求数量值');
            $table->tinyInteger('is_finish')->default(0)->comment('活动是否开启  0 关闭  1 开启 ');
            $table->integer('raward_money')->unsigned()->default(0)->comment('奖励金额');
            $table->integer('act_type_share')->unsigned()->default(0)->comment('指定商品分享点击次数');
            $table->integer('act_type_place')->unsigned()->default(0)->comment('指定商品分享下单付款次数');
            $table->tinyInteger('raward_type')->default(0)->comment('奖励类型  0  积分  1  余额  2  佣金');
            $table->tinyInteger('complete_required')->default(0)->comment('活动完成条件  0  全部完成  1  完成任意一个');
            $table->integer('add_time')->unsigned()->default(0)->comment('活动添加时间');
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
        Schema::dropIfExists('drp_activity_detailes');
    }
}
