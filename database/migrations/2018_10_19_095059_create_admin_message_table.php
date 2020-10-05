<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAdminMessageTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'admin_message';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('message_id')->comment('自增ID号');
            $table->boolean('sender_id')->default(0)->index()->comment('发送该留言的管理员id，同ecs_admin_user的user_id');
            $table->boolean('receiver_id')->default(0)->index('receiver_id')->comment('接收消息的管理员id，同ecs_admin_user的user_id，如果是给多个管理员发送，则同一个消息给每个管理员id发送一条');
            $table->integer('sent_time')->unsigned()->default(0)->comment('留言发送时间');
            $table->integer('read_time')->unsigned()->default(0)->comment('留言阅读时间');
            $table->boolean('readed')->default(0)->comment('留言是否阅读，1，已阅读；0，未阅读');
            $table->boolean('deleted')->default(0)->comment('留言是否已经是否已经被删除，1，已删除；0，未删除');
            $table->string('title', 150)->default('')->comment('留言的主题');
            $table->text('message')->comment('留言的内容');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '管理员留言记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('admin_message');
    }
}
