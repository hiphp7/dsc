<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBaitiaoPayLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'baitiao_pay_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->integer('baitiao_id')->unsigned()->default(0)->index('baitiao_id')->comment('白条id');
            $table->integer('log_id')->unsigned()->default(0)->index('log_id')->comment('支付日志id');
            $table->integer('stages_num')->unsigned()->default(0)->index('stages_num')->comment('分期数');
            $table->decimal('stages_price', 10, 2)->unsigned()->default(0.00)->comment('分期总额');
            $table->boolean('is_pay')->default(0)->index('is_pay')->comment('是否支付');
            $table->integer('pay_id')->unsigned()->default(0)->index('pai_id')->comment('支付id');
            $table->string('pay_code', 20)->comment('支付方式代码名称（文件名称）');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('pay_time')->unsigned()->default(0)->comment('支付时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '白条支付日志'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('baitiao_pay_log');
    }
}
