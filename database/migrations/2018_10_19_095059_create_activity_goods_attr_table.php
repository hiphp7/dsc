<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateActivityGoodsAttrTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'activity_goods_attr';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增id');
            $table->integer('bargain_id')->unsigned()->default(0)->comment('砍价活动id');
            $table->integer('goods_id')->unsigned()->default(0)->comment('商品id');
            $table->integer('product_id')->unsigned()->default(0)->comment('属性id');
            $table->decimal('target_price', 10, 2)->default(0.00)->comment('砍价目标价格');
            $table->string('type')->default('')->comment('活动类型');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '活动商品属性(砍价模块)'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('activity_goods_attr');
    }
}
