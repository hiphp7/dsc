<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUserFeedTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'user_feed';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('feed_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('会员id');
            $table->integer('value_id')->unsigned()->default(0)->comment('未知');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品id');
            $table->boolean('feed_type')->default(0)->comment('订阅类型：0 购买商品，1 添加商品评论');
            $table->boolean('is_feed')->default(0)->comment('判断是否向UC发送');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '此表已废弃'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_feed');
    }
}
