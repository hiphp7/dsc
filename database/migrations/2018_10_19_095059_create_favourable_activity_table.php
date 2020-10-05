<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateFavourableActivityTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'favourable_activity';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('act_id')->comment('自增ID');
            $table->string('act_name')->default('')->index('act_name')->comment('活动名称');
            $table->integer('start_time')->unsigned()->index('start_time')->comment('活动的开始时间');
            $table->integer('end_time')->unsigned()->index('end_time')->comment('活动结束时间');
            $table->string('user_rank')->default('')->comment('可以参加活动的用户信息，取值于ecs_user_rank的rank_id；其中0是非会员，其他是相应的会员等级；多个值用逗号分隔');
            $table->boolean('act_range')->default(0)->comment('优惠范围；0，全部商品；1，按分类；2，按品牌；3，按商品');
            $table->string('act_range_ext')->default('')->comment('根据优惠活动范围的不同，该处意义不同；但是都是优惠范围的约束；如，如果是商品，该处是商品的id，如果是品牌，该处是品牌的id');
            $table->decimal('min_amount', 10, 2)->unsigned()->comment('订单达到金额下限，才参加活动');
            $table->decimal('max_amount', 10, 2)->unsigned()->comment('参加活动的订单金额上限，0，表示没有上限');
            $table->boolean('act_type')->default(0)->index('act_type')->comment('参加活动的优惠方式；0，送赠品或优惠购买；1，现金减免；价格打折优惠');
            $table->decimal('act_type_ext', 10, 2)->unsigned()->default(0.00)->comment('如果是送赠品，该处是允许的最大数量，0，无数量限制；现今减免，则是减免金额，单位元；打折，是折扣值，100算，8折就是80');
            $table->string('activity_thumb')->default('')->comment('图片');
            $table->text('gift')->nullable()->comment('如果有特惠商品，这里是序列化后的特惠商品的id,name,price信息;取值于ecs_goods的goods_id，goods_name，价格是添加活动时填写的');
            $table->boolean('sort_order')->default(50)->comment('排序');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('商家ID（同dsc_users表user_id）');
            $table->integer('rs_id')->default(0)->index('rs_id')->comment('卖场ID');
            $table->boolean('userFav_type')->default(0)->index('userFav_type')->comment('活动类型（0-自主使用，1-全场通用）');
            $table->string('userFav_type_ext')->default('')->comment('使用类型扩展');
            $table->boolean('review_status')->default(1)->index('review_status')->comment('审核状态');
            $table->string('review_content')->default('')->comment('审核回复内容');
            $table->string('user_range_ext')->default('')->comment('已废弃');
            $table->boolean('is_user_brand')->default(0)->comment('使用类型拓展');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '优惠活动'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('favourable_activity');
    }
}
