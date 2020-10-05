<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUserBonusTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'user_bonus';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('bonus_id')->comment('自增ID');
            $table->boolean('bonus_type_id')->default(0)->index('bonus_type_id')->comment('红包发送类型.0,按用户如会员等级,会员名称发放;1,按商品类别发送;2,按订单金额所达到的额度发送;3,线下发送');
            $table->bigInteger('bonus_sn')->unsigned()->default(0)->comment('红包号,如果为0就是没有红包号.如果大于0,就需要输入该红包号才能使用红包');
            $table->string('bonus_password', 60)->default('')->comment('红包密码');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('该红包属于某会员的id.如果为0,就是该红包不属于某会员');
            $table->integer('used_time')->unsigned()->default(0)->comment('红包使用的时间');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('使用了该红包的交易号');
            $table->boolean('emailed')->default(0)->index('emailed')->comment('猜的，应该是是否已经将红包发送到用户的邮箱；1，是；0，否');
            $table->integer('bind_time')->unsigned()->default(0)->comment('绑定时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '会员红包'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_bonus');
    }
}
