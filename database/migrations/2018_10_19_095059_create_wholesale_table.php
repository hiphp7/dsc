<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesaleTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('act_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->comment('会员id');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品id');
            $table->integer('wholesale_cat_id')->unsigned()->default(0)->comment('批发分类id');
            $table->string('goods_name')->default('')->comment('商品名称');
            $table->string('rank_ids')->default('')->comment('等级id组');
            $table->decimal('goods_price', 10, 2)->unsigned()->default(0.00)->comment('商品价格');
            $table->boolean('enabled')->default(0)->comment('是否禁用');
            $table->boolean('review_status')->default(1)->index('review_status')->comment('审核状态');
            $table->string('review_content', 1000)->default('')->comment('审核内容');
            $table->boolean('price_model')->default(0)->comment('价格模式');
            $table->integer('goods_type')->unsigned()->default(0)->comment('商品类型');
            $table->integer('goods_number')->unsigned()->default(0)->comment('商品数量');
            $table->integer('moq')->unsigned()->default(0)->comment('最小起批量');
            $table->boolean('is_recommend')->default(0)->comment('是否推荐：0 否 1 是');
            $table->boolean('is_promote')->default(0)->comment('是否促销');
            $table->integer('start_time')->unsigned()->default(0)->comment('开始时间');
            $table->integer('end_time')->unsigned()->default(0)->comment('结束时间');
            $table->decimal('shipping_fee', 10, 2)->unsigned()->default(0.00)->comment('商品固定运费');
            $table->boolean('freight')->default(0)->comment('运费类型');
            $table->integer('tid')->unsigned()->default(0)->comment('商品运费模板');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '批发商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale');
    }
}
