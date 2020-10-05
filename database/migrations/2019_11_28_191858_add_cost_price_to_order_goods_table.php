<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCostPriceToOrderGoodsTable extends Migration
{
    private $table = 'order_goods';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->table, 'cost_price')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->decimal('cost_price', 10, 2)->default(0)->comment('商品成本价');
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
        if (Schema::hasColumn($this->table, 'cost_price')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('cost_price');
            });
        }
    }
}
