<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCouponsUserTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'coupons_user';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('uc_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->nullable()->index('user_id')->comment('用户ID');
            $table->integer('cou_id')->nullable()->index('cou_id')->comment('优惠券ID');
            $table->decimal('cou_money', 10, 0)->unsigned()->default(0)->comment('优惠券金额');
            $table->boolean('is_use')->default(0)->index('is_use')->comment('是否使用');
            $table->char('uc_sn', 12)->default(0)->comment('使用编号');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单ID');
            $table->integer('is_use_time')->unsigned()->default(0)->comment('使用时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '优惠券会员用户'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('coupons_user');
    }
}
