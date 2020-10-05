<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesaleGoodsAttrTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_goods_attr';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('goods_attr_id')->comment('自增ID');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品id');
            $table->integer('attr_id')->unsigned()->default(0)->index('attr_id')->comment('属性id');
            $table->text('attr_value')->nullable()->comment('属性值');
            $table->text('color_value')->nullable()->comment('颜色值');
            $table->decimal('attr_price', 10, 2)->default(0.00)->comment('属性价格');
            $table->integer('attr_sort')->unsigned()->default(0)->comment('属性排序');
            $table->string('attr_img_flie')->default('')->comment('属性图片');
            $table->string('attr_gallery_flie')->default('')->comment('属性相册图片');
            $table->string('attr_img_site')->default('')->comment('属性图片地址');
            $table->boolean('attr_checked')->default(0)->comment('属性是否选中');
            $table->integer('lang_flag')->default(0)->comment('语言标记');
            $table->string('attr_img')->default('')->comment('属性图片');
            $table->string('attr_thumb')->default('')->comment('属性缩略图');
            $table->integer('img_flag')->default(0)->comment('图片标记');
            $table->string('attr_pid', 60)->default('')->comment('属性父级id');
            $table->integer('admin_id')->unsigned()->default(0)->index('admin_id')->comment('管理员');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '批发商品属性'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_goods_attr');
    }
}
