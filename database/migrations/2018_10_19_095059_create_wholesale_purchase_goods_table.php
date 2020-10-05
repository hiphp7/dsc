<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesalePurchaseGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_purchase_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('goods_id')->comment('自增ID');
            $table->integer('purchase_id')->unsigned()->default(0)->index('purchase_id')->comment('求购ID');
            $table->integer('cat_id')->unsigned()->default(0)->comment('分类id');
            $table->string('goods_name')->default('')->comment('商品名称');
            $table->integer('goods_number')->unsigned()->default(0)->comment('商品数量');
            $table->decimal('goods_price', 10)->default(0.00)->comment('商品价格');
            $table->text('goods_img')->nullable()->comment('商品图片');
            $table->string('remarks')->default('')->comment('备注');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '批发求购商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_purchase_goods');
    }
}
