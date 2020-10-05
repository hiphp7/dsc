<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateShippingTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'shipping';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('shipping_id')->comment('自增ID');
            $table->string('shipping_code', 20)->default('')->comment('配送方式的字符串代号');
            $table->string('shipping_name', 120)->default('')->comment('配送方式的名称');
            $table->string('shipping_desc')->default('')->comment('配送方式的描述');
            $table->string('insure', 10)->default('')->comment('保价费用，单位元，或者是百分数，该值直接输出为报价费用');
            $table->boolean('support_cod')->default(0)->comment('是否支持货到付款，1，支持；0，不支持');
            $table->boolean('enabled')->default(0)->comment('该配送方式是否被禁用，1，可用；0，禁用');
            $table->text('shipping_print')->nullable()->comment('快递打印模板');
            $table->string('print_bg')->default('')->comment('图片文件');
            $table->text('config_lable')->nullable()->comment('自提点地址');
            $table->boolean('print_model')->default(0)->comment('打印单模式');
            $table->boolean('shipping_order')->default(0)->comment('排序');
            $table->string('customer_name', 50)->default('')->comment('快递鸟账号');
            $table->string('customer_pwd', 50)->default('')->comment('快递鸟密码');
            $table->string('month_code', 50)->default('')->comment('月结编号');
            $table->string('send_site', 50)->default('')->comment('收件网点标识');
            $table->index(['shipping_code', 'enabled'], 'shipping_code');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '配送方式'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('shipping');
    }
}
