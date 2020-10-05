<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCouponsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'coupons';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('cou_id')->comment('自增ID');
            $table->string('cou_name', 128)->default('')->comment('优惠券类型名称');
            $table->integer('cou_total')->default(0)->comment('优惠券发放总数');
            $table->decimal('cou_man', 10, 0)->unsigned()->default(0)->comment('优惠券使用门槛');
            $table->decimal('cou_money', 10, 0)->unsigned()->default(0)->comment('优惠券金额');
            $table->integer('cou_user_num')->unsigned()->default(1)->comment('每人限领张数');
            $table->string('cou_goods')->default('0')->comment('优惠券可使用商品');
            $table->text('spec_cat')->comment('优惠券可使用分类');
            $table->integer('cou_start_time')->unsigned()->default(0)->comment('优惠券使用开始时间');
            $table->integer('cou_end_time')->unsigned()->default(0)->comment('优惠券使用结束时间');
            $table->boolean('cou_type')->default(1)->index('cou_type')->comment('优惠券类型');
            $table->decimal('cou_get_man', 10, 0)->default(0)->comment('购物满指定额度可获得优惠券');
            $table->string('cou_ok_user')->default('0')->comment('可使用优惠券用户组');
            $table->string('cou_ok_goods')->default('0')->comment('可使用优惠券商品');
            $table->text('cou_ok_cat')->comment('可使用优惠券分类');
            $table->text('cou_intro')->comment('优惠券类型介绍说明');
            $table->integer('cou_add_time')->unsigned()->default(0)->comment('优惠券类型添加时间');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID');
            $table->integer('cou_order')->unsigned()->default(0)->comment('优惠券订单');
            $table->string('cou_title')->default('')->comment('优惠券类型标题');
            $table->boolean('review_status')->default(1)->index('review_status')->comment('审核状态');
            $table->string('review_content', 1000)->comment('审核回复内容');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '优惠券'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('coupons');
    }
}
