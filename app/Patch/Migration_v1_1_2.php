<?php

namespace App\Patch;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migration_v1_1_2
{
    public function run()
    {
        // 判断 pay_log 表字段是否存在添加
        if (!Schema::hasColumn('pay_log', 'pay_trade_data')) {
            Schema::table('pay_log', function (Blueprint $table) {
                $table->text('pay_trade_data')->default('')->comment('在线支付交易数据, 格式json');
            });
        }
        // 判断 order_return 表字段是否存在添加
        if (!Schema::hasColumn('order_return', 'return_trade_data')) {
            Schema::table('order_return', function (Blueprint $table) {
                $table->text('return_trade_data')->default('')->comment('在线退款交易数据, 格式json');
            });
        }
    }
}
