<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCommentTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'comment';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('comment_id')->comment('自增ID');
            $table->boolean('comment_type')->default(0)->index('comment_type')->comment('用户评论的类型；0，评论的是商品；1，评论的是文章');
            $table->integer('id_value')->unsigned()->default(0)->index('id_value')->comment('文章或者商品的id，文章对应的是dsc_article 的article_id；商品对应的是dsc_goods的goods_id');
            $table->string('email', 60)->default('')->comment('评论时提交的email地址');
            $table->string('user_name', 60)->default('')->comment('会员名称');
            $table->text('content')->comment('评论的内容');
            $table->boolean('comment_rank')->default(5)->comment('该文章或者商品的星级；只有1到5星；由数字代替；其中5是代表5星');
            $table->boolean('comment_server')->default(5)->comment('该文章或者商品的星级；只有1到5星；由数字代替；其中5是代表5星');
            $table->boolean('comment_delivery')->default(5)->comment('该文章或者商品的星级；只有1到5星；由数字代替；其中5是代表5星');
            $table->integer('add_time')->unsigned()->default(0)->comment('评论的时间');
            $table->string('ip_address', 15)->default('')->comment('评论时的用户ip');
            $table->boolean('status')->default(0)->index('status')->comment('是否被管理员批准显示，1，是；0，未批准');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('评论的父节点；取值该表的comment_id字段；如果该字段为0，则是一个普通评论，否则该条评论就是该字段的值所对应的评论的回复');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('会员ID');
            $table->integer('ru_id')->unsigned()->index('ru_id')->comment('商家ID');
            $table->integer('single_id')->unsigned()->nullable()->default(0)->index('single_id')->comment('晒单ID，取值dsc_single');
            $table->integer('order_id')->unsigned()->nullable()->default(0)->index('order_id')->comment('订单ID');
            $table->integer('rec_id')->unsigned()->default(0)->index('rec_id')->comment('记录ID');
            $table->string('goods_tag', 500)->nullable()->comment('商品评论标签');
            $table->integer('useful')->nullable()->default(0)->comment('回复人数');
            $table->text('useful_user')->comment('浏览人数');
            $table->string('use_ip', 15)->nullable()->comment('评论的用户IP');
            $table->integer('dis_id')->unsigned()->nullable()->default(0)->comment('讨论圈ID');
            $table->integer('like_num')->default(0)->comment('点赞数');
            $table->integer('dis_browse_num')->unsigned()->default(0)->comment('浏览数');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '用户对文章和商品的评论'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('comment');
    }
}
