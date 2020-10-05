<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOrderGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'order_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('rec_id')->comment('自增ID号');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('会员ID');
            $table->text('cart_recid')->comment('购物车id');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品ID');
            $table->string('goods_name', 120)->default('')->comment('商品名称');
            $table->string('goods_sn', 60)->default('')->comment('商品货号');
            $table->integer('product_id')->unsigned()->default(0)->index('product_id')->comment('商品货品ID');
            $table->integer('goods_number')->unsigned()->default(1)->comment('购买商品数量');
            $table->decimal('market_price', 10, 2)->default(0.00)->comment('商品市场价');
            $table->decimal('goods_price', 10, 2)->default(0.00)->comment('购买商品价格');
            $table->text('goods_attr')->comment('商品属性值');
            $table->integer('send_number')->unsigned()->default(0)->comment('当不是实物时，是否已发货，0，否；1，是');
            $table->boolean('is_real')->default(0)->index('is_real')->comment('是否是实物，0，否；1，是；取值dsc_goods');
            $table->string('extension_code', 30)->default('')->comment('商品的扩展属性，比如像虚拟卡。取值dsc_goods');
            $table->integer('parent_id')->unsigned()->default(0)->comment('父商品id，取值于ecs_cart的parent_id；如果有该值则是值多代表的物品的配件');
            $table->integer('is_gift')->unsigned()->default(0)->index('is_gift')->comment('是否参加优惠活动，0，否；其他，取值于ecs_cart 的is_gift，跟其一样，是参加的优惠活动的id');
            $table->boolean('model_attr')->default(0)->comment('商品属性模式');
            $table->text('goods_attr_id')->comment('商品属性ID');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID（同dsc_users表user_id）');
            $table->decimal('shopping_fee', 10, 2)->default(0.00)->comment('商品运费（此字段暂时无用）');
            $table->integer('warehouse_id')->unsigned()->default(0)->index('warehouse_id')->comment('仓库ID');
            $table->integer('area_id')->unsigned()->default(0)->index('area_id')->comment('仓库地区ID');
            $table->integer('area_city')->unsigned()->default(0)->index('area_city')->comment('城市ID');
            $table->boolean('is_single')->nullable()->default(0)->comment('晒单ID（此字段暂时无用）');
            $table->boolean('freight')->default(0)->index('freight')->comment('运费模式');
            $table->integer('tid')->unsigned()->default(0)->index('tid')->comment('运费模板id');
            $table->decimal('shipping_fee', 10, 2)->unsigned()->default(0.00)->comment('运费');
            $table->decimal('drp_money', 10, 2)->default(0.00)->comment('分销金额');
            $table->boolean('is_distribution')->default(0)->comment('订单商品是否参与分销');
            $table->string('commission_rate', 10)->default('0')->comment('商品佣金比例');
            $table->integer('stages_qishu')->default(-1)->index('stages_qishu')->comment('用户选择的当前商品的分期期数 -1:不分期');
            $table->string('product_sn')->default('')->comment('商品规格货号');
            $table->boolean('is_reality')->default(-1)->comment('正品保证');
            $table->boolean('is_return')->default(-1)->comment('包退服务');
            $table->boolean('is_fast')->default(-1)->comment('闪速配送');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '订单商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('order_goods');
    }
}
