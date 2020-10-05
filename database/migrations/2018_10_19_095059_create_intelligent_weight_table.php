<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateIntelligentWeightTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'intelligent_weight';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品ID');
            $table->integer('goods_number')->unsigned()->default(0)->comment('商品购买数量');
            $table->integer('return_number')->unsigned()->default(0)->comment('商品退换货数量');
            $table->integer('user_number')->unsigned()->default(0)->comment('购买此商品的会员数量');
            $table->integer('goods_comment_number')->unsigned()->default(0)->comment('对商品评价数量');
            $table->integer('merchants_comment_number')->unsigned()->default(0)->comment('对商家评价数量');
            $table->integer('user_attention_number')->unsigned()->default(0)->comment('会员关注此商品数量');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商品智能权重'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('intelligent_weight');
    }
}
