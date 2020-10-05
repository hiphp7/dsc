<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGoodsTypeCatTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'goods_type_cat';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('cat_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('商家ID（同dsc_users表user_id）');
            $table->integer('suppliers_id')->unsigned()->default(0)->index()->comment('供应商ID');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('父级id');
            $table->string('cat_name', 90)->index('cat_name')->comment('类型名称');
            $table->integer('sort_order')->unsigned()->default(50)->comment('排序');
            $table->boolean('level')->default(1)->comment('层级');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '商品属性分类'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_type_cat');
    }
}
