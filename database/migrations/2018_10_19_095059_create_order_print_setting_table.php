<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOrderPrintSettingTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'order_print_setting';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('会员ID');
            $table->string('specification', 50)->default('')->comment('规格');
            $table->string('printer', 50)->default('')->comment('名称');
            $table->integer('width')->unsigned()->default(0)->comment('宽度');
            $table->boolean('is_default')->default(0)->comment('是否默认');
            $table->boolean('sort_order')->default(0)->comment('排序');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '快递鸟打印设置'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('order_print_setting');
    }
}
