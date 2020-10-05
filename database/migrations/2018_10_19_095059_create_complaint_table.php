<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateComplaintTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'complaint';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('complaint_id')->comment('自增ID');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单id');
            $table->string('order_sn')->index('order_sn')->comment('订单编号');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('会员id');
            $table->string('user_name', 60)->comment('会员名称');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家id');
            $table->string('shop_name', 60)->comment('店铺名称');
            $table->integer('title_id')->unsigned()->default(0)->index('title_id')->comment('投诉主题id');
            $table->text('complaint_content')->comment('投诉内容');
            $table->integer('add_time')->unsigned()->default(0)->comment('投诉时间');
            $table->integer('complaint_handle_time')->unsigned()->default(0)->comment('处理时间');
            $table->integer('admin_id')->unsigned()->default(0)->comment('管理员id');
            $table->text('appeal_messg')->comment('申诉内容');
            $table->integer('appeal_time')->unsigned()->default(0)->comment('申诉时间');
            $table->integer('end_handle_time')->default(0)->comment('最终处理时间');
            $table->integer('end_admin_id')->unsigned()->default(0)->comment('最终处理管理员id');
            $table->text('end_handle_messg')->comment('最终处理意见');
            $table->boolean('complaint_state')->default(0)->comment('投诉状态（0：未处理，1、待申诉，2、对话中，3、待仲裁，4完成）');
            $table->boolean('complaint_active')->default(0)->comment('是否在商家显示(0、不显示，1、显示)');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '交易纠纷'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('complaint');
    }
}
