<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesaleOrderInfoTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_order_info';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('order_id')->comment('自增ID');
            $table->integer('main_order_id')->unsigned()->default(0)->index('main_order_id')->comment('区分主订单作用（0为主订单，大于0是子订单）');
            $table->string('order_sn', 100)->default('')->unique('order_sn')->comment('订单号');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('会员id');
            $table->boolean('order_status')->default(0)->index('order_status')->comment('订单状态');
            $table->string('consignee', 60)->default('')->comment('收货人地址');
            $table->integer('country')->unsigned()->default(0)->index('country')->comment('国家id');
            $table->integer('province')->unsigned()->default(0)->index('province')->comment('省份id');
            $table->integer('city')->unsigned()->default(0)->index('city')->comment('城市id');
            $table->integer('district')->unsigned()->default(0)->index('district')->comment('地区id');
            $table->integer('street')->unsigned()->default(0)->index('street')->comment('街道id');
            $table->string('address')->default('')->comment('详细地址');
            $table->string('mobile', 60)->default('')->comment('手机号');
            $table->string('email', 60)->default('')->comment('邮箱');
            $table->string('postscript')->default('')->comment('留言');
            $table->string('inv_payee', 120)->default('')->comment('发票抬头，用户页面填写');
            $table->string('inv_content', 120)->default('')->comment('发票内容');
            $table->decimal('order_amount', 10)->default(0.00)->comment('订单金额');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->string('extension_code', 30)->default('')->comment('活动标识');
            $table->string('inv_type', 60)->default('')->comment('发票类型');
            $table->decimal('tax', 10)->default(0.00)->comment('发票税额');
            $table->boolean('is_delete')->default(0)->comment('会员操作删除订单状态（0为删除，1删除回收站，2会员订单列表不显示该订单信息）');
            $table->boolean('invoice_type')->default(0)->comment('发票类型 0:普通发票、1:增值税发票');
            $table->integer('vat_id')->default(0)->comment('增值税发票信息ID 关联 users_vat_invoices_info表自增ID');
            $table->string('tax_id')->default('')->comment('纳税人识别号');
            $table->integer('pay_id')->unsigned()->default(0)->comment('支付方式id');
            $table->boolean('pay_status')->default(0)->comment('支付状态');
            $table->integer('pay_time')->unsigned()->default(0)->comment('支付时间');
            $table->decimal('pay_fee', 10)->default(0.00)->comment('支付费用');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '批发订单'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_order_info');
    }
}
