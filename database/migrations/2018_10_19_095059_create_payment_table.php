<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePaymentTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'payment';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('pay_id')->comment('支付方式ID');
            $table->string('pay_code', 20)->default('')->unique('pay_code')->comment('支付方式代码名称（文件名称）');
            $table->string('pay_name', 120)->default('')->comment('支持方式名称');
            $table->string('pay_fee', 10)->default('0')->comment('支付费用');
            $table->text('pay_desc')->comment('支付方式描述');
            $table->boolean('pay_order')->default(0)->comment('支付方式在页面的显示顺序');
            $table->text('pay_config')->comment('支付方式的配置信息，包括商户号和密钥什么的');
            $table->boolean('enabled')->default(0)->comment('是否可用，0，否；1，是');
            $table->boolean('is_cod')->default(0)->comment('是否货到付款，0，否；1，是');
            $table->boolean('is_online')->default(0)->index('is_online')->comment('是否在线支付，0，否；1，是');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '支付方式'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('payment');
    }
}
