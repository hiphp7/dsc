<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesaleDeliveryOrderTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_delivery_order';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('delivery_id')->comment('自增ID');
            $table->string('delivery_sn', 20)->comment('发货单号');
            $table->string('order_sn', 20)->comment('订单号');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单ID');
            $table->string('invoice_no', 50)->nullable()->comment('快递单号');
            $table->integer('add_time')->unsigned()->nullable()->default(0)->comment('添加时间');
            $table->boolean('shipping_id')->nullable()->default(0)->comment('快递ID');
            $table->string('shipping_name', 120)->nullable()->comment('快递名称');
            $table->integer('user_id')->unsigned()->nullable()->default(0)->index('user_id')->comment('会员名称');
            $table->string('action_user', 30)->nullable()->comment('操作该次的人员');
            $table->string('consignee', 60)->nullable()->comment('收货人');
            $table->string('address', 250)->nullable()->comment('收货人地址');
            $table->smallInteger('country')->unsigned()->nullable()->default(0)->comment('国家');
            $table->smallInteger('province')->unsigned()->nullable()->default(0)->comment('省份');
            $table->smallInteger('city')->unsigned()->nullable()->default(0)->comment('城市');
            $table->smallInteger('district')->unsigned()->nullable()->default(0)->comment('地区');
            $table->string('sign_building', 120)->nullable()->comment('建筑物（标识）');
            $table->string('email', 60)->nullable()->comment('收货人邮箱地址');
            $table->string('zipcode', 60)->nullable()->comment('收货人邮政编号');
            $table->string('tel', 60)->nullable()->comment('收货人电话');
            $table->string('mobile', 60)->nullable()->comment('收货人手机号');
            $table->string('best_time', 120)->nullable()->comment('配送时间');
            $table->string('postscript')->nullable()->comment('订单附言，由用户提交订单前填写');
            $table->string('how_oos', 120)->nullable()->comment('缺货处理方式，等待所有商品备齐后再发； 取消订单；与店主协商');
            $table->decimal('insure_fee', 10)->unsigned()->nullable()->default(0.00)->comment('自增ID');
            $table->decimal('shipping_fee', 10)->unsigned()->nullable()->default(0.00)->comment('自增ID');
            $table->integer('update_time')->unsigned()->nullable()->default(0)->comment('保价费用');
            $table->smallInteger('suppliers_id')->nullable()->default(0)->comment('供货商ID');
            $table->boolean('status')->default(0)->comment('发货状态 0 已发货， 1 退款， 2 生成发货单');
            $table->smallInteger('agency_id')->unsigned()->nullable()->default(0)->comment('办事处ID');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '供应链订单发货单'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_delivery_order');
    }

}
