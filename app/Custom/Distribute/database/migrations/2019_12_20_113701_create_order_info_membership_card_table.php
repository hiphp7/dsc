<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOrderInfoMembershipCardTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'order_info_membership_card';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('order_id')->unsigned()->default(0)->comment('关联订单表order_id');
            $table->integer('user_id')->unsigned()->default(0)->comment('关联会员表user_id');
            $table->decimal('order_amount', 10, 2)->unsigned()->default(0.00)->comment('购买权益卡订单应付金额(不含购买权益卡金额)');
            $table->integer('membership_card_id')->unsigned()->default(0)->comment('购买会员权益卡ID');
            $table->decimal('membership_card_buy_money', 10, 2)->unsigned()->default(0.00)->comment('购买会员权益卡金额');
            $table->decimal('membership_card_discount_price', 10, 2)->unsigned()->default(0.00)->comment('购买会员权益卡折扣');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '订单开通购买会员权益卡表'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_info_membership_card');
    }
}
