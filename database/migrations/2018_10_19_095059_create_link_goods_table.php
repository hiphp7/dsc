<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateLinkGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'link_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->integer('goods_id')->unsigned()->default(0)->comment('商品id');
            $table->integer('link_goods_id')->unsigned()->default(0)->comment('关联商品id');
            $table->boolean('is_double')->default(0)->comment('关联类型：0 双向关联， 1 单向关联');
            $table->boolean('admin_id')->default(0)->comment('管理员ID');
            $table->primary(['goods_id','link_goods_id','admin_id']);
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '关联商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('link_goods');
    }
}
