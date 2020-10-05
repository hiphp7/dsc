<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePresaleActivityTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'presale_activity';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('act_id')->comment('自增ID号');
            $table->string('act_name')->default('')->comment('预售活动名称');
            $table->integer('cat_id')->unsigned()->default(0)->index('cat_id')->comment('活动分类');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('商家ID（同dsc_users表user_id）');
            $table->integer('goods_id')->unsigned()->index('goods_id')->comment('商品ID');
            $table->string('goods_name')->index('goods_name')->comment('商品名称');
            $table->text('act_desc')->nullable()->comment('活动描述');
            $table->decimal('deposit', 10)->default(0.00)->comment('活动定金');
            $table->integer('start_time')->unsigned()->default(0)->index('start_time')->comment('活动开始时间');
            $table->integer('end_time')->unsigned()->default(0)->index('end_time')->comment('活动结束时间');
            $table->integer('pay_start_time')->unsigned()->default(0)->comment('支付开始时间');
            $table->integer('pay_end_time')->unsigned()->default(0)->comment('支付结束时间');
            $table->boolean('is_finished')->default(0)->comment('活动是否结束（0否，1是）');
            $table->boolean('review_status')->default(1)->index('review_status')->comment('审核状态');
            $table->string('review_content', 1000)->default('')->comment('审核回复内容');
            $table->integer('pre_num')->unsigned()->default(0)->comment('预约商品人数');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '商品预售活动'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('presale_activity');
    }
}
