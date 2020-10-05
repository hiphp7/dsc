<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBargainGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'bargain_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('bargain_name')->default('')->comment('砍价活动标题');
            $table->integer('goods_id')->unsigned()->default(0)->comment('砍价商品id');
            $table->integer('start_time')->unsigned()->default(0)->comment('活动开始时间');
            $table->integer('end_time')->unsigned()->default(0)->comment('活动结束时间');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->decimal('goods_price', 10)->default(0.00)->comment('活动原价');
            $table->integer('min_price')->unsigned()->default(0)->comment('价格区间（最小值）');
            $table->integer('max_price')->unsigned()->default(0)->comment('价格区间（最大值）');
            $table->decimal('target_price', 10)->default(0.00)->comment('砍价目标价格');
            $table->integer('total_num')->unsigned()->default(0)->comment('参与人数');
            $table->boolean('is_hot')->default(0)->comment('是否热销');
            $table->boolean('is_audit')->default(0)->comment('0未审核，1未通过，2已审核');
            $table->string('isnot_aduit_reason')->default('')->comment('审核未通过原因');
            $table->string('bargain_desc')->default('')->comment('砍价介绍');
            $table->boolean('status')->default(0)->comment('活动状态（0进行中、1关闭）');
            $table->boolean('is_delete')->default(0)->comment('活动删除状态（1删除）');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '活动砍价商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('bargain_goods');
    }
}
