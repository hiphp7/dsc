<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeReturnGoodsAddMembershipCardDiscountPriceTable extends Migration
{
    protected $tableName = 'return_goods'; // 退换货订单商品表

    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'membership_card_discount_price')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->decimal('membership_card_discount_price', 10, 2)->unsigned()->default(0.00)->comment('退款订单商品购买权益卡折扣');
            });
        }
    }

    /**
     * 回滚数据库迁移
     *
     * @return void
     */
    public function down()
    {
        // 删除字段
        if (Schema::hasColumn($this->tableName, 'membership_card_discount_price')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('membership_card_discount_price');
            });
        }
    }
}
