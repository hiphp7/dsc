<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesaleProductsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_products';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('product_id')->comment('自增ID');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品id');
            $table->string('goods_attr', 50)->nullable()->comment('商品属性');
            $table->string('product_sn', 60)->nullable()->index('product_sn')->comment('商品货品货号');
            $table->integer('product_number')->unsigned()->nullable()->default(0)->comment('商品货品数量');
            $table->integer('admin_id')->unsigned()->default(0)->index('admin_id')->comment('管理员');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '批发商品货品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_products');
    }
}
