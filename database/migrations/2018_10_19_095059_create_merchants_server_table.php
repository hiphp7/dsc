<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMerchantsServerTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'merchants_server';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('server_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('商家ID（同dsc_users表user_id）');
            $table->text('suppliers_desc')->nullable()->comment('描述');
            $table->integer('suppliers_percent')->unsigned()->default(0)->index('suppliers_percent')->comment('同ecs_merchants_percent的percent_id');
            $table->boolean('commission_model')->default(0)->comment('佣金模式');
            $table->integer('bill_freeze_day')->unsigned()->default(0)->comment('账单冻结时间');
            $table->boolean('cycle')->default(2)->index('cycle')->comment('结算周期');
            $table->integer('day_number')->unsigned()->default(0)->comment('天数');
            $table->integer('bill_time')->unsigned()->default(0)->index('bill_time')->comment('开始生产账单时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商家佣金设置'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants_server');
    }
}
