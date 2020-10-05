<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateImMessageTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'im_message';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('from_user_id')->unsigned()->default(0)->comment('客服对应 im_customer id  客户对应 用户表ID');
            $table->integer('to_user_id')->unsigned()->default(0)->comment('客服对应 im_customer id  客户对应 用户表ID');
            $table->integer('dialog_id')->unsigned()->default(0)->comment('会话记录');
            $table->text('message')->comment('聊天内容');
            $table->integer('add_time')->unsigned()->default(0)->comment('会话记录');
            $table->boolean('user_type')->default(0)->comment('消息属于  1-客服 2-用户');
            $table->boolean('status')->default(0)->comment('0为已读  1为未读');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '客服消息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('im_message');
    }
}
