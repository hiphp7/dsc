<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateQrpayTagTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'qrpay_tag';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->integer('ru_id')->unsigned()->default(0)->comment('商家ID');
            $table->string('tag_name')->default('')->comment('标签名称');
            $table->integer('self_qrpay_num')->unsigned()->default(0)->comment('相关自助收款码数量');
            $table->integer('fixed_qrpay_num')->unsigned()->default(0)->comment('相关指定金额收款码数量');
            $table->integer('add_time')->unsigned()->default(0)->comment('创建时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '扫码支付标签列表'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('qrpay_tag');
    }
}
