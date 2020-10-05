<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateValueCardTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'value_card';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('vid')->comment('自增ID');
            $table->integer('tid')->index('tid')->comment('储值卡类型ID');
            $table->string('value_card_sn', 30)->unique('value_card_sn')->comment('储值卡序列号');
            $table->string('value_card_password', 20)->default('')->comment('储值卡密码');
            $table->integer('user_id')->default(0)->index('user_id')->comment('绑定用户ID');
            $table->integer('vc_value')->default(0)->comment('储值卡面值');
            $table->decimal('card_money', 10, 2)->unsigned()->default(0.00)->comment('储值卡余额');
            $table->integer('bind_time')->default(0)->comment('绑定时间');
            $table->integer('end_time')->default(0)->comment('截止日期');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '储值卡'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('value_card');
    }
}
