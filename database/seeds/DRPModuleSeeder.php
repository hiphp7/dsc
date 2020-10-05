<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 分销模块数据填充
 * Class DRPModuleSeeder
 */
class DRPModuleSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->drpConfig();
        $this->article();
        $this->wechatTemplate();
        $this->adminAction();
        $this->users();

        $this->upgradeTov1_4();
        $this->upgradeTov1_4_1();
        $this->upgradeTov1_4_2();
        $this->upgradeTov1_4_3();
        $this->upgradeTov1_4_4();
    }

    private function drpConfig()
    {
        $result = DB::table('drp_config')->get();
        $result = $result->toArray();
        if (empty($result)) {
            // 默认数据
            $rows = [
                [
                    'code' => 'notice',
                    'type' => 'textarea',
                    'store_range' => '',
                    'value' => "亲，您的佣金由三部分组成：\r\n1.我的下线购买分销商品，我所获得的佣金（即本一级分销佣金）\r\n2.下级分店的下线会员购买分销商品，我所获得的佣金（即二级分销佣金）\r\n3.下级分店发展的分店的下线会员购买分销商品，我所获得的佣金（即三级分店佣金）。",
                    'name' => '温馨提示',
                    'warning' => '申请成为分销商时，提示用户需要注意的信息',
                ],
                [
                    'code' => 'novice',
                    'type' => 'textarea',
                    'store_range' => '',
                    'value' => "1、开微店收入来源之一：您已成功注册微店，已经取得整个商城的商品销售权，只要您的下线会员购买分销商品，即可获得“一级分销佣金”。\r\n2、开微店收入来源之二：邀请您的朋友注册微店，他就会成为你的下级分销商，他的下线会员购买分销商品，您即可获得“二级分销佣金”。\r\n3、开微店收入来源之三：您的下级分销商邀请他的朋友注册微店后，他朋友的下线会员购买分销商品，您即可获得“三级分销佣金”。",
                    'name' => '新手必读',
                    'warning' => '分销商申请成功后，用户要注意的事项',
                ],
                [
                    'code' => 'withdraw',
                    'type' => 'textarea',
                    'store_range' => '',
                    'value' => '可提现金额为交易成功后7天且为提现范围内的金额',
                    'name' => '提现提示',
                    'warning' => '申请提现时，少于该值将无法提现',
                ],
                [
                    'code' => 'draw_money',
                    'type' => 'text',
                    'store_range' => '',
                    'value' => '10',
                    'name' => '提现金额',
                    'warning' => '申请提现时，少于该值将无法提现',
                ],
                [
                    'code' => 'issend',
                    'type' => 'radio',
                    'store_range' => '0,1',
                    'value' => '1',
                    'name' => '消息推送',
                    'warning' => '申请店铺成功时,推送消息到微信',
                ],
                [
                    'code' => 'isbuy',
                    'type' => 'radio',
                    'store_range' => '0,1',
                    'value' => '1',
                    'name' => '购买成为分销商',
                    'warning' => '是否开启购买成为分销商,默认申请成为分销商',
                ],
                [
                    'code' => 'buy_money',
                    'type' => 'text',
                    'store_range' => '',
                    'value' => '100',
                    'name' => '购买金额',
                    'warning' => '购买金额达到该数值,才能成为分销商',
                ],
                [
                    'code' => 'isdrp',
                    'type' => 'radio',
                    'store_range' => '0,1',
                    'value' => '1',
                    'name' => '商品分销模式',
                    'warning' => '是否开启分销模式,默认分销模式。控制商品详情页‘我要分销’按钮',
                ],
                [
                    'code' => 'ischeck',
                    'type' => 'radio',
                    'store_range' => '0,1',
                    'value' => '1',
                    'name' => '分销商审核',
                    'warning' => '成为分销商,是否需要审核',
                ],
                [
                    'code' => 'drp_affiliate',
                    'type' => '',
                    'store_range' => '',
                    'value' => 'a:3:{s:6:"config";a:5:{s:6:"expire";i:0;s:11:"expire_unit";s:3:"day";s:3:"day";s:1:"8";s:15:"level_point_all";s:2:"8%";s:15:"level_money_all";s:2:"1%";}s:4:"item";a:3:{i:0;a:2:{s:11:"level_point";s:3:"60%";s:11:"level_money";s:3:"60%";}i:1;a:2:{s:11:"level_point";s:3:"30%";s:11:"level_money";s:3:"30%";}i:2;a:2:{s:11:"level_point";s:3:"10%";s:11:"level_money";s:3:"10%";}}s:2:"on";i:1;}',
                    'name' => '三级分销比例',
                    'warning' => '',
                ],
                [
                    'code' => 'custom_distributor',
                    'type' => 'text',
                    'store_range' => '',
                    'value' => '代言人',
                    'name' => '自定义“分销商”名称',
                    'warning' => '替换设定的分销商名称',
                ],
                [
                    'code' => 'custom_distribution',
                    'type' => 'text',
                    'store_range' => '',
                    'value' => '代言',
                    'name' => '自定义“分销”名称',
                    'warning' => '替换设定的分销名称',
                ],
                [
                    'code' => 'commission',
                    'type' => 'radio',
                    'store_range' => '0,1',
                    'value' => '0',
                    'name' => '是否显示佣金比例',
                    'warning' => '控制店铺页面是否显示佣金比例',
                ],
                [
                    'code' => 'is_buy_money',
                    'type' => 'radio',
                    'store_range' => '0,1',
                    'value' => '0',
                    'name' => '累计消费金额',
                    'warning' => '是否开启购物累计消费金额满足设置才能开店',
                ],
                [
                    'code' => 'buy',
                    'type' => 'text',
                    'store_range' => '',
                    'value' => '200',
                    'name' => '设置累计消费金额',
                    'warning' => '设置会员累计消费金额',
                ]
            ];
            DB::table('drp_config')->insert($rows);
        }

        $result = DB::table('drp_config')->where('code', 'count_commission')->get();
        $result = $result->toArray();
        $result_register = DB::table('drp_config')->where('code', 'register')->get();
        $result_register = $result_register->toArray();
        $result_isdistributionr = DB::table('drp_config')->where('code', 'isdistribution')->get();
        $result_isdistributionr = $result_isdistributionr->toArray();
        if (empty($result)) {
            // 插入新数据
            $rows = [
                [
                    'code' => 'count_commission',
                    'type' => 'radio',
                    'store_range' => '0,1,2',
                    'value' => '2',
                    'name' => '按时间统计分销商佣金排行',
                    'warning' => '按时间统计分销商佣金进行分销商排行，可以按 周，月，年 排行',
                ]
            ];
            DB::table('drp_config')->insert($rows);
        }

        if (empty($result_register)) {
            // 插入新数据
            $rows = [
                [
                    'code' => 'register',
                    'type' => 'radio',
                    'store_range' => '0,1',
                    'value' => '0',
                    'name' => '开启分销商店铺自动注册',
                    'warning' => '开启分销商店铺自动注册后，授权登录，关注商城会自动创建一个分销商店铺',
                ]
            ];
            DB::table('drp_config')->insert($rows);
        }
        if (empty($result_isdistributionr)) {
            // 插入新数据
            $rows = [
                [
                    'code' => 'isdistribution',
                    'type' => 'radio',
                    'store_range' => '0,1',
                    'value' => '1',
                    'name' => '分销分成模式',
                    'warning' => '开启分成模式从上级分销商开始分成，否则从当前下单分销商分成',
                ]
            ];
            DB::table('drp_config')->insert($rows);
        }
        /**
         * 修改 isdistribution 描述
         */
        if (!empty($result_isdistributionr)) {
            DB::table('drp_config')->where('code', 'isdistribution')->update(['warning' => '开启分成模式从上级分销商开始分成，否则从当前下单分销商分成']);
        }

        $count = DB::table('drp_config')->where('code', 'articlecatid')->count();

        if ($count < 1) {
            // 插入新数据
            $rows = [
                [
                    'code' => 'articlecatid',
                    'type' => 'text',
                    'store_range' => '',
                    'value' => '1000',
                    'name' => '指定分销文章分类',
                    'warning' => '分销店铺中心新手必看指定文章分类下的文章',
                ]
            ];
            DB::table('drp_config')->insert($rows);
        }


        $result = DB::table('drp_config')->where('code', 'agreement_id')->get();
        $result = $result->toArray();

        if (empty($result)) {
            // 插入新数据
            $rows = [
                [
                    'code' => 'agreement_id',
                    'type' => 'text',
                    'store_range' => '',
                    'value' => '6',
                    'name' => '指定高级用户协议文章',
                    'warning' => '大商创高级用户正式协议',
                ]
            ];
            DB::table('drp_config')->insert($rows);
        }
    }

    private function article()
    {
        $result = DB::table('article_cat')->where('cat_id', 1000)->get();
        $result = $result->toArray();
        if (empty($result)) {
            $cats = [
                [
                    'cat_id' => 1000,
                    'cat_name' => '微分销',
                    'cat_type' => 1,
                    'keywords' => '分销',
                    'show_in_nav' => 1,
                ]
            ];
            DB::table('article_cat')->insert($cats);

            $articles = [
                [
                    'cat_id' => 1000,
                    'title' => '什么是微分销？',
                    'content' => '微分销是一体化微信分销交易平台，基于朋友圈传播，帮助企业打造“企业微商城+粉丝微店+员工微店”的多层级微信营销模式，轻松带领千万微信用户一起为您的商品进行宣传及销售。',
                    'keywords' => '分销',
                    'is_open' => 1,
                    'add_time' => '1467962482'
                ],
                [
                    'cat_id' => 1000,
                    'title' => '如何申请成为分销商？',
                    'content' => '关注微信公众号，进入会员中心点击我的微店。申请后，等待管理员审核通过，即可拥有自己的微店，坐等佣金收入分成！',
                    'keywords' => '分销',
                    'is_open' => 1,
                    'add_time' => '1467962482'
                ]
            ];
            DB::table('article')->insert($articles);
        }
    }

    private function wechatTemplate()
    {
        $result = DB::table('wechat_template')->where('code', 'OPENTM207126233')->first();
        if (empty($result)) {
            // 默认数据
            $rows = [
                [
                    'wechat_id' => 1,
                    'code' => 'OPENTM207126233',
                    'template' => '{{first.DATA}}\r\n分销商名称：{{keyword1.DATA}}\r\n分销商电话：{{keyword2.DATA}}\r\n申请时间：{{keyword3.DATA}}\r\n{{remark.DATA}}',
                    'title' => '分销商申请成功'
                ]
            ];
            DB::table('wechat_template')->insert($rows);
        }

        $result_2 = DB::table('wechat_template')->where('code', 'OPENTM202967310')->first();
        if (empty($result_2)) {
            // 插入新数据
            $rows = [
                [
                    'wechat_id' => 1,
                    'code' => 'OPENTM202967310',
                    'template' => '{{first.DATA}}会员编号：{{keyword1.DATA}}加入时间：{{keyword2.DATA}}{{remark.DATA}}',
                    'title' => '新会员加入通知'
                ]
            ];
            DB::table('wechat_template')->insert($rows);
        }
        $result_3 = DB::table('wechat_template')->where('code', 'OPENTM220197216')->first();
        if (!empty($result_3)) {
            // 删除旧数据
            DB::table('wechat_template')->where('code', 'OPENTM220197216')->delete();
        }
    }

    private function adminAction()
    {
        $result = DB::table('admin_action')->where('action_code', 'drp')->get();
        $result = $result->toArray();
        if (empty($result)) {
            // 默认数据
            $row = [
                'parent_id' => 0,
                'action_code' => 'drp',
                'seller_show' => 0
            ];
            $action_id = DB::table('admin_action')->insertGetId($row);

            // 默认数据
            $rows = [
                [
                    'parent_id' => $action_id,
                    'action_code' => 'drp_config',
                    'seller_show' => 0
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'drp_shop',
                    'seller_show' => 0
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'drp_list',
                    'seller_show' => 0
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'drp_order_list',
                    'seller_show' => 0
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'drp_set_config',
                    'seller_show' => 0
                ]
            ];
            DB::table('admin_action')->insert($rows);
        }
    }

    private function users()
    {
        $where = [
            ['parent_id', '>', 0],
            ['drp_parent_id', '=', 0]
        ];
        $result = DB::table('users')->select('user_id', 'parent_id')->where($where)->get();
        $result = $result->toArray();
        if (!empty($result)) {
            foreach ($result as $user) {
                $data = [
                    'drp_parent_id' => $user->parent_id
                ];
                DB::table('users')->where('user_id', $user->user_id)->update($data);
            }
        }
    }

    /**
     * 升级 v1.4.0
     */
    protected function upgradeTov1_4()
    {
        /* 更新分销商配置分组 start */

        // 更新排序
        DB::table('drp_config')->update(['sort_order' => '50']);

        // 隐藏 原购买成为分销商、累计消费金额、分销商审核 开关配置
        DB::table('drp_config')->whereIn('code', ['isbuy', 'is_buy_money', 'buy_money', 'buy'])->update(['type' => 'hidden']);

        /* 新增分销商升级条件字段值 start */
        $this->addDrpUpgradeSeeder();
        /* 新增分销商升级条件字段值 end */


        /* 新增分销广告 start */
        $this->addDrpTouchAdPosition();
        /* 新增分销广告 end */
    }

    /**
     * 新增分销商升级条件字段值
     */
    protected function addDrpUpgradeSeeder()
    {
        // 成为分销商条件：分销订单总金额
        $buy_goods = DB::table('drp_upgrade_condition')->where('name', 'all_order_money')->count();
        if (empty($buy_goods)) {
            // 插入新数据
            $rows = [
                [
                    'name' => 'all_order_money',
                    'dsc' => '分销订单总金额',
                ]
            ];
            DB::table('drp_upgrade_condition')->insert($rows);
        }

        // 成为分销商条件：一级分销订单总额
        $buy_goods = DB::table('drp_upgrade_condition')->where('name', 'all_direct_order_money')->count();
        if (empty($buy_goods)) {
            // 插入新数据
            $rows = [
                [
                    'name' => 'all_direct_order_money',
                    'dsc' => '一级分销订单总额',
                ]
            ];
            DB::table('drp_upgrade_condition')->insert($rows);
        }

        // 成为分销商条件：分销订单总笔数
        $buy_goods = DB::table('drp_upgrade_condition')->where('name', 'all_order_num')->count();
        if (empty($buy_goods)) {
            // 插入新数据
            $rows = [
                [
                    'name' => 'all_order_num',
                    'dsc' => '分销订单总笔数',
                ]
            ];
            DB::table('drp_upgrade_condition')->insert($rows);
        }

        // 成为分销商条件：一级分销订单总数
        $buy_goods = DB::table('drp_upgrade_condition')->where('name', 'all_direct_order_num')->count();
        if (empty($buy_goods)) {
            // 插入新数据
            $rows = [
                [
                    'name' => 'all_direct_order_num',
                    'dsc' => '一级分销订单总数',
                ]
            ];
            DB::table('drp_upgrade_condition')->insert($rows);
        }

        // 成为分销商条件：自购订单金额
        $buy_goods = DB::table('drp_upgrade_condition')->where('name', 'all_self_order_money')->count();
        if (empty($buy_goods)) {
            // 插入新数据
            $rows = [
                [
                    'name' => 'all_self_order_money',
                    'dsc' => '自购订单金额',
                ]
            ];
            DB::table('drp_upgrade_condition')->insert($rows);
        }

        // 成为分销商条件：自购订单数量
        $buy_goods = DB::table('drp_upgrade_condition')->where('name', 'all_self_order_num')->count();
        if (empty($buy_goods)) {
            // 插入新数据
            $rows = [
                [
                    'name' => 'all_self_order_num',
                    'dsc' => '自购订单数量',
                ]
            ];
            DB::table('drp_upgrade_condition')->insert($rows);
        }

        // 成为分销商条件：下级总数量
        $buy_goods = DB::table('drp_upgrade_condition')->where('name', 'all_direct_user_num')->count();
        if (empty($buy_goods)) {
            // 插入新数据
            $rows = [
                [
                    'name' => 'all_direct_user_num',
                    'dsc' => '下级总数量',
                ]
            ];
            DB::table('drp_upgrade_condition')->insert($rows);
        }

        // 成为分销商条件：直属下级总数量
        $buy_goods = DB::table('drp_upgrade_condition')->where('name', 'all_direct_drp_num')->count();
        if (empty($buy_goods)) {
            // 插入新数据
            $rows = [
                [
                    'name' => 'all_direct_drp_num',
                    'dsc' => '直属下级总数量',
                ]
            ];
            DB::table('drp_upgrade_condition')->insert($rows);
        }

        // 成为分销商条件：下级分销商总人数
        $buy_goods = DB::table('drp_upgrade_condition')->where('name', 'all_indirect_drp_num')->count();
        if (empty($buy_goods)) {
            // 插入新数据
            $rows = [
                [
                    'name' => 'all_indirect_drp_num',
                    'dsc' => '下级分销商总人数',
                ]
            ];
            DB::table('drp_upgrade_condition')->insert($rows);
        }

        // 成为分销商条件：一级分销商人数
        $buy_goods = DB::table('drp_upgrade_condition')->where('name', 'all_develop_drp_num')->count();
        if (empty($buy_goods)) {
            // 插入新数据
            $rows = [
                [
                    'name' => 'all_develop_drp_num',
                    'dsc' => '一级分销商人数',
                ]
            ];
            DB::table('drp_upgrade_condition')->insert($rows);
        }

        // 成为分销商条件：升级商品ID
        $buy_goods = DB::table('drp_upgrade_condition')->where('name', 'goods_id')->count();
        if (empty($buy_goods)) {
            // 插入新数据
            $rows = [
                [
                    'name' => 'goods_id',
                    'dsc' => '升级商品ID',
                ]
            ];
            DB::table('drp_upgrade_condition')->insert($rows);
        }

        // 成为分销商条件：已提现佣金总额
        $buy_goods = DB::table('drp_upgrade_condition')->where('name', 'withdraw_all_money')->count();
        if (empty($buy_goods)) {
            // 插入新数据
            $rows = [
                [
                    'name' => 'withdraw_all_money',
                    'dsc' => '已提现佣金总额',
                ]
            ];
            DB::table('drp_upgrade_condition')->insert($rows);
        }

    }


    /**
     * 分销广告位
     */
    private function addDrpTouchAdPosition()
    {
        $result = DB::table('touch_ad_position')->where('ad_type', 'drp')->count();
        if (empty($result)) {
            // 默认数据
            $row = [
                'position_name' => '分销-banner广告位',
                'ad_width' => '360',
                'ad_height' => '168',
                'position_style' => '{foreach $ads as $ad}<div class="swiper-slide">{$ad}</div>{/foreach}' . "\n" . '',
                'theme' => 'ecmoban_dsc2017',
                'tc_type' => 'banner',
                'ad_type' => 'drp'
            ];
            $position_id = DB::table('touch_ad_position')->insertGetId($row);
            if ($position_id > 0) {
                $this->addTouchAd($position_id);
            }
        }
    }

    private function addTouchAd($position_id = 0)
    {
        $result = DB::table('touch_ad')->where('position_id', $position_id)->count();
        if (empty($result)) {
            // 默认数据
            $rows = [
                [
                    'position_id' => $position_id,
                    'media_type' => '0',
                    'ad_name' => '分销广告r-01',
                    'ad_link' => '',
                    'link_color' => '',
                    'ad_code' => '1509663779787829146.jpg',
                    'start_time' => '1569219147',
                    'end_time' => '1609434061',
                    'link_man' => '',
                    'link_email' => '',
                    'link_phone' => '',
                    'click_count' => '0',
                    'enabled' => '1',
                    'is_new' => '0',
                    'is_hot' => '0',
                    'is_best' => '0',
                    'public_ruid' => '0',
                    'ad_type' => '0',
                    'goods_name' => '0',
                ]
            ];
            DB::table('touch_ad')->insert($rows);
        }
    }

    /**
     * 升级 v1.4.1
     */
    protected function upgradeTov1_4_1()
    {
        // 更新排序
        DB::table('drp_config')->update(['sort_order' => '50']);

        // 还原1.4版本隐藏的 分销商审核字段
        DB::table('drp_config')->whereIn('code', ['ischeck'])->update(['type' => 'radio']);
        // 隐藏原 分销比例设置
        DB::table('drp_config')->whereIn('code', ['drp_affiliate'])->update(['type' => 'hidden']);


        // 分销配置重新分组： 空 基本配置、 show 显示配置、 scale 结算配置、qrcode 分享配置、message 消息配置

        DB::table('drp_config')->whereIn('code', ['register', 'ischeck'])->update(['group' => '']);

        DB::table('drp_config')->whereIn('code', ['notice', 'novice', 'isdrp', 'custom_distributor', 'custom_distribution', 'commission', 'agreement_id', 'count_commission', 'articlecatid'])->update(['group' => 'show']);

        DB::table('drp_config')->whereIn('code', ['withdraw', 'draw_money'])->update(['group' => 'scale']);

        DB::table('drp_config')->whereIn('code', ['issend'])->update(['group' => 'message']);


        // 新增配置
        $drp_config = DB::table('drp_config')->where('code', 'drp_affiliate')->value('value');
        if ($drp_config) {
            $drp_config = unserialize($drp_config);
        }
        // 开启VIP分销
        $drp_affiliate_on = DB::table('drp_config')->where('code', 'drp_affiliate_on')->count();
        if (empty($drp_affiliate_on)) {
            // 插入新数据
            $rows = [
                [
                    'code' => 'drp_affiliate_on',
                    'type' => 'radio',
                    'store_range' => '0,1',
                    'value' => $drp_config['on'] ?? 1,
                    'name' => '开启VIP分销',
                    'warning' => '如果关闭则不会计算分销佣金',
                    'sort_order' => '0',
                    'group' => '',
                ]
            ];
            DB::table('drp_config')->insert($rows);
        }

        // 佣金结算时间
        $settlement_time = DB::table('drp_config')->where('code', 'settlement_time')->count();
        if (empty($settlement_time)) {
            // 插入新数据
            $rows = [
                [
                    'code' => 'settlement_time',
                    'type' => 'text',
                    'store_range' => '',
                    'value' => $drp_config['config']['day'] ?? 7,
                    'name' => '佣金分成时间',
                    'warning' => '设置会员确认收货后，X天后，生成分成订单。单位：天，默认7天',
                    'sort_order' => '50',
                    'group' => 'scale',
                ]
            ];
            DB::table('drp_config')->insert($rows);
        }

        // 佣金结算时机
        $settlement_type = DB::table('drp_config')->where('code', 'settlement_type')->count();
        if (empty($settlement_type)) {
            // 插入新数据
            $rows = [
                [
                    'code' => 'settlement_type',
                    'type' => 'radio',
                    'store_range' => '0,1', // 0 禁用即手动 、1 启用即自动
                    'value' => '0',
                    'name' => '是否自动分佣',
                    'warning' => '设置禁用即手动，则订单确认收货后，过了结算分成时间，生成的分成订单需手动点击分成;<br/>设置启用即自动，则订单确认收货后，过了结算分成时间，系统将会自动分成',
                    'sort_order' => '51',
                    'group' => 'scale',
                ]
            ];
            DB::table('drp_config')->insert($rows);
        }

        //会员分销权益卡管理
        $count = DB::table('admin_action')->where('action_code', 'drpcard_manage')->count();
        if ($count <= 0) {

            $parent_id = DB::table('admin_action')->where('action_code', 'drp')->value('action_id');

            DB::table('admin_action')->insert([
                'parent_id' => $parent_id,
                'action_code' => 'drpcard_manage',
                'seller_show' => '0'
            ]);
        }

        DB::table('drp_config')->where('code', ['drp_affiliate_on'])->update(['sort_order' => '0']);
    }

    /**
     * 升级 v1.4.2
     */
    protected function upgradeTov1_4_2()
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
     * 升级 v1.4.3
     */
    protected function upgradeTov1_4_3()
    {
        // 新增配置 客户关系有效期
        $children_expiry_days = DB::table('drp_config')->where('code', 'children_expiry_days')->count();
        if (empty($children_expiry_days)) {
            // 插入新数据
            $rows = [
                [
                    'code' => 'children_expiry_days',
                    'type' => 'text',
                    'store_range' => '', // 0永久有效，数值固定天数有效
                    'value' => 7,
                    'name' => '客户关系有效期',
                    'warning' => "有效期天数，0永久有效，到期后关系解绑；若会员在有效期内，每次访问带参数的链接或重新扫码，自动更新有效期为x天（不累加）。",
                    'sort_order' => '50',
                    'group' => '',
                ]
            ];
            DB::table('drp_config')->insert($rows);
        }
    }


    /**
     * 升级 v1.4.4
     */
    protected function upgradeTov1_4_4()
    {
        // 还原分销内购模式
        DB::table('drp_config')->where('code', ['isdistribution'])->update(['type' => 'radio', 'store_range' => '0,1,2', 'warning' => '']);
        DB::table('drp_config')->where('code', ['drp_affiliate_mode'])->update(['warning' => '', 'name' => '分销商业绩归属']);

        // 更新旧分销商申请时间
        DB::table('drp_shop')->where('apply_time', '')->where('create_time', '<>', '')
            ->chunkById(1000, function ($users) {
                foreach ($users as $user) {
                    DB::table('drp_shop')
                        ->where('id', $user->id)
                        ->update(['apply_time' => $user->create_time]);
                }
            });

        // 更新旧分销商权益卡领取有效期类型
        DB::table('drp_shop')->where('expiry_type', '')
            ->chunkById(1000, function ($users) {
                foreach ($users as $user) {
                    if ($user->membership_card_id > 0) {
                        $expiry_type = DB::table('user_membership_card')->where('id', $user->membership_card_id)->value('expiry_type');
                        DB::table('drp_shop')
                            ->where('id', $user->id)
                            ->update(['expiry_type' => $expiry_type]);
                    } else {
                        continue;
                    }
                }
            });

    }
}
