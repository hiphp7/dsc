<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDrpTypeTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'drp_type';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->comment('会员ID');
            $table->integer('cat_id')->unsigned()->default(0)->index()->comment('分类ID');
            $table->integer('goods_id')->unsigned()->default(0)->comment('商品ID');
            $table->boolean('type')->default(0)->comment('分销商品类型：0全部，1分类，2商品');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '分销商分销商品类型'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('drp_type');
    }
}
