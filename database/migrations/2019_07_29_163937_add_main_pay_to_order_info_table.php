<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMainPayToOrderInfoTable extends Migration
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

        if (!Schema::hasColumn($this->table, 'main_pay')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->boolean('main_pay')->default(0)->comment('主订单是否支付【0：过滤之前订单 1:未支付 2：已支付】');
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
        if (Schema::hasColumn($this->table, 'main_pay')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('main_pay');
            });
        }
    }
}
