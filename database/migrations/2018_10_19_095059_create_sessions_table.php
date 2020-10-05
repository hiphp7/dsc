<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSessionsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'sessions';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->string('sesskey', 32)->primary()->comment('session值');
            $table->integer('expiry')->unsigned()->default(0)->index('expiry')->comment('session创建时间');
            $table->integer('userid')->unsigned()->default(0)->index('userid')->comment('如果不是管理员，记录用户id');
            $table->integer('adminid')->unsigned()->default(0)->comment('如果是管理员记录管理员id');
            $table->string('ip', 15)->default('')->comment('客户端ip');
            $table->string('user_name', 60)->default('')->comment('会员名称');
            $table->boolean('user_rank')->default(0)->comment('会员等级');
            $table->decimal('discount', 3)->default(0.00)->comment('会员折扣');
            $table->string('email', 60)->default('')->comment('邮箱地址');
            $table->text('data')->nullable()->comment('session序列化后的数据');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment 'session记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sessions');
    }
}
