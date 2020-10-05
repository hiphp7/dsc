<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesaleExtendTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_extend';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('extend_id')->comment('自增ID');
            $table->integer('goods_id')->default(0)->comment('商品id');
            $table->boolean('is_delivery')->default(0)->comment('是否48小时发货，0否 1是');
            $table->boolean('is_return')->default(0)->comment('是否支持包退服务0否1是');
            $table->boolean('is_free')->default(0)->comment('是否闪速送货0否1是');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '批发限时发货标识'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_extend');
    }
}
