<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRatePriceReturnGoodsTable extends Migration
{
    private $table = 'return_goods';

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

        if (!Schema::hasColumn($this->table, 'rate_price')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->decimal('rate_price', 10, 2)->default('0.00')->comment('退税金额');
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
        if (Schema::hasColumn($this->table, 'rate_price')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('rate_price');
            });
        }
    }
}
