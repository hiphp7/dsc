<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSaleNoticeTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'sale_notice';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->comment('会员ID');
            $table->integer('goods_id')->unsigned()->default(0)->comment('商品ID');
            $table->string('cellphone', 16)->default('')->comment('通知手机号');
            $table->string('email', 30)->default('')->comment('通知邮箱');
            $table->decimal('hopeDiscount', 10)->default(0.00)->comment('降价金额');
            $table->boolean('status')->default(2)->comment('发送状态');
            $table->boolean('send_type')->default(0)->comment('发送方式');
            $table->integer('add_time')->default(0)->comment('添加时间');
            $table->string('mark')->default('')->comment('备注');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '会员商品降价通知'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sale_notice');
    }
}
