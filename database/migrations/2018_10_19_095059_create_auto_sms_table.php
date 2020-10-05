<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAutoSmsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'auto_sms';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('item_id')->comment('模板ID');
            $table->boolean('item_type')->default(0)->comment('模板类型');
            $table->integer('user_id')->unsigned()->default(0)->comment('用户ID');
            $table->integer('ru_id')->unsigned()->default(0)->comment('商家ID');
            $table->integer('order_id')->unsigned()->default(0)->comment('订单ID');
            $table->string('add_time')->default('')->comment('新增短信模板时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '自动发短信、邮件（计划任务）'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('auto_sms');
    }
}
