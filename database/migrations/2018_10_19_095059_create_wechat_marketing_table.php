<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatMarketingTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_marketing';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->string('marketing_type')->default('')->comment('活动类型');
            $table->string('name')->default('')->comment('活动名称');
            $table->string('keywords')->default('')->comment('扩展词');
            $table->string('command')->default('')->comment('关键词');
            $table->string('description')->default('')->comment('活动说明');
            $table->integer('starttime')->unsigned()->default(0)->comment('开始时间');
            $table->integer('endtime')->unsigned()->default(0)->comment('结束时间');
            $table->integer('addtime')->unsigned()->default(0)->comment('添加时间');
            $table->string('logo')->default('')->comment('logo图');
            $table->string('background')->default('')->comment('活动背景图');
            $table->text('config')->nullable()->comment('配置信息');
            $table->string('support')->default('')->comment('赞助支持');
            $table->boolean('status')->default(0)->comment('活动状态: 0未开始,1进行中,2已结束');
            $table->string('qrcode')->default('')->comment('二维码地址');
            $table->string('url')->default('')->comment('活动地址');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通营销活动'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_marketing');
    }
}
