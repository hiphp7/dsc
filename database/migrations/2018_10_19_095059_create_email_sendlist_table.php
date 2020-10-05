<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateEmailSendlistTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'email_sendlist';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('email', 100)->comment('该邮件将要发送到的邮箱地址');
            $table->integer('template_id')->index('template_id')->comment('该邮件的模板id，取值于dsc_mail_templates的template_id');
            $table->text('email_content')->comment('邮件发送的内容');
            $table->boolean('error')->default(0)->comment('错误次数，不知干什么用的，猜应该是发送邮件的失败记录');
            $table->boolean('pri')->comment('该邮件发送的优先级；0，普通；1，高');
            $table->integer('last_send')->comment('上一次发送的时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '邮件发送队列列表'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('email_sendlist');
    }
}
