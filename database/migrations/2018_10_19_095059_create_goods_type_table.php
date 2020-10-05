<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGoodsTypeTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'goods_type';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('cat_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->index('user_id')->comment('商家ID（同dsc_users表user_id）');
            $table->integer('suppliers_id')->unsigned()->default(0)->index()->comment('供应商ID');
            $table->string('cat_name', 60)->default('')->index('cat_name')->comment('类型名称');
            $table->boolean('enabled')->default(1)->index('enabled')->comment('类型状态，1，为可用；0为不可用；不可用的类型，在添加商品的时候选择商品属性将不可选');
            $table->string('attr_group')->default('')->comment('商品属性分组，将一个商品类型的属性分成组，在显示的时候也是按组显示。该字段的值显示在属性的前一行，像标题的作用');
            $table->integer('c_id')->unsigned()->default(0)->index('c_id')->comment('属性分类id');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '商品属性类型'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_type');
    }
}
