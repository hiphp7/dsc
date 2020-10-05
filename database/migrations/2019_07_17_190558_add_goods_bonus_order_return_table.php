<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGoodsBonusOrderReturnTable extends Migration
{
    private $table = 'order_return';

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

        if (!Schema::hasColumn($this->table, 'goods_bonus')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->decimal('goods_bonus', 10, 2)->default(0)->comment('红包均摊商品');
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
        if (Schema::hasColumn($this->table, 'goods_bonus')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('goods_bonus');
            });
        }
    }
}
