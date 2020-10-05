<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGoodsTransportTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'goods_transport';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('tid')->comment('自增ID');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID（同dsc_users表user_id）');
            $table->boolean('type')->default(0)->comment('类型名称');
            $table->boolean('freight_type')->default(0)->comment('类型');
            $table->string('title', 50)->default('')->comment('标题');
            $table->string('shipping_title')->comment('运费标题');
            $table->decimal('free_money', 10)->unsigned()->default(0.00)->comment('免费额度');
            $table->integer('update_time')->unsigned()->default(0)->comment('更新时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '商品运费'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_transport');
    }
}
