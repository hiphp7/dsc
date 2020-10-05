<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSellerTemplateApplyTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seller_template_apply';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('apply_id')->comment('自增ID');
            $table->string('apply_sn', 50)->default('')->index('apply_sn')->comment('购买模板订单号');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家id');
            $table->integer('temp_id')->unsigned()->default(0)->index('temp_id')->comment('模板id');
            $table->string('temp_code', 60)->default('')->comment('模板名称');
            $table->boolean('pay_status')->default(0)->comment('支付状态');
            $table->boolean('apply_status')->default(0)->comment('订单状态');
            $table->decimal('total_amount', 10, 2)->default(0.00)->comment('订单总额');
            $table->decimal('pay_fee', 10, 2)->default(0.00)->comment('支付手续费');
            $table->integer('add_time')->unsigned()->default(0)->comment('申请时间');
            $table->integer('pay_time')->unsigned()->default(0)->comment('支付时间');
            $table->integer('pay_id')->unsigned()->default(0)->index('pay_id')->comment('支付方式ID');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '店铺购买模板'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('seller_template_apply');
    }
}
