<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 微信通模块数据填充
 * Class WechatModuleSeeder
 */
class WechatModuleSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->wechatTemplate();
        $this->adminAction();
        $this->adminActionTemplate();
        $this->adminActionWxapp();

        $this->upgradeTov1_4_2();
    }

    /**
     * 增加微信消息模板
     */
    private function wechatTemplate()
    {
        $result = DB::table('wechat_template')->count();

        if (empty($result)) {
            // 默认数据
            $rows = [
                [
                    'wechat_id' => 1,
                    'code' => 'TM00016',
                    'template' => '订单号：{{orderID.DATA}}\r\n待付金额：{{orderMoneySum.DATA}}\r\n{{backupFieldName.DATA}}{{backupFieldData.DATA}}\r\n{{remark.DATA}}',
                    'title' => '订单提交成功'
                ],
                [
                    'wechat_id' => 1,
                    'code' => 'OPENTM204987032',
                    'template' => '{{first.DATA}}\r\n订单：{{keyword1.DATA}}\r\n支付状态：{{keyword2.DATA}}\r\n支付日期：{{keyword3.DATA}}\r\n商户：{{keyword4.DATA}}\r\n金额：{{keyword5.DATA}}\r\n{{remark.DATA}}',
                    'title' => '订单支付成功通知'
                ],
                [
                    'wechat_id' => 1,
                    'code' => 'OPENTM202243318',
                    'template' => '{{first.DATA}}\r\n订单内容：{{keyword1.DATA}}\r\n物流服务：{{keyword2.DATA}}\r\n快递单号：{{keyword3.DATA}}\r\n收货信息：{{keyword4.DATA}}\r\n{{remark.DATA}}',
                    'title' => '订单发货通知'
                ],
                [
                    'wechat_id' => 1,
                    'code' => 'OPENTM401833445',
                    'template' => '{{first.DATA}}\r\n变动时间：{{keyword1.DATA}}\r\n变动类型：{{keyword2.DATA}}\r\n变动金额：{{keyword3.DATA}}\r\n当前余额：{{keyword4.DATA}}\r\n{{remark.DATA}}',
                    'title' => '余额变动提示'
                ]
            ];
            DB::table('wechat_template')->insert($rows);
        }
    }

    /**
     * 增加微信通模块权限
     */
    private function adminAction()
    {
        $result = DB::table('admin_action')->where('action_code', 'wechat')->count();

        if (empty($result)) {
            // 默认数据
            $row = [
                'parent_id' => 0,
                'action_code' => 'wechat',
                'seller_show' => 1
            ];
            $action_id = DB::table('admin_action')->insertGetId($row);

            // 默认数据
            $rows = [
                [
                    'parent_id' => $action_id,
                    'action_code' => 'wechat_admin',
                    'seller_show' => 1
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'mass_message',
                    'seller_show' => 1
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'auto_reply',
                    'seller_show' => 1
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'menu',
                    'seller_show' => 1
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'fans',
                    'seller_show' => 1
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'media',
                    'seller_show' => 1
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'qrcode',
                    'seller_show' => 1
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'market',
                    'seller_show' => 1
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'extend',
                    'seller_show' => 1
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'template',
                    'seller_show' => 0
                ]
            ];
            DB::table('admin_action')->insert($rows);
        }
    }

    /**
     * 增加微信通模板消息设置权限
     * seller_show = 0 为不显示商家可分配权限列表
     */
    private function adminActionTemplate()
    {
        $result = DB::table('admin_action')->where('action_code', 'wechat')->first();
        if (!empty($result)) {
            $res = DB::table('admin_action')->where('action_code', 'template')->first();
            if (empty($res)) {
                $row2 = [
                    'parent_id' => $result->action_id,
                    'action_code' => 'template',
                    'seller_show' => 0
                ];
                DB::table('admin_action')->insert($row2);
            } else {
                if ($res->seller_show == 1) {
                    // 更新seller_show为0
                    DB::table('admin_action')->where('action_code', 'template')->update(['seller_show' => 0]);
                }
            }
        }
    }

    /**
     * 增加小程序设置权限
     * seller_show = 0 为不显示商家可分配权限列表
     */
    private function adminActionWxapp()
    {
        $result = DB::table('admin_action')->where('action_code', 'wechat')->first();
        if (!empty($result)) {
            $res = DB::table('admin_action')->where('action_code', 'wxapp_config')->first();
            if (empty($res)) {
                $row2 = [
                    'parent_id' => $result->action_id,
                    'action_code' => 'wxapp_config',
                    'seller_show' => 0
                ];
                DB::table('admin_action')->insert($row2);
            } else {
                if ($res->seller_show == 1) {
                    // 更新seller_show为0
                    DB::table('admin_action')->where('action_code', 'wxapp_config')->update(['seller_show' => 0]);
                }
            }
        }
    }

    /**
     * v1.4.2 更换模板消息
     */
    private function upgradeTov1_4_2()
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

}
