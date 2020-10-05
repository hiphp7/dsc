<?php

namespace App\Services\UserRights;

use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\PluginManageService;
use Illuminate\Support\Facades\DB;


class SyncDataService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;
    protected $pluginManageService;
    protected $userRightsManageService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        PluginManageService $pluginManageService,
        UserRightsManageService $userRightsManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->pluginManageService = $pluginManageService;
        $this->userRightsManageService = $userRightsManageService;
    }

    public function syncData()
    {
        //兼容旧版本数据,会员卡数据为空认为是初始化数据
        $count_user_membership_card = DB::table('user_membership_card')->count();
        if (empty($count_user_membership_card)) {
            //同步设置
            $drp_config = DB::table('drp_config')->where('code', 'drp_affiliate')->value('value');
            $drp_config = empty($drp_config) ? [] : unserialize($drp_config);

            //会员权益插件（升级有礼、商品分销、会员卡分销、尊享客服）
            $plugin_code = ['upgrade', 'drp_goods', 'drp', 'customer'];
            // 安装插件
            $code = 'upgrade';
            $plugin_upgrade_id = DB::table('user_membership_rights')->where('code', $code)->value('id');
            if (empty($plugin_upgrade_id)) {
                $config = $this->pluginManageService->getPluginConfig($code, 'UserRights');
                $config['rights_configure'][0]['value'] = 100;//升级默认积分
                $config['rights_configure'] = serialize($config['rights_configure']);
                $config['trigger_point'] = 'direct';
                $config['enable'] = 1;
                $res = $this->userRightsManageService->createUserRights($code, $config);
                if ($res) {
                    $res = $res->toArray();
                    $plugin_upgrade_id = $res['id'];
                }
            }

            $code = 'drp_goods';
            $plugin_drp_goods_id = DB::table('user_membership_rights')->where('code', $code)->value('id');
            if (empty($plugin_drp_goods_id)) {
                $config = $this->pluginManageService->getPluginConfig($code, 'UserRights');

                $config['rights_configure'][0]['value'] = $drp_config['item'][0]['credit_t'] ?? '20%';
                $config['rights_configure'][1]['value'] = $drp_config['item'][1]['credit_y'] ?? '20%';
                $config['rights_configure'][2]['value'] = $drp_config['item'][2]['credit_j'] ?? '20%';

                $config['rights_configure'] = serialize($config['rights_configure']);
                $config['trigger_point'] = 'direct';
                $config['enable'] = 1;
                $res = $this->userRightsManageService->createUserRights($code, $config);
                if ($res) {
                    $res = $res->toArray();
                    $plugin_drp_goods_id = $res['id'];
                }
            }

            $code = 'drp';
            $plugin_drp_id = DB::table('user_membership_rights')->where('code', $code)->value('id');
            if (empty($plugin_drp_id)) {
                $config = $this->pluginManageService->getPluginConfig($code, 'UserRights');
                $config['rights_configure'][0]['value'] = '20%';//一级分佣比例
                $config['rights_configure'][1]['value'] = '10%';//二级分佣比例
                $config['rights_configure'][2]['value'] = '5%';//三级分佣比例
                $config['rights_configure'] = serialize($config['rights_configure']);
                $config['trigger_point'] = 'direct';
                $config['enable'] = 1;
                $res = $this->userRightsManageService->createUserRights($code, $config);
                if ($res) {
                    $res = $res->toArray();
                    $plugin_drp_id = $res['id'];
                }
            }

            $code = 'customer';
            $plugin_customer_id = DB::table('user_membership_rights')->where('code', $code)->value('id');
            if (empty($plugin_customer_id)) {
                $config = $this->pluginManageService->getPluginConfig($code, 'UserRights');
                $config['rights_configure'][0]['value'] = $GLOBALS['_CFG']['service_phone'] ?? '';//客服电话
                $config['rights_configure'] = serialize($config['rights_configure']);
                $config['trigger_point'] = 'direct';
                $config['enable'] = 1;
                $res = $this->userRightsManageService->createUserRights($code, $config);
                if ($res) {
                    $res = $res->toArray();
                    $plugin_customer_id = $res['id'];
                }
            }


            //特殊会员等级
            $rank_name_tong = '铜牌会员权益卡';
            $rank_id_tong = DB::table('user_rank')->where('rank_name', $rank_name_tong)->value('rank_id');
            if (empty($rank_id_tong)) {
                $data_rank_tong = [
                    'rank_name' => $rank_name_tong,
                    'discount' => 60,
                    'show_price' => 1,
                    'special_rank' => 1
                ];
                $rank_id_tong = DB::table('user_rank')->insertGetId($data_rank_tong);
            }


            $rank_name_yin = '银牌会员权益卡';
            $rank_id_yin = DB::table('user_rank')->where('rank_name', $rank_name_yin)->value('rank_id');
            if (empty($rank_id_yin)) {
                $data_rank_yin = [
                    'rank_name' => $rank_name_yin,
                    'discount' => 70,
                    'show_price' => 1,
                    'special_rank' => 1
                ];
                $rank_id_yin = DB::table('user_rank')->insertGetId($data_rank_yin);
            }

            $rank_name_jin = '金牌会员权益卡';
            $rank_id_jin = DB::table('user_rank')->where('rank_name', $rank_name_jin)->value('rank_id');
            if (empty($rank_id_jin)) {
                $data_rank_jin = [
                    'rank_name' => '金牌会员权益卡',
                    'discount' => 80,
                    'show_price' => 1,
                    'special_rank' => 1
                ];
                $rank_id_jin = DB::table('user_rank')->insertGetId($data_rank_jin);
            }

            //会员卡
            $config_buy_pay_point = DB::table('drp_config')->where('code', 'buy_pay_point')->value('value');
            $config_buy_goods = DB::table('drp_config')->where('code', 'buy_goods')->value('value');
            $config_buy_money = DB::table('drp_config')->where('code', 'buy_money')->value('value');//消费金额
            $config_buy = DB::table('drp_config')->where('code', 'buy')->value('value');//消费金额累积
            $card_receive_value = [
                [
                    "type" => "buy",//消费金额
                    "value" => $config_buy_money ?? 10
                ],
                [
                    "type" => "order",//消费金额累积
                    "value" => $config_buy ?? 100
                ],
                [
                    "type" => "integral",//积分兑换
                    "value" => $config_buy_pay_point ?? 100
                ]
            ];
            $time = $this->timeRepository->getGmTime();
            $card_id_tong = DB::table('user_membership_card')->where('name', $rank_name_tong)->value('id');
            if (empty($card_id_tong)) {
                $data_tong = [
                    'name' => $rank_name_tong,
                    'type' => 2,
                    'description' => $rank_name_tong,
                    'background_color' => '#efccc6',
                    'receive_value' => serialize($card_receive_value),//同步历史设置
                    'expiry_type' => 'forever',
                    'enable' => 1,
                    'add_time' => $time,
                    'update_time' => $time,
                    'user_rank_id' => $rank_id_tong,
                ];
                $card_id_tong = DB::table('user_membership_card')->insertGetId($data_tong);
            }

            $card_id_yin = DB::table('user_membership_card')->where('name', $rank_name_yin)->value('id');
            if (empty($card_id_yin)) {
                $data_yin = [
                    'name' => $rank_name_yin,
                    'type' => 2,
                    'description' => $rank_name_yin,
                    'background_color' => '#f3eef4',
                    'receive_value' => serialize($card_receive_value),//同步历史设置
                    'expiry_type' => 'forever',
                    'enable' => 1,
                    'add_time' => $time,
                    'update_time' => $time,
                    'user_rank_id' => $rank_id_yin,
                ];
                $card_id_yin = DB::table('user_membership_card')->insertGetId($data_yin);
            }

            $card_id_jin = DB::table('user_membership_card')->where('name', $rank_name_jin)->value('id');
            if (empty($card_id_jin)) {
                $data_jin = [
                    'name' => $rank_name_jin,
                    'type' => 2,
                    'description' => $rank_name_jin,
                    'background_color' => '#f5e7cd',
                    'receive_value' => serialize($card_receive_value),//同步历史设置
                    'expiry_type' => 'forever',
                    'enable' => 1,
                    'add_time' => $time,
                    'update_time' => $time,
                    'user_rank_id' => $rank_id_jin,
                ];
                $card_id_jin = DB::table('user_membership_card')->insertGetId($data_jin);
            }

            //会员卡对应权益
            //升级有礼 权益空 继承
            $count = DB::table('user_membership_card_rights')->where('membership_card_id', $card_id_tong)->where('rights_id', $plugin_upgrade_id)->count();
            if ($count <= 0) {
                $data = [
                    'rights_id' => $plugin_upgrade_id,
                    'membership_card_id' => $card_id_tong,
                    'add_time' => $time
                ];
                DB::table('user_membership_card_rights')->insert($data);
            }
            $count = DB::table('user_membership_card_rights')->where('membership_card_id', $card_id_yin)->where('rights_id', $plugin_upgrade_id)->count();
            if ($count <= 0) {
                $data = [
                    'rights_id' => $plugin_upgrade_id,
                    'membership_card_id' => $card_id_yin,
                    'add_time' => $time
                ];
                DB::table('user_membership_card_rights')->insert($data);
            }
            $count = DB::table('user_membership_card_rights')->where('membership_card_id', $card_id_jin)->where('rights_id', $plugin_upgrade_id)->count();
            if ($count <= 0) {
                $data = [
                    'rights_id' => $plugin_upgrade_id,
                    'membership_card_id' => $card_id_jin,
                    'add_time' => $time
                ];
                DB::table('user_membership_card_rights')->insert($data);
            }

            //商品分销-同步配置
            $rights_configure = DB::table('user_membership_rights')->where('id', $plugin_drp_goods_id)->value('rights_configure');
            if ($rights_configure) {
                $rights_configure = unserialize($rights_configure);
            }
            $count = DB::table('user_membership_card_rights')->where('membership_card_id', $card_id_tong)->where('rights_id', $plugin_drp_goods_id)->count();
            if ($count <= 0) {
                $data = [
                    'rights_id' => $plugin_drp_goods_id,
                    'membership_card_id' => $card_id_tong,
                    'add_time' => $time
                ];
                if ($rights_configure) {
                    $rights_configure[0]['value'] = $drp_config['item'][0]['credit_t'] ?? '20%';
                    $rights_configure[1]['value'] = $drp_config['item'][1]['credit_t'] ?? '20%';
                    $rights_configure[2]['value'] = $drp_config['item'][2]['credit_t'] ?? '20%';
                    $data['rights_configure'] = serialize($rights_configure);
                }

                DB::table('user_membership_card_rights')->insert($data);
            }
            //银牌
            $count = DB::table('user_membership_card_rights')->where('membership_card_id', $card_id_yin)->where('rights_id', $plugin_drp_goods_id)->count();
            if ($count <= 0) {
                $data = [
                    'rights_id' => $plugin_drp_goods_id,
                    'membership_card_id' => $card_id_yin,
                    'add_time' => $time
                ];
                if ($rights_configure) {
                    $rights_configure[0]['value'] = $drp_config['item'][0]['credit_y'] ?? '20%';
                    $rights_configure[1]['value'] = $drp_config['item'][1]['credit_y'] ?? '20%';
                    $rights_configure[2]['value'] = $drp_config['item'][2]['credit_y'] ?? '20%';
                    $data['rights_configure'] = serialize($rights_configure);
                }

                DB::table('user_membership_card_rights')->insert($data);
            }
            //金牌
            $count = DB::table('user_membership_card_rights')->where('membership_card_id', $card_id_jin)->where('rights_id', $plugin_drp_goods_id)->count();
            if ($count <= 0) {
                $data = [
                    'rights_id' => $plugin_drp_goods_id,
                    'membership_card_id' => $card_id_jin,
                    'add_time' => $time
                ];
                if ($rights_configure) {
                    $rights_configure[0]['value'] = $drp_config['item'][0]['credit_j'] ?? '20%';
                    $rights_configure[1]['value'] = $drp_config['item'][1]['credit_j'] ?? '20%';
                    $rights_configure[2]['value'] = $drp_config['item'][2]['credit_j'] ?? '20%';
                    $data['rights_configure'] = serialize($rights_configure);
                }

                DB::table('user_membership_card_rights')->insert($data);
            }

            //会员卡分销 权益空 继承
            $count = DB::table('user_membership_card_rights')->where('membership_card_id', $card_id_tong)->where('rights_id', $plugin_drp_id)->count();
            if ($count <= 0) {
                $data = [
                    'rights_id' => $plugin_drp_id,
                    'membership_card_id' => $card_id_tong,
                    'add_time' => $time
                ];
                DB::table('user_membership_card_rights')->insert($data);
            }
            $count = DB::table('user_membership_card_rights')->where('membership_card_id', $card_id_yin)->where('rights_id', $plugin_drp_id)->count();
            if ($count <= 0) {
                $data = [
                    'rights_id' => $plugin_drp_id,
                    'membership_card_id' => $card_id_yin,
                    'add_time' => $time
                ];
                DB::table('user_membership_card_rights')->insert($data);
            }
            $count = DB::table('user_membership_card_rights')->where('membership_card_id', $card_id_jin)->where('rights_id', $plugin_drp_id)->count();
            if ($count <= 0) {
                $data = [
                    'rights_id' => $plugin_drp_id,
                    'membership_card_id' => $card_id_jin,
                    'add_time' => $time
                ];
                DB::table('user_membership_card_rights')->insert($data);
            }

            //尊享客服 权益空 继承
            $count = DB::table('user_membership_card_rights')->where('membership_card_id', $card_id_tong)->where('rights_id', $plugin_customer_id)->count();
            if ($count <= 0) {
                $data = [
                    'rights_id' => $plugin_customer_id,
                    'membership_card_id' => $card_id_tong,
                    'add_time' => $time
                ];
                DB::table('user_membership_card_rights')->insert($data);
            }
            $count = DB::table('user_membership_card_rights')->where('membership_card_id', $card_id_yin)->where('rights_id', $plugin_customer_id)->count();
            if ($count <= 0) {
                $data = [
                    'rights_id' => $plugin_customer_id,
                    'membership_card_id' => $card_id_yin,
                    'add_time' => $time
                ];
                DB::table('user_membership_card_rights')->insert($data);
            }
            $count = DB::table('user_membership_card_rights')->where('membership_card_id', $card_id_jin)->where('rights_id', $plugin_customer_id)->count();
            if ($count <= 0) {
                $data = [
                    'rights_id' => $plugin_customer_id,
                    'membership_card_id' => $card_id_jin,
                    'add_time' => $time
                ];
                DB::table('user_membership_card_rights')->insert($data);
            }


            // 原分销商 数据
            $prefix = config('database.connections.mysql.prefix');
            $table = $prefix . 'drp_shop';
            DB::statement("update `$table` set `open_time` =  `create_time`");

            DB::table('drp_shop')->where('credit_id', 1)->orWhere('credit_id', 0)->where('membership_card_id', 0)->update(['membership_card_id' => $card_id_tong, 'membership_status' => 1]);
            DB::table('drp_shop')->where('credit_id', 2)->where('membership_card_id', 0)->update(['membership_card_id' => $card_id_yin, 'membership_status' => 1]);
            DB::table('drp_shop')->where('credit_id', 3)->where('membership_card_id', 0)->update(['membership_card_id' => $card_id_jin, 'membership_status' => 1]);

            // 修改原分销商的 绑定会员的会员等级 user_rank
            $user_id_tong = DB::table('drp_shop')->where('credit_id', 1)->pluck('user_id');
            if ($user_id_tong) {
                DB::table('users')->whereIn('user_id', $user_id_tong)->update(['user_rank' => $rank_id_tong]);
            }
            $user_id_yin = DB::table('drp_shop')->where('credit_id', 2)->pluck('user_id');
            if ($user_id_yin) {
                DB::table('users')->whereIn('user_id', $user_id_yin)->update(['user_rank' => $rank_id_yin]);
            }
            $user_id_jin = DB::table('drp_shop')->where('credit_id', 3)->pluck('user_id');
            if ($user_id_jin) {
                DB::table('users')->whereIn('user_id', $user_id_jin)->update(['user_rank' => $rank_id_jin]);
            }

            // 处理 原 drp_shop 表 credit_id = 0 需要动态查询订单佣金 拿到 对应的分销商等级id
            $credit_id_0 = DB::table('drp_shop')->where('credit_id', 0)->pluck('user_id');
            if ($credit_id_0) {
                foreach ($credit_id_0 as $user_id) {
                    //echo $user_id;
                    //统计分销商所属订单金额
                    $goods_price = DB::table('order_goods as o')
                        ->leftjoin('drp_log as a', 'o.order_id', '=', 'a.order_id')
                        ->where('a.is_separate', 1)
                        ->where('a.separate_type', '!=', '-1')
                        ->where('a.user_id', $user_id)
                        ->sum('money');
                    $goods_price = $goods_price ?? 0;

                    $credit_id = DB::table('drp_user_credit')->where('min_money', '<=', $goods_price)
                        ->where('max_money', '>', $goods_price)
                        ->orderBy('min_money', 'ASC')
                        ->value('id');

                    if ($credit_id > 0) {
                        DB::table('users')->where('user_id', $user_id)->update(['user_rank' => $credit_id]);
                    }
                }
            }

            return true;
        }

        return false;
    }

}
