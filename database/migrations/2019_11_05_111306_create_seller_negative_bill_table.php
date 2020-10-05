<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSellerNegativeBillTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seller_negative_bill';
        if (Schema::hasTable($name)) {
            return false;
        }

        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->string('bill_sn', 30)->default('')->comment('负账单单号');
            $table->string('commission_bill_sn', 30)->default('账单编号');
            $table->integer('commission_bill_id')->unsigned()->default(0)->index('commission_bill_id')->comment('账单ID');
            $table->integer('seller_id')->unsigned()->default(0)->index('seller_id')->comment('商家ID');
            $table->decimal('return_amount')->unsigned()->default('0.00')->comment('负账单总金额');
            $table->decimal('return_shippingfee')->unsigned()->default('0.00')->comment('负账单退款总金额');
            $table->boolean('chargeoff_status')->unsigned()->default(0)->comment('账单状态（0 未处理， 1已处理）');
            $table->integer('start_time')->unsigned()->default(0)->comment('负账单开始时间');
            $table->integer('end_time')->unsigned()->default(0)->comment('负账单结束时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_negative_bill');
    }
}
