<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateShippingDateTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'shipping_date';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('shipping_date_id')->comment('自增ID');
            $table->string('start_date', 10)->default('')->comment('开始时间');
            $table->string('end_date', 10)->default('')->comment('结束时间');
            $table->integer('select_day')->unsigned()->default(0)->comment('可选开始日期');
            $table->string('select_date', 11)->default('')->comment('请选择日期');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '自提点配送时间'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('shipping_date');
    }
}
