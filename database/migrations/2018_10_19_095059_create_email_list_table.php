<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateEmailListTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'email_list';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('email', 60)->comment('邮件订阅所填的邮箱地址');
            $table->boolean('stat')->default(0)->comment('是否确认，可以用户确认也可以管理员确认；0，未确认；1，已确认');
            $table->string('hash', 10)->comment('邮箱确认的验证码，系统生成后发送到用户邮箱，用户验证激活时通过该值判断是否合法；主要用来防止非法验证邮箱');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '邮件杂志订阅列表'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('email_list');
    }
}
