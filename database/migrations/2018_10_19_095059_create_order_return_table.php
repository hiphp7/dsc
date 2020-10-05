<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOrderReturnTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'order_return';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('ret_id')->comment('退换货id');
            $table->string('return_sn', 20)->index('return_sn')->comment('退换货订单编号');
            $table->integer('goods_id')->index('goods_id')->comment('商品唯一id');
            $table->integer('user_id')->index('user_id')->comment('用户id');
            $table->integer('rec_id')->index('rec_id')->comment('订单商品唯一id');
            $table->integer('order_id')->index('order_id')->comment('所属订单号');
            $table->string('order_sn', 20)->index('order_sn')->comment('订单编号');
            $table->boolean('credentials')->default(0)->comment('有检测报告');
            $table->boolean('maintain')->default(0)->comment('维修标识');
            $table->boolean('back')->default(0)->comment('退货标识');
            $table->text('goods_attr')->comment('退货颜色属性');
            $table->boolean('exchange')->default(0)->comment('换货标识');
            $table->boolean('return_type')->default(0)->comment('退换货标识：0：维修，1：退货，2：换货，3：仅退款');
            $table->text('attr_val')->comment('换货属性');
            $table->integer('cause_id')->comment('退换货原因');
            $table->integer('apply_time')->default(0)->comment('退换货申请时间');
            $table->integer('return_time')->default(0)->comment('退换货时间');
            $table->decimal('should_return', 10, 2)->unsigned()->default(0.00)->comment('应退金额');
            $table->decimal('actual_return', 10, 2)->unsigned()->default(0.00)->comment('实退金额');
            $table->decimal('return_shipping_fee', 10, 2)->unsigned()->comment('退换货配送金额');
            $table->string('return_brief', 2000)->comment('退换货描述');
            $table->string('remark', 2000)->comment('备注');
            $table->integer('country')->comment('国家');
            $table->integer('province')->comment('省份');
            $table->integer('city')->comment('城市');
            $table->integer('district')->comment('区');
            $table->integer('street')->unsigned()->default(0)->comment('街道');
            $table->string('addressee', 30)->comment('收件人');
            $table->char('phone', 22)->comment('联系电话');
            $table->string('address', 100)->comment('详细地址');
            $table->integer('zipcode')->nullable()->comment('邮编');
            $table->boolean('is_check')->comment('是否审核0：未审核1：已审核');
            $table->boolean('return_status')->comment('-1：仅退款，0：申请，1：收到退换货，2：换出商品寄出 分单，3：换出商品寄出，4：完成退换货，5：同意申请，6：拒绝申请');
            $table->boolean('refound_status')->comment('0：未退款，1：已退款，2：已换货，3：已维修，4：未换货，5：未维修');
            $table->string('back_shipping_name', 30)->comment('退回快递名称');
            $table->string('back_other_shipping', 30)->comment('其他快递名称');
            $table->string('back_invoice_no', 50)->comment('退回快递单号');
            $table->string('out_shipping_name', 30)->comment('换出快递名称');
            $table->string('out_invoice_no', 50)->comment('换出快递单号');
            $table->boolean('agree_apply')->default(0)->comment('是否收到用户退回商品（0否，1是）');
            $table->boolean('chargeoff_status')->default(0)->comment('账单 (0:未结账 1:已出账 2:已结账单)');
            $table->boolean('activation_number')->default(0)->comment('激活次数');
            $table->boolean('refund_type')->default(1)->comment('判断退换货订单退款方式：1 退还余额,2 线下退款 3 不处理, 6 原路退款');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商品退换货订单'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('order_return');
    }
}
