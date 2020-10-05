<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGoodsCouponsToOrderGoodsTable extends Migration
{
    private $table = 'order_goods';

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

        if (!Schema::hasColumn($this->table, 'goods_coupons')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->decimal('goods_coupons', 10, 2)->default(0)->comment('优惠券均摊商品');
            });
        }

        if (Schema::hasColumn($this->table, 'goods_bonus')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->decimal('goods_bonus', 10, 2)->change();
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
        if (Schema::hasColumn($this->table, 'goods_coupons')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('goods_coupons');
            });
        }
    }
}
