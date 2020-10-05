<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDiscussCircleTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'discuss_circle';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('dis_id')->comment('自增ID');
            $table->integer('dis_browse_num')->unsigned()->comment('浏览数');
            $table->integer('like_num')->default(0)->comment('点赞数');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('父级ID');
            $table->integer('quote_id')->unsigned()->default(0)->comment('回复贴dis_id');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('用户ID');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单ID');
            $table->boolean('dis_type')->default(0)->index('dis_type')->comment('回复 0 讨论帖 1  问答帖 2 圈子帖 3');
            $table->string('dis_title', 200)->default('')->comment('标题');
            $table->text('dis_text')->nullable()->comment('内容');
            $table->integer('add_time')->default(0)->comment('添加时间');
            $table->string('user_name', 60)->default('')->comment('用户名');
            $table->boolean('review_status')->default(1)->index('review_status')->comment('审核状态');
            $table->string('review_content')->default('')->comment('审核不通过内容');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '网友讨论圈'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('discuss_circle');
    }
}
