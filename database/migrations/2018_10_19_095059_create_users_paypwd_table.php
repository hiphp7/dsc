<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUsersPaypwdTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'users_paypwd';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('paypwd_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('用户ID');
            $table->string('ec_salt', 10)->nullable()->comment('加密扩展');
            $table->string('pay_password', 32)->default('')->comment('支付密码');
            $table->boolean('pay_online')->default(0)->comment('是否在线支付');
            $table->boolean('user_surplus')->default(0)->comment('是否余额支付');
            $table->boolean('user_point')->default(0)->comment('是否使用积分');
            $table->boolean('baitiao')->default(0)->comment('是否白条支付');
            $table->boolean('gift_card')->default(0)->comment('是否礼品卡');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '会员消费支付密码'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users_paypwd');
    }
}
