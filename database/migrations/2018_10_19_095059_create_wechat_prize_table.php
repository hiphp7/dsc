<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatPrizeTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_prize';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->string('openid')->default('')->comment('微信用户openid');
            $table->string('prize_name')->default('')->comment('奖品名称');
            $table->boolean('issue_status')->default(0)->comment('发放状态，0未发放，1发放');
            $table->string('winner')->default('')->comment('信息');
            $table->integer('dateline')->unsigned()->default(0)->comment('中奖时间');
            $table->boolean('prize_type')->default(0)->comment('是否中奖，0未中奖，1中奖');
            $table->string('activity_type')->default('')->comment('活动类型');
            $table->integer('market_id')->unsigned()->default(0)->index()->comment('关联活动ID');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通活动中奖记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_prize');
    }
}
