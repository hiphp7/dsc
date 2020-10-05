<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateQrpayManageTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'qrpay_manage';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->string('qrpay_name')->default('')->comment('收款码名称');
            $table->boolean('type')->default(0)->comment('收款码类型(0自助、1 指定)');
            $table->decimal('amount', 10, 2)->default(0.00)->comment('收款码金额');
            $table->integer('discount_id')->unsigned()->default(0)->comment('关联优惠类型id');
            $table->integer('tag_id')->unsigned()->default(0)->comment('关联标签id');
            $table->integer('qrpay_status')->unsigned()->default(0)->comment('收款状况');
            $table->integer('ru_id')->unsigned()->default(0)->comment('商家ID');
            $table->string('qrpay_code')->default('')->comment('二维码链接');
            $table->integer('add_time')->unsigned()->default(0)->comment('创建时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '扫码支付管理'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('qrpay_manage');
    }
}
