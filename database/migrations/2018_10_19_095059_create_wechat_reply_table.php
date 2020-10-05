<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatReplyTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_reply';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->string('type')->default('')->comment('自动回复类型');
            $table->string('content')->default('')->comment('回复内容');
            $table->integer('media_id')->unsigned()->default(0)->index()->comment('素材id');
            $table->string('rule_name')->default('')->comment('规则名称');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->string('reply_type')->default('')->comment('关键词回复内容的类型');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通自动回复'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_reply');
    }
}
