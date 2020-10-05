<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateImServiceTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'im_service';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->comment('管理员ID');
            $table->string('user_name', 60)->default('')->comment('管理员名称');
            $table->string('nick_name', 60)->default('')->comment('昵称');
            $table->string('post_desc', 60)->default('')->comment('描述');
            $table->integer('login_time')->unsigned()->default(0)->comment('管理员登录时间');
            $table->boolean('chat_status')->default(1)->comment('0-在线 1-离开  2-退出');
            $table->boolean('status')->default(1)->comment('0为删除， 1为正常， 2为暂停');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '客服'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('im_service');
    }
}
