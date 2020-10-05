<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSellerCommissionBillTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seller_commission_bill';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('seller_id')->unsigned()->default(0)->index('seller_id')->comment('商家id');
            $table->string('bill_sn')->default('')->index('bill_sn')->comment('账单编号');
            $table->decimal('order_amount', 10, 2)->unsigned()->default(0.00)->comment('订单总额');
            $table->decimal('shipping_amount', 10, 2)->unsigned()->default(0.00)->comment('运费总金额');
            $table->decimal('return_amount', 10, 2)->unsigned()->default(0.00)->comment('退款总额');
            $table->decimal('return_shippingfee', 10, 2)->unsigned()->default(0.00)->comment('订单退货运费');
            $table->decimal('drp_money', 10, 2)->unsigned()->default(0.00)->comment('分销金额');
            $table->string('proportion', 20)->default('')->comment('佣金比例');
            $table->boolean('commission_model')->default(-1)->comment('佣金模式（0：按商家比例 1：按平台分类比例）');
            $table->decimal('gain_commission', 10, 2)->unsigned()->default(0.00)->comment('收取佣金金额');
            $table->decimal('should_amount', 10, 2)->unsigned()->default(0.00)->comment('本期结算');
            $table->decimal('actual_amount', 10, 2)->unsigned()->default(0.00)->comment('实结金额（账单结束）');
            $table->integer('chargeoff_time')->unsigned()->default(0)->index('chargeoff_time')->comment('出账时间');
            $table->integer('settleaccounts_time')->unsigned()->default(0)->comment('结账时间');
            $table->integer('start_time')->unsigned()->default(0)->index('start_time')->comment('开始时间');
            $table->integer('end_time')->unsigned()->default(0)->index('end_time')->comment('结束时间');
            $table->boolean('chargeoff_status')->default(0)->index('chargeoff_status')->comment('出账状态 0:未出账 1:已出账 2:账单结束 3:关闭账单');
            $table->boolean('bill_cycle')->default(2)->index('bill_cycle')->comment('账单结算周期类型');
            $table->boolean('bill_apply')->default(0)->index('bill_apply')->comment('商家申请账单 (0:未申请 1:已申请)');
            $table->string('apply_note')->default('')->comment('申请描述');
            $table->integer('apply_time')->unsigned()->default(0)->comment('申请时间');
            $table->string('operator')->default('')->comment('触发产生账单管理员');
            $table->boolean('check_status')->default(0)->comment('审核账单状态（0：待处理 1：同意 2：拒绝）');
            $table->string('reject_note')->default('')->comment('拒绝账单内容');
            $table->integer('check_time')->unsigned()->default(0)->comment('审核账单时间');
            $table->decimal('frozen_money', 10, 2)->unsigned()->default(0.00)->comment('账单冻结资金');
            $table->integer('frozen_data')->unsigned()->default(0)->comment('账单冻结时间（天）');
            $table->integer('frozen_time')->unsigned()->default(0)->comment('操作冻结时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商家账单'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('seller_commission_bill');
    }
}
