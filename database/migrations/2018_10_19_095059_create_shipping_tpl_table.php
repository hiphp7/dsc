<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateShippingTplTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'shipping_tpl';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('st_id')->comment('自增ID');
            $table->integer('shipping_id')->unsigned()->default(0)->index('shipping_id')->comment('配送方式ID');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID（同dsc_users表user_id）');
            $table->string('print_bg')->default('')->comment('快递单背景图片');
            $table->boolean('print_model')->default(0)->comment('模板模式');
            $table->text('config_lable')->nullable()->comment('标签信息');
            $table->text('shipping_print')->nullable()->comment('订单模板变量说明');
            $table->string('update_time')->default('')->comment('更新时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '配送模板'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('shipping_tpl');
    }
}
