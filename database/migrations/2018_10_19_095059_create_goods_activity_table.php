<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGoodsActivityTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'goods_activity';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('act_id')->comment('自增ID');
            $table->string('act_name')->index('act_name')->comment('活动名称');
            $table->integer('user_id')->unsigned()->index('user_id')->comment('商家ID（同dsc_users表user_id');
            $table->text('act_desc')->comment('活动描述');
            $table->string('activity_thumb')->comment('活动图片');
            $table->text('act_promise')->comment('服务保障');
            $table->text('act_ensure')->comment('竞拍攻略');
            $table->boolean('act_type')->index('act_type')->comment('活动类型');
            $table->integer('goods_id')->unsigned()->index('goods_id')->comment('商品ID');
            $table->integer('product_id')->unsigned()->default(0)->index('product_id')->comment('商品货品ID');
            $table->string('goods_name')->index('goods_name')->comment('商品名称');
            $table->integer('start_time')->unsigned()->comment('活动开始时间');
            $table->integer('end_time')->unsigned()->comment('活动结束时间');
            $table->boolean('is_finished')->comment('活动是否结束（0-否，1-是）');
            $table->text('ext_info')->comment('活动填写内容信息');
            $table->boolean('is_hot')->default(0)->index('is_hot')->comment('是否热销（0-否，1是）');
            $table->boolean('is_new')->default(0)->index('is_new')->comment('是否新品（0-否，1是）');
            $table->boolean('review_status')->default(1)->index('review_status')->comment('审核状态');
            $table->string('review_content', 1000)->comment('审核回复内容');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '拍卖活动和夺宝奇兵活动配置信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_activity');
    }
}
