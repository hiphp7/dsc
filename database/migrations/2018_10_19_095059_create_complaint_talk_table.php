<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateComplaintTalkTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'complaint_talk';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('talk_id')->comment('自增ID');
            $table->integer('complaint_id')->unsigned()->index('complaint_id')->comment('投诉id');
            $table->integer('talk_member_id')->unsigned()->index('talk_member_id')->comment('发言人id');
            $table->string('talk_member_name', 30)->comment('发言人名称');
            $table->boolean('talk_member_type')->default(0)->comment('发言人类型 （1、投诉人，2、被投诉人，3、平台）');
            $table->string('talk_content')->comment('谈话内容');
            $table->boolean('talk_state')->default(1)->comment('发言状态（1、显示，2、不显示）');
            $table->integer('admin_id')->unsigned()->default(0)->comment('管理员id');
            $table->integer('talk_time')->default(0)->comment('发言时间');
            $table->string('view_state', 60)->comment('查看状态');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '交易纠纷对话信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('complaint_talk');
    }
}
