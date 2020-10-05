<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryOrderTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'delivery_order';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('delivery_id')->comment('自增ID');
            $table->string('delivery_sn', 20)->index('delivery_sn')->comment('发货单号');
            $table->string('order_sn', 20)->index('order_sn')->comment('订单号');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单ID');
            $table->string('invoice_no', 50)->default('')->comment('运单号');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->boolean('shipping_id')->default(0)->index('shipping_id')->comment('配送方式ID');
            $table->string('shipping_name', 120)->default('')->comment('配送方式名称');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('会员ID');
            $table->string('action_user', 30)->default('')->comment('操作该次的人员');
            $table->string('consignee', 60)->default('')->comment('收货人姓名');
            $table->string('address', 250)->default('')->comment('收货地址');
            $table->integer('country')->unsigned()->default(0)->index('country')->comment('国家');
            $table->integer('province')->unsigned()->default(0)->index('province')->comment('省份');
            $table->integer('city')->unsigned()->default(0)->index('city')->comment('城市');
            $table->integer('district')->unsigned()->default(0)->index('district')->comment('地区');
            $table->integer('street')->unsigned()->default(0)->index('street')->comment('街道');
            $table->string('sign_building', 120)->default('')->comment('建筑物（标识）');
            $table->string('email', 60)->default('')->comment('收货人邮箱地址');
            $table->string('zipcode', 60)->default('')->comment('收货人邮政编号');
            $table->string('tel', 60)->default('')->comment('收货人电话');
            $table->string('mobile', 60)->default('')->comment('收货人手机号');
            $table->string('best_time', 120)->default('')->comment('配送时间');
            $table->string('postscript')->default('')->comment('订单附言，由用户提交订单前填写');
            $table->string('how_oos', 120)->default('')->comment('缺货处理方式，等待所有商品备齐后再发； 取消订单；与店主协商');
            $table->decimal('insure_fee', 10)->unsigned()->default(0.00)->comment('保价费用');
            $table->decimal('shipping_fee', 10)->unsigned()->default(0.00)->comment('配送费用');
            $table->integer('update_time')->unsigned()->default(0)->comment('更新时间');
            $table->integer('suppliers_id')->unsigned()->default(0)->index('suppliers_id')->comment('供应商ID');
            $table->boolean('status')->default(0)->index('status')->comment('发货状态 0 已发货， 1 退款， 2 生成发货单');
            $table->integer('agency_id')->unsigned()->default(0)->index('agency_id')->comment('办事处ID');
            $table->boolean('is_zc_order')->default(0)->comment('是否是众筹订单');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '订单发货单'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('delivery_order');
    }
}
