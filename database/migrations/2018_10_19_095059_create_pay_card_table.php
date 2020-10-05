<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePayCardTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'pay_card';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->string('card_number', 60)->unique('card_number')->comment('充值卡卡号');
            $table->string('card_psd', 40)->comment('充值卡密码');
            $table->integer('user_id')->index('user_id')->comment('用户ID');
            $table->string('used_time', 40)->comment('使用时间');
            $table->integer('status')->unsigned()->nullable()->default(0)->comment('状态');
            $table->integer('c_id')->unsigned()->comment('充值卡类型ID');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '充值卡'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pay_card');
    }
}
