<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatRedpackLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_redpack_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->integer('market_id')->unsigned()->default(0)->index()->comment('关联活动id');
            $table->boolean('hb_type')->default(0)->comment('红包类型： 0 普通红包，1裂变红包');
            $table->string('openid')->default('')->comment('微信用户公众号唯一标示');
            $table->boolean('hassub')->default(0)->comment('是否领取：0未领取，1已领取');
            $table->decimal('money', 10, 2)->default(0.00)->comment('领取金额');
            $table->integer('time')->unsigned()->default(0)->comment('领取时间');
            $table->string('mch_billno')->default('')->comment('商户订单号');
            $table->string('mch_id')->default('')->comment('微信支付商户号');
            $table->string('wxappid')->default('')->comment('公众账号appid');
            $table->string('bill_type')->default('')->comment('订单类型');
            $table->text('notify_data')->comment('交易数据');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通现金红包领取记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_redpack_log');
    }
}
