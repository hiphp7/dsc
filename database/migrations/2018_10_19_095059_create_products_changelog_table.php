<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateProductsChangelogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'products_changelog';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('product_id')->comment('自增ID号');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品ID号');
            $table->string('goods_attr', 50)->default('')->comment('商品属性ID（字符串拼接）');
            $table->string('product_sn', 60)->default('')->comment('货品编号');
            $table->string('bar_code', 60)->default('')->comment('条形码');
            $table->integer('product_number')->unsigned()->default(0)->comment('货品库存数量');
            $table->decimal('product_price', 10)->unsigned()->default(0.00)->comment('货品价格');
            $table->decimal('product_market_price', 10)->unsigned()->default(0.00)->comment('货品超市价格');
            $table->decimal('product_promote_price', 10)->unsigned()->default(0.00)->comment('促销价格');
            $table->integer('product_warn_number')->unsigned()->default(0)->comment('货品缺货警告');
            $table->integer('warehouse_id')->unsigned()->default(0)->index('warehouse_id')->comment('仓库ID');
            $table->integer('area_id')->unsigned()->default(0)->index('area_id')->comment('仓库地区id');
            $table->integer('city_id')->unsigned()->default(0)->index('city_id')->comment('城市id');
            $table->integer('admin_id')->unsigned()->default(0)->index('admin_id')->comment('管理员');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '商品货品临时记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('products_changelog');
    }
}
