<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStoreGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'store_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品ID');
            $table->integer('store_id')->unsigned()->default(0)->index('store_id')->comment('门店ID');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID');
            $table->integer('goods_number')->unsigned()->default(1)->comment('商品数量');
            $table->text('extend_goods_number')->nullable()->comment('扩展商品数量');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '门店商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('store_goods');
    }
}
