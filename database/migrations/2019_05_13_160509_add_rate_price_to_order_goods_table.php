<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRatePriceToOrderGoodsTable extends Migration
{
    /**
     * @var string
     */
    private $table = 'order_goods';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->table, 'rate_price')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->decimal('rate_price', 10, 2)->comment('税费金额');
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
