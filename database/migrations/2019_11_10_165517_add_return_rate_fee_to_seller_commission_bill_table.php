<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReturnRateFeeToSellerCommissionBillTable extends Migration
{
    private $table = 'seller_commission_bill';

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

        if (!Schema::hasColumn($this->table, 'return_rate_fee')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->decimal('return_rate_fee', 10, 2)->default(0)->comment('跨境税费退款金额');
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
        if (Schema::hasColumn($this->table, 'return_rate_fee')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('return_rate_fee');
            });
        }
    }
}
