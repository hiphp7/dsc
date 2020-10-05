<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateKdniaoEorderConfigTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'kdniao_eorder_config';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('ru_id')->unsigned()->default(0)->index()->comment('商家id');
            $table->integer('shipping_id')->unsigned()->default(0)->index()->comment('配送方式id');
            $table->string('shipper_code')->default('')->comment('配送方式code');
            $table->string('customer_name')->default('')->comment('名称');
            $table->string('customer_pwd')->default('')->comment('密码');
            $table->string('month_code')->default('')->comment('月结编码');
            $table->string('send_site')->default('')->comment('网点标识');
            $table->boolean('pay_type')->default(1)->comment('付款方式');
            $table->string('template_size')->default('')->comment('模板尺寸');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '快递鸟账号配置'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('kdniao_eorder_config');
    }
}
