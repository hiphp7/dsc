<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGoodsLibTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'goods_lib';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('goods_id')->comment('自增ID');
            $table->integer('cat_id')->unsigned()->default(0)->index('cat_id')->comment('分类ID');
            $table->smallInteger('lib_cat_id')->comment('商品库分类ID');
            $table->string('goods_sn', 60)->default('')->index('goods_sn')->comment('商品货号');
            $table->string('bar_code', 60)->comment('条形码');
            $table->string('goods_name', 120)->default('')->comment('商品名称');
            $table->string('goods_name_style', 60)->default('+')->comment('商品名称样式');
            $table->integer('brand_id')->unsigned()->default(0)->index('brand_id')->comment('品牌id');
            $table->decimal('goods_weight', 10, 3)->unsigned()->default(0.000)->index('goods_weight')->comment('商品重量');
            $table->decimal('market_price', 10)->unsigned()->default(0.00)->comment('市场价格');
            $table->decimal('cost_price', 10)->default(0.00)->comment('成本价');
            $table->decimal('shop_price', 10)->unsigned()->default(0.00)->comment('销售价格');
            $table->string('keywords')->default('')->comment('商品关键字');
            $table->string('goods_brief')->default('')->comment('商品的简短描述');
            $table->text('goods_desc')->comment('商品的详细描述');
            $table->text('desc_mobile')->comment('商品手机描述');
            $table->string('goods_thumb')->default('')->comment('商品在前台显示的微缩图片，如在分类筛选时显示的小图片');
            $table->string('goods_img')->default('')->comment('商品的实际大小图片，如进入该商品页时介绍商品属性所显示的大图片');
            $table->string('original_img')->default('')->comment('上传的商品的原始图片');
            $table->boolean('is_real')->default(1)->comment('是否是实物，1，是；0，否；比如虚拟卡就为0，不是实物');
            $table->string('extension_code', 30)->default('')->comment('商品的扩展属性，比如像虚拟卡');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('sort_order')->unsigned()->default(100)->index('sort_order')->comment('商品排序');
            $table->integer('last_update')->unsigned()->default(0)->index('last_update')->comment('最后更新时间');
            $table->integer('goods_type')->unsigned()->default(0)->comment('商品所属类型id，取值表dsc_goods_type的cat_id');
            $table->boolean('is_check')->nullable()->comment('废弃字段');
            $table->decimal('largest_amount', 10)->unsigned()->default(0.00)->comment('废弃字段');
            $table->text('pinyin_keyword')->nullable()->comment('商品名称拼音');
            $table->integer('lib_goods_id')->unsigned()->comment('商品库商品ID');
            $table->boolean('is_on_sale')->comment('上下架');
            $table->integer('from_seller')->default(0)->comment('商家导入的商品标注来源');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '商品库'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_lib');
    }
}
