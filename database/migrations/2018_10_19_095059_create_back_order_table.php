<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBackOrderTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'back_order';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('back_id')->comment('自增ID号');
            $table->string('delivery_sn', 30)->default('')->comment('退货单号');
            $table->string('order_sn', 30)->default('')->comment('订单号');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单ID');
            $table->string('invoice_no', 50)->default('')->comment('运单号');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->boolean('shipping_id')->default(0)->comment('配送方式ID');
            $table->string('shipping_name', 120)->default('')->comment('配送方式名称');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('会员ID');
            $table->string('action_user', 30)->default('')->comment('操作该次的人员');
            $table->string('consignee', 60)->default('')->comment('收货人');
            $table->string('address')->default('')->comment('收货地址');
            $table->integer('country')->unsigned()->default(0)->index('country')->comment('国家');
            $table->integer('province')->unsigned()->default(0)->index('province')->comment('省份');
            $table->integer('city')->unsigned()->default(0)->index('city')->comment('城市');
            $table->integer('district')->unsigned()->default(0)->index('district')->comment('地区');
            $table->integer('street')->unsigned()->default(0)->index('street')->comment('街道');
            $table->string('sign_building', 120)->default('')->comment('建筑物（标识）');
            $table->string('email', 60)->default('')->comment('邮箱地址');
            $table->string('zipcode', 60)->default('')->comment('邮政编号');
            $table->string('tel', 60)->default('')->comment('电话');
            $table->string('mobile', 60)->default('')->comment('手机');
            $table->string('best_time', 120)->default('')->comment('送货时间');
            $table->string('postscript')->default('')->comment('订单附言，由用户提交订单前填写');
            $table->string('how_oos', 120)->default('')->comment('缺货处理方式，等待所有商品备齐后再发； 取消订单；与店主协商');
            $table->decimal('insure_fee', 10)->unsigned()->default(0.00)->comment('保价费用');
            $table->decimal('shipping_fee', 10)->unsigned()->default(0.00)->comment('配送费用');
            $table->integer('update_time')->unsigned()->default(0)->comment('更新时间');
            $table->integer('suppliers_id')->unsigned()->default(0)->comment('供货商ID');
            $table->boolean('status')->default(0)->comment('状态：0 发货， 1 退货， 2 正常');
            $table->integer('return_time')->unsigned()->default(0)->comment('退货时间');
            $table->integer('agency_id')->unsigned()->default(0)->comment('该笔订单被指派给的办事处的id，根据订单内容和办事处负责范围自动决定，也可以有管理员修改，取值于表agency');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '退货订单'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('back_order');
    }
}
