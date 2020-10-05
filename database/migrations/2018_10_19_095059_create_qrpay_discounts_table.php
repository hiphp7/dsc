<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateQrpayDiscountsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'qrpay_discounts';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->integer('ru_id')->unsigned()->default(0)->comment('商家ID');
            $table->decimal('min_amount', 10, 2)->default(0.00)->comment('满金额');
            $table->decimal('discount_amount', 10, 2)->default(0.00)->comment('优惠金额');
            $table->decimal('max_discount_amount', 10, 2)->default(0.00)->comment('最高优惠金额');
            $table->boolean('status')->default(0)->comment('优惠状态(0 关闭，1 开启)');
            $table->integer('add_time')->unsigned()->default(0)->comment('创建时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '扫码支付优惠列表'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('qrpay_discounts');
    }
}
