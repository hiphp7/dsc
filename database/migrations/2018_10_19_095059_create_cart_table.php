<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCartTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'cart';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('rec_id')->comment('自增id');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('会员id');
            $table->string('session_id')->nullable()->index('session_id')->comment('登录的sessionid');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品的id');
            $table->string('goods_sn', 60)->default('')->comment('商品的货号');
            $table->string('product_id')->default('')->index('product_id')->comment('商品属性货品id');
            $table->string('group_id')->default('')->comment('组合购买分组ID');
            $table->string('goods_name', 120)->default('')->comment('商品的名称');
            $table->decimal('market_price', 10)->unsigned()->default(0.00)->comment('商品的市场价');
            $table->decimal('goods_price', 10)->default(0.00)->comment('商品的本店价');
            $table->integer('goods_number')->unsigned()->default(0)->comment('购买商品数量');
            $table->text('goods_attr')->nullable()->comment('商品属性值');
            $table->boolean('is_real')->default(0)->index('is_real')->comment('取自dsc_goods的is_real');
            $table->string('extension_code', 30)->default('')->comment('商品的扩展属性，取自dsc_goods的extension_code');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('该商品的父商品id，没有该值为0，有的话那该商品就是该id的配件');
            $table->boolean('rec_type')->default(0)->index('rec_type')->comment('购物车商品类型');
            $table->integer('is_gift')->unsigned()->default(0)->index('is_gift')->comment('是否是赠品 0否，1是');
            $table->boolean('is_shipping')->default(0)->index('is_shipping')->comment('是否免运费');
            $table->boolean('can_handsel')->default(0)->comment('能否处理：0 否， 1 是');
            $table->boolean('model_attr')->default(0)->comment('商品属性模式（0-默认，1-仓库，2-地区）');
            $table->text('goods_attr_id')->nullable()->comment('该商品的属性的id');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID');
            $table->decimal('shopping_fee', 10)->default(0.00)->comment('商品运费');
            $table->integer('warehouse_id')->unsigned()->default(0)->index('warehouse_id')->comment('仓库ID');
            $table->integer('area_id')->unsigned()->default(0)->index('area_id')->comment('仓库地区ID');
            $table->integer('area_city')->unsigned()->default(0)->index()->comment('市级ID');
            $table->integer('add_time')->default(0)->comment('添加时间');
            $table->string('stages_qishu', 4)->default('-1')->comment('用户选择的当前商品的分期期数 -1:不分期');
            $table->integer('store_id')->unsigned()->default(0)->index('store_id')->comment('门店ID');
            $table->boolean('freight')->default(0)->index('freight')->comment('是否需要寄送');
            $table->integer('tid')->unsigned()->default(0)->index('tid')->comment('运费模板id');
            $table->decimal('shipping_fee', 10)->unsigned()->default(0.00)->comment('运费');
            $table->string('store_mobile', 20)->default('')->comment('门店');
            $table->dateTime('take_time')->default('1000-01-01 00:00:00')->comment('提货时间');
            $table->boolean('is_checked')->default(1)->index('is_checked')->comment('选中状态，0未选中，1选中');
            $table->string('commission_rate', 10)->default('0')->comment('商品佣金比例');
            $table->boolean('is_invalid')->default(0)->comment('购物车商品是否无效 0有效，1无效');
            $table->integer('act_id')->unsigned()->default(0)->index()->comment('优惠活动ID');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '购物车'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cart');
    }
}
