<?php

namespace App\Patch;

use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Migration_v1_4_2
{
    public function run()
    {
        $this->shopConfig();


        if (file_exists(MOBILE_DRP)) {

            // 升级 1.4.2
            $this->changeDrpV142();

            $this->DRPModuleSeeder1_4_2();
        }

        if (file_exists(MOBILE_WECHAT)) {
            $this->WechatModuleSeeder1_4_2();
        }

        $this->sellerCommissionBill();
        $this->sellerBillOrder();
        $this->sellerNegativeBill();
        $this->sellerNegativeOrder();
        $this->orderReturn();
    }


    private function changeDrpV142()
    {
        $tableName = 'drp_account_log'; // 分销商记录表
        // 判断字段是否存在添加
        if (!Schema::hasColumn($tableName, 'log_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('log_id')->unsigned()->default(0)->index()->comment('支付日志id,关联pay_log表');
            });
        }
        if (!Schema::hasColumn($tableName, 'drp_is_separate')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('drp_is_separate')->unsigned()->default(0)->comment('分成状态：0 未分成、1 已分成');
            });
        }
        if (!Schema::hasColumn($tableName, 'parent_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('parent_id')->unsigned()->default(0)->comment('推荐人id 关联会员表');
            });
        }

        $tableName = 'drp_log';
        if (!Schema::hasColumn($tableName, 'drp_account_log_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('drp_account_log_id')->unsigned()->default(0)->comment('支付订单id,关联drp_account_log表');
            });
        }
    }

    public function sellerCommissionBill()
    {
        $table = 'seller_commission_bill';
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'rate_fee')) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('rate_fee', 10, 2)->default(0)->comment('跨境税费');
            });
        }
    }

    public function sellerBillOrder()
    {
        $table = 'seller_bill_order';
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'rate_fee')) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('rate_fee', 10, 2)->default(0)->comment('跨境税费');
            });
        }
    }

    public function sellerNegativeBill()
    {
        $table = 'seller_negative_bill';
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'return_rate_price')) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('return_rate_price', 10, 2)->default(0)->comment('跨境税费退款金额');
            });
        }
    }

    public function sellerNegativeOrder()
    {
        $table = 'seller_negative_order';
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'return_rate_price')) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('return_rate_price', 10, 2)->default(0)->comment('跨境税费退款金额');
            });
        }
    }

    public function orderReturn()
    {
        $table = 'order_return';
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'negative_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->integer('negative_id')->index('negative_id')->default(0)->comment('负账单ID');
            });
        }
    }

    /**
     * 升级 v1.4.2
     */
    public function DRPModuleSeeder1_4_2()
    {
        // 隐藏原分销内购设置
        DB::table('drp_config')->whereIn('code', ['isdistribution'])->update(['type' => 'hidden']);

        // 新增配置 注册分成锁定模式
        $drp_affiliate_mode = DB::table('drp_config')->where('code', 'drp_affiliate_mode')->count();
        if (empty($drp_affiliate_mode)) {
            // 插入新数据
            $rows = [
                [
                    'code' => 'drp_affiliate_mode',
                    'type' => 'radio',
                    'store_range' => '0,1', // 0 禁用 1 启用
                    'value' => 1,
                    'name' => '注册锁定模式',
                    'warning' => '注册锁定模式, 默认启用 即注册+分享必须同一人',
                    'sort_order' => '50',
                    'group' => '',
                ]
            ];
            DB::table('drp_config')->insert($rows);
        }
    }

    /**
     * v1.4.2 更换模板消息
     */
    private function WechatModuleSeeder1_4_2()
    {
        // 订单成功通知 TM00016
        $order = DB::table('wechat_template')->where('code', 'OPENTM415293129')->count();
        if (empty($order)) {
            $data = [
                'code' => 'OPENTM415293129',
                'template' => '{{first.DATA}}\r\n订单编号：{{keyword1.DATA}}\r\n应付金额：{{keyword2.DATA}}\r\n下单时间：{{keyword3.DATA}}\r\n{{remark.DATA}}',
                'title' => '订单提交成功提醒',
                'template_id' => '',
                'status' => 0,
                'add_time' => 0
            ];
            DB::table('wechat_template')->where('code', 'TM00016')->update($data);
            DB::table('wechat_template_log')->where('code', 'TM00016')->delete();
        }

        // 分销订单成功通知 OPENTM206328970
        DB::table('wechat_template')->where('code', 'OPENTM206328970')->delete();
        DB::table('wechat_template_log')->where('code', 'OPENTM206328970')->delete();

        // 佣金提醒 OPENTM201812627
        $commission = DB::table('wechat_template')->where('code', 'OPENTM409909643')->count();
        if (empty($commission)) {
            $data = [
                'code' => 'OPENTM409909643',
                'template' => '{{first.DATA}}\r\n分销佣金：{{keyword1.DATA}}\r\n交易金额：{{keyword2.DATA}}\r\n结算时间：{{keyword3.DATA}}\r\n{{remark.DATA}}',
                'title' => '结算成功通知',
                'template_id' => '',
                'status' => 0,
                'add_time' => 0
            ];
            DB::table('wechat_template')->where('code', 'OPENTM201812627')->update($data);
            DB::table('wechat_template_log')->where('code', 'OPENTM201812627')->delete();
        }
    }

    /**
     * @throws \Exception
     */
    private function shopConfig()
    {

        $count = ShopConfig::where('code', 'oss_network')->count();

        if ($count <= 0) {
            $parent_id = ShopConfig::where('code', 'extend_basic')->value('id');

            ShopConfig::insert([
                'parent_id' => $parent_id,
                'code' => 'oss_network',
                'type' => 'hidden',
                'store_range' => '0,1',
                'sort_order' => 1,
                'value' => 0
            ]);
        }

        /* 更新版本 */
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v1.4.2'
        ]);

        $this->clearCache();
    }

    /**
     * @throws \Exception
     */
    private function clearCache()
    {
        cache()->forget('shop_config');
    }
}