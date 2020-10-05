<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAttributeImgTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'attribute_img';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->integer('attr_id')->unsigned()->default(0)->index('attr_id')->comment('商品属性ID，同goods_attr的goods_attr_id');
            $table->string('attr_values')->default('')->comment('属性值名称');
            $table->string('attr_img')->default('')->comment('图片路径地址');
            $table->string('attr_site')->default('')->comment('链接跳转地址');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '商品属性图片'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('attribute_img');
    }
}
