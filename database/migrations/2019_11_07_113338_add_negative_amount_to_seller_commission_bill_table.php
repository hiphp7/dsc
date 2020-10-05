<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNegativeAmountToSellerCommissionBillTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seller_commission_bill';
        if (!Schema::hasTable($name)) {
            return false;
        }

        if (!Schema::hasColumn($name, 'negative_amount')) {
            Schema::table($name, function (Blueprint $table) {
                $table->decimal('negative_amount')->unsigned()->default('0.00')->comment('负账单金额');
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
        $name = 'seller_commission_bill';
        if (Schema::hasColumn($name, 'negative_amount')) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('negative_amount');
            });
        }
    }
}
