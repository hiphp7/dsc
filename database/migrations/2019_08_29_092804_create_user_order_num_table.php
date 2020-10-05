<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserOrderNumTable extends Migration
{
    private $table = 'user_order_num';

    /**
     * Run the migrations.
     *
     * @return bool
     */
    public function up()
    {
        if (Schema::hasTable($this->table)) {
            return false;
        }
        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('user_id')->default(0)->unsigned()->index('user_id')->comment('会员ID');
            $table->string('user_name', 120)->default('')->comment('会员名称');
            $table->integer('order_all_num')->unsigned()->default(0)->comment('订单数量');
            $table->integer('order_nopay')->unsigned()->default(0)->comment('待付款订单数量');
            $table->integer('order_nogoods')->unsigned()->default(0)->comment('待收货订单数量');
            $table->integer('order_isfinished')->unsigned()->default(0)->comment('已完成订单数量');
            $table->integer('order_isdelete')->unsigned()->default(0)->comment('回收站订单数量');
            $table->integer('order_team_num')->unsigned()->default(0)->comment('拼团订单数量');
            $table->integer('order_not_comment')->unsigned()->default(0)->comment('待评价订单数量');
            $table->integer('order_return_count')->unsigned()->default(0)->comment('待同意状态退换货申请数量');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->table);
    }
}
