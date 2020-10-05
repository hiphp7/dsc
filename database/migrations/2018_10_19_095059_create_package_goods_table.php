<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePackageGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'package_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增id');
            $table->integer('package_id')->index('package_id')->comment('活动id');
            $table->integer('goods_id')->index('goods_id')->unsigned()->default(0)->comment('商品ID');
            $table->integer('product_id')->index('product_id')->unsigned()->default(0)->comment('商品货品ID');
            $table->integer('goods_number')->unsigned()->default(1)->comment('数量');
            $table->boolean('admin_id')->index('admin_id')->default(0)->comment('操作管理员');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '超值礼包商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('package_goods');
    }
}
