<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGainAmountToOrderSettlementLogTable extends Migration
{
    private $table = 'order_settlement_log';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->table, 'gain_amount')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->decimal('gain_amount', 10, 2)->default(0)->after('is_settlement')->comment('实际收取金额');
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
        // 删除字段
        if (Schema::hasColumn($this->table, 'gain_amount')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('gain_amount');
            });
        }
    }
}
