<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDrpAffiliateLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'drp_affiliate_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('log_id')->comment('自增ID');
            $table->integer('order_id')->unsigned()->default(0)->index()->comment('订单ID');
            $table->integer('time')->unsigned()->default(0)->comment('分成时间');
            $table->integer('user_id')->unsigned()->default(0)->index()->comment('会员ID');
            $table->string('user_name')->default('')->comment('会员名称');
            $table->decimal('money', 10, 2)->default(0.00)->comment('分成获得佣金');
            $table->integer('point')->unsigned()->default(0)->comment('分成获得积分');
            $table->boolean('separate_type')->default(0)->comment('记录状态');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '分销分成记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('drp_affiliate_log');
    }
}
