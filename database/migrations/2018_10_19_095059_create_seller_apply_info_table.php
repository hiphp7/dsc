<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSellerApplyInfoTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seller_apply_info';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('apply_id')->comment('自增ID');
            $table->integer('ru_id')->default(0)->index('ru_id')->comment('商家ID');
            $table->integer('grade_id')->default(0)->index('grade_id')->comment('等级ID');
            $table->string('apply_sn', 20)->index('apply_sn')->comment('申请序列号');
            $table->boolean('pay_status')->default(0)->index('pay_status')->comment('支付状态');
            $table->boolean('apply_status')->default(0)->index('apply_status')->comment('申请状态');
            $table->decimal('total_amount', 10, 2)->default(0.00)->comment('总金额');
            $table->decimal('payable_amount', 10, 2)->default(0.00)->comment('应付款');
            $table->decimal('refund_price', 10, 2)->default(0.00)->comment('退款金额');
            $table->decimal('back_price', 10, 2)->default(0.00)->comment('返还金额');
            $table->integer('fee_num')->unsigned()->default(1)->comment('申请数量');
            $table->decimal('pay_fee', 10, 2)->default(0.00)->comment('支付费用');
            $table->text('entry_criteria')->nullable()->comment('入驻标准');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->boolean('is_confirm')->default(0)->comment('是否确认');
            $table->integer('pay_time')->unsigned()->default(0)->comment('支付时间');
            $table->integer('pay_id')->unsigned()->default(0)->index('pay_id')->comment('支付ID');
            $table->boolean('is_paid')->default(0)->index('is_paid')->comment('是否已支付');
            $table->integer('confirm_time')->unsigned()->default(0)->comment('确认时间');
            $table->string('reply_seller')->default('0')->comment('答复商家');
            $table->boolean('valid')->default(0)->comment('是否有效');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商家等级申请信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('seller_apply_info');
    }
}
