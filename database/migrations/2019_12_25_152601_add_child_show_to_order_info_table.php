<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddChildShowToOrderInfoTable extends Migration
{
    private $table = 'order_info';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->table)) {
            return false;
        }

        if (!Schema::hasColumn($this->table, 'child_show')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->tinyInteger('child_show')->default(0)->comment('当有主订单并未付款的状态情况下，操作其中的一个子订单为[分单、付款、发货]时，会员中心列表则显示');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn($this->table, 'child_show')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('child_show');
            });
        }
    }
}
