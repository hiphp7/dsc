<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesaleCartTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_cart';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('rec_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->comment('会员id');
            $table->string('session_id', 60)->default('')->index('session_id')->comment('记录用户session_id');
            $table->integer('goods_id')->unsigned()->default(0)->comment('商品id');
            $table->string('goods_sn', 60)->default('')->comment('货号');
            $table->string('product_id')->default('')->comment('商品货号id');
            $table->string('goods_name', 120)->default('')->comment('商品名称');
            $table->decimal('market_price', 10)->unsigned()->default(0.00)->comment('市场价格');
            $table->decimal('goods_price', 10)->default(0.00)->comment('商品价格');
            $table->integer('goods_number')->unsigned()->default(0)->comment('商品数量');
            $table->text('goods_attr')->nullable()->comment('商品属性');
            $table->boolean('is_real')->default(0)->comment('是否实物商品');
            $table->string('extension_code', 30)->default('')->comment('活动标识');
            $table->boolean('rec_type')->default(0)->comment('购物类型');
            $table->boolean('is_shipping')->default(0)->comment('是否免运费');
            $table->string('goods_attr_id')->default('')->comment('商品属性id');
            $table->integer('ru_id')->unsigned()->default(0)->comment('商品id');
            $table->integer('add_time')->default(0)->comment('添加时间');
            $table->boolean('freight')->default(0)->comment('商品运费类型');
            $table->integer('tid')->unsigned()->default(0)->comment('商品运费模板ID');
            $table->decimal('shipping_fee', 10)->unsigned()->default(0.00)->comment('商品固定运费');
            $table->boolean('is_checked')->default(1)->comment('选中状态，0未选中，1选中');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '批发购物车'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_cart');
    }
}
