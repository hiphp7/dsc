<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCartComboTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'cart_combo';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('rec_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('会员ID');
            $table->string('session_id')->default('')->index('session_id')->comment('登录的sessionid');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品ID');
            $table->string('goods_sn', 60)->default('')->comment('商品货号');
            $table->integer('product_id')->unsigned()->default(0)->index('product_id')->comment('商品货品货号');
            $table->string('group_id')->default('')->index('group_id')->comment('组ID');
            $table->string('goods_name', 120)->default('')->comment('商品名称');
            $table->decimal('market_price', 10)->unsigned()->default(0.00)->comment('商品市场价');
            $table->decimal('goods_price', 10)->default(0.00)->comment('商品售价');
            $table->integer('goods_number')->unsigned()->default(0)->comment('购买数量');
            $table->text('goods_attr')->nullable()->comment('商品属性值');
            $table->string('img_flie')->default('')->comment('属性图片');
            $table->boolean('is_real')->default(0)->index('is_real')->comment('取自dsc_goods的is_real');
            $table->string('extension_code', 30)->default('')->index('extension_code')->comment('商品的扩展属性，取自dsc_goods的extension_code');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('该商品的父商品id，没有该值为0，有的话那该商品就是该id的配件');
            $table->boolean('rec_type')->default(0)->comment('购物车商品类型');
            $table->integer('is_gift')->unsigned()->default(0)->index('is_gift')->comment('是否是赠品，0，否；其他，是参加优惠活动的id，取值于dsc_favourable_activity 的act_id');
            $table->boolean('is_shipping')->default(0)->comment('是否免运费');
            $table->boolean('can_handsel')->default(0)->comment('能否处理 0 否');
            $table->text('goods_attr_id')->nullable()->comment('商品属性的id');
            $table->integer('ru_id')->default(0)->index('ru_id')->comment('商家ID');
            $table->integer('warehouse_id')->unsigned()->default(0)->index('warehouse_id')->comment('仓库ID');
            $table->integer('area_id')->unsigned()->default(0)->index('area_id')->comment('仓库地区ID');
            $table->integer('area_city')->unsigned()->default(0)->index()->comment('市级ID');
            $table->boolean('model_attr')->default(0)->index('model_attr')->comment('商品属性模式（0-默认，1-仓库，2-地区）');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->string('commission_rate', 10)->default('')->comment('商品佣金比例');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '购物车组合购买信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cart_combo');
    }
}
