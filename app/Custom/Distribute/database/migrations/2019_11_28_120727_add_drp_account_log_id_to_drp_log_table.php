<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDrpAccountLogIdToDrpLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = 'drp_log';

        if (!Schema::hasTable($tableName)) {
            return false;
        }

        if (!Schema::hasColumn($tableName, 'drp_account_log_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('drp_account_log_id')->unsigned()->default(0)->comment('支付订单id,关联drp_account_log表');
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
        $tableName = 'drp_log';
        // 删除字段
        if (Schema::hasColumn($tableName, 'drp_account_log_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('drp_account_log_id');
            });
        }
    }
}
