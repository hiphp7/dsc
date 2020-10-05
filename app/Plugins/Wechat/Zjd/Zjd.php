<?php

namespace App\Plugins\Wechat\Zjd;

use App\Http\Controllers\Wechat\PluginController;
use App\Models\Users;
use App\Models\WechatExtend;
use App\Models\WechatPrize;
use Illuminate\Support\Str;

/**
 * 砸金蛋
 */
class Zjd extends PluginController
{
    // 插件名称
    protected $plugin_name = '';
    // 微信通ID
    protected $wechat_id = 0;
    // 商家ID
    protected $wechat_ru_id = 0;
    // 配置
    protected $cfg = [];

    /**
     * 构造方法
     *
     * @param array $cfg
     */
    public function __construct($cfg = [])
    {
        parent::__construct();
        $this->plugin_name = strtolower(basename(__FILE__, '.php'));
        $this->cfg = $cfg;
        $this->wechat_ru_id = isset($this->cfg['wechat_ru_id']) ? $this->cfg['wechat_ru_id'] : 0;

        $this->wechat_id = $this->wechatService->getWechatId($this->wechat_ru_id);

        $this->plugin_assign('config', $this->cfg);
    }

    /**
     * 安装
     */
    public function install()
    {
        if ($this->wechat_ru_id > 0) {
            // 查询商家管理员
            $this->assign('admin_info', $this->cfg['seller']);
            $this->assign('ru_id', $this->cfg['seller']['ru_id']);
            $this->assign('seller_name', $this->cfg['seller']['user_name']);

            //判断编辑个人资料权限
            $this->assign('privilege_seller', $this->cfg['privilege_seller']);
            // 商家菜单列表
            $this->assign('seller_menu', $this->cfg['menu']);
            // 当前选择菜单
            $this->assign('menu_select', $this->cfg['menu_select']);
            // 当前位置
            $this->assign('postion', $this->cfg['postion']);
        }

        // 编辑
        if (!empty($this->cfg['handler']) && is_array($this->cfg['config'])) {
            // url处理
            if (!empty($this->cfg['config']['plugin_url'])) {
                $this->cfg['config']['plugin_url'] = html_out($this->cfg['config']['plugin_url']);
            }
            // 奖品处理
            if (isset($this->cfg['config']['prize_level'])) {
                if (is_array($this->cfg['config']['prize_level']) && is_array($this->cfg['config']['prize_count']) && is_array($this->cfg['config']['prize_prob']) && is_array($this->cfg['config']['prize_name'])) {
                    foreach ($this->cfg['config']['prize_level'] as $key => $val) {
                        $this->cfg['config']['prize'][] = [
                            'prize_level' => $val,
                            'prize_name' => $this->cfg['config']['prize_name'][$key],
                            'prize_count' => $this->cfg['config']['prize_count'][$key],
                            'prize_prob' => $this->cfg['config']['prize_prob'][$key]
                        ];
                    }
                }
            }
        }
        $this->plugin_assign('config', $this->cfg);
        return $this->plugin_display('install', $this->_data);
    }

    /**
     * 获取数据
     * @param string $fromusername
     * @param array $info
     * @return array
     */
    public function returnData($fromusername = '', $info = [])
    {
        $articles = ['type' => 'text', 'content' => lang('wechat.zjd_empty')];
        // 插件配置
        $config = $this->get_plugin_config($this->wechat_id, $this->plugin_name);
        // 页面信息
        if (isset($config['media']) && !empty($config['media'])) {
            if ($this->wechat_ru_id == 0) {
                $users = $this->wechatUserService->get_wechat_user_id($fromusername);
                if (empty($users) || empty($users['mobile_phone'])) {
                    $articles = ['type' => 'text', 'content' => lang('wechat.zjd_mobile_phone_empty')];
                    return $articles;
                }
            }
            // 数据
            $articles = [];
            $articles['type'] = 'news';
            $articles['content'][0]['Title'] = $config['media']['title'];
            $articles['content'][0]['Description'] = empty($config['media']['digest']) ? Str::limit($config['media']['content'], 100) : $config['media']['digest'];
            $articles['content'][0]['PicUrl'] = $this->wechatHelperService->get_wechat_image_path($config['media']['file']);
            $articles['content'][0]['Url'] = html_out($config['media']['link']);
        }

        return $articles;
    }


    /**
     * 页面显示
     */
    public function html_show()
    {
        // 插件配置
        $config = $this->get_plugin_config($this->wechat_id, $this->plugin_name);

        if (!empty($config)) {
            $num = count($config['prize']);
            if ($num > 0) {
                foreach ($config['prize'] as $key => $val) {
                    // 删除最后一项未中奖
                    if ($key == ($num - 1)) {
                        unset($config['prize'][$key]);
                    }
                }
            }
        }

        $starttime = $this->timeRepository->getLocalStrtoTime($config['starttime']);
        $endtime = $this->timeRepository->getLocalStrtoTime($config['endtime']);
        $nowtime = $this->timeRepository->getGmTime();

        $openid = $this->get_openid($this->wechat_ru_id);

        if (empty($openid)) {
            return $this->show_message(lang('wechat.please_login'));
        }

        // 用户抽奖剩余的次数
        if ($starttime > $nowtime || $endtime < $nowtime) {
            $config['prize_num'] = 0;
            if ($nowtime < $starttime) {
                $config['activity_status'] = 1; // 活动状态 未开始
            }
            if ($nowtime > $endtime) {
                $config['activity_status'] = 2; // 活动状态 活动已结束
            }
        } else {
            if ($config['point_status'] == 1) {
                $users = $this->wechatUserService->get_wechat_user_id($openid);
                if ($users) {
                    $user_pay_points = Users::where(['user_id' => $users['user_id']])->value('pay_points');
                }

                $config['user_pay_points'] = $user_pay_points ?? 0; //显示会员消费积分
                //会员消费积分除以后台填写积分得出次数
                $num = floor($config['user_pay_points'] / $config['point_value']);
                $config['prize_num'] = ($config['point_value'] > 0) ? ($num < 0 ? 0 : $num) : 0;
            } else {
                if ($this->wechat_ru_id > 0) {
                    // 商家抽奖次数 判断 起止时间
                    $num = WechatPrize::where('wechat_id', $this->wechat_id)
                        ->where('openid', $openid)
                        ->where('activity_type', $this->plugin_name)
                        ->whereBetween('dateline', [$starttime, $endtime])
                        ->count();
                } else {
                    // 平台判断 起止时间
                    $num = WechatPrize::where('wechat_id', $this->wechat_id)
                        ->where('openid', $openid)
                        ->where('activity_type', $this->plugin_name)
                        ->where('dateline', '>', $nowtime - $config['point_interval'])
                        ->count();
                }
                $count = isset($num) ? $num : 0;
                $config['prize_num'] = ($config['prize_num'] - $count) < 0 ? 0 : $config['prize_num'] - $count;
            }

            // 活动状态 正在进行
            $config['activity_status'] = 0;
        }

        // 中奖记录 但不含用户本人
        $model = WechatPrize::where('wechat_id', $this->wechat_id)
            ->where('openid', '<>', $openid)
            ->where('activity_type', $this->plugin_name)
            ->whereBetween('dateline', [$starttime, $endtime])
            ->where('prize_type', 1);

        $condition['wechat_id'] = $this->wechat_id;
        $model = $model->with([
            'getWechatUser' => function ($query) use ($condition) {
                $query->select('openid', 'nickname')->where('subscribe', 1)->where('wechat_id', $condition['wechat_id']);
            }
        ]);

        $list = $model->limit(10)
            ->orderBy('dateline', 'DESC')
            ->get();
        $list = $list ? $list->toArray() : [];

        if ($list) {
            foreach ($list as $key => $val) {
                $val = empty($val['get_wechat_user']) ? $val : collect($val)->merge($val['get_wechat_user'])->except('get_wechat_user')->all();
                $list[$key] = $val;
            }
        }

        $this->plugin_assign('list', $list);

        // 用户个人中奖记录 显示1条在前面, 并显示链接跳转到填写中奖地址页面
        $model = WechatPrize::where('wechat_id', $this->wechat_id)
            ->where('openid', $openid)
            ->where('activity_type', $this->plugin_name)
            ->whereBetween('dateline', [$starttime, $endtime])
            ->where('prize_type', 1);

        $condition['wechat_id'] = $this->wechat_id;
        $model = $model->with([
            'getWechatUser' => function ($query) use ($condition) {
                $query->select('openid', 'nickname')->where('subscribe', 1)->where('wechat_id', $condition['wechat_id']);
            }
        ]);

        $list_oneself = $model->orderBy('dateline', 'DESC')
            ->first();
        $list_oneself = $list_oneself ? $list_oneself->toArray() : [];

        if ($list_oneself) {
            $val = empty($list_oneself['get_wechat_user']) ? $list_oneself : collect($list_oneself)->merge($list_oneself['get_wechat_user'])->except('get_wechat_user')->all();
            $val['winner_url'] = route('wechat/plugin_action', ['name' => $this->plugin_name, 'id' => $val['id'], 'wechat_ru_id' => $this->wechat_ru_id]);
            $list_oneself = $val;
        }

        $this->plugin_assign('list_oneself', $list_oneself);

        $config['description'] = nl2br($config['description']);
        $this->plugin_assign('prize_num', count($config['prize'])); // 奖项数量
        $this->plugin_assign('data', $config);
        $this->plugin_assign('plugin_name', $this->plugin_name);
        $this->plugin_assign('wechat_ru_id', $this->wechat_ru_id);

        $is_wechat = (is_wechat_browser() && file_exists(MOBILE_WECHAT)) ? 1 : 0;
        $this->plugin_assign('is_wechat', $is_wechat);
        // 微信JSSDK分享
        $share_data = [
            'title' => $config['media']['title'], //分享标题
            'desc' => $config['media']['digest'], //分享描述
            'link' => html_out($config['media']['link']), //分享链接
            'img' => $this->wechatHelperService->get_wechat_image_path($config['media']['file']), //分享图片
        ];
        $this->plugin_assign('share_data', $share_data);
        return $this->show_display('index', $this->_data);
    }

    /**
     * 行为操作
     */
    public function executeAction()
    {
        // 插件配置
        $config = $this->get_plugin_config($this->wechat_id, $this->plugin_name);
        // 信息提交
        $operate = request()->input('operate', '');
        if (request()->isMethod('POST') && $operate == 'address') {
            $id = request()->input('id', 0);
            $data = request()->input('data');
            if (empty($id)) {
                return response()->json(['error' => 1, 'msg' => lang('wechat.please_prize')]);
            }
            if (empty($data['phone'])) {
                return response()->json(['error' => 1, 'msg' => lang('wechat.please_phone')]);
            }
            if (empty($data['address'])) {
                return response()->json(['error' => 1, 'msg' => lang('wechat.please_address')]);
            }
            $winner['winner'] = empty($data) ? '' : serialize($data);

            WechatPrize::where(['id' => $id, 'wechat_id' => $this->wechat_id])->update($winner);

            return response()->json(['error' => 0, 'msg' => lang('wechat.success_please_wait'), 'url' => route('wechat/plugin_show', ['name' => $this->plugin_name, 'wechat_ru_id' => $this->wechat_ru_id])]);
        }

        // 获奖用户资料填写页面
        $id = request()->input('id', 0);
        if (!empty($id) && !request()->isMethod('POST')) {
            $openid = $this->get_openid($this->wechat_ru_id);

            if (empty($openid)) {
                return $this->show_message(lang('wechat.please_login'));
            }

            $prize = WechatPrize::select('winner', 'issue_status')
                ->where(['openid' => $openid, 'id' => $id, 'wechat_id' => $this->wechat_id, 'prize_type' => 1, 'activity_type' => $this->plugin_name])
                ->first();
            $prize = $prize ? $prize->toArray() : [];

            $winner_result = [];
            if ($prize && $prize['issue_status'] != 1) {
                if (!empty($prize['winner'])) {
                    $winner_result = unserialize($prize['winner']);
                } else {
                    // 查询上一次中奖记录 联系地址
                    $rs1 = WechatPrize::where('wechat_id', $this->wechat_id)
                        ->where('openid', $openid)
                        ->where('activity_type', $this->plugin_name)
                        ->where('prize_type', 1)
                        ->where('id', '<', $id)
                        ->orderBy('dateline', 'DESC')
                        ->first();
                    $rs1 = $rs1 ? $rs1->toArray() : [];

                    if ($rs1) {
                        $winner_result = empty($rs1['winner']) ? '' : unserialize($rs1['winner']);
                    }
                }
            }

            $this->plugin_assign('winner_result', $winner_result);
            $this->plugin_assign('plugin_name', $this->plugin_name);
            $this->plugin_assign('id', $id);
            $this->plugin_assign('wechat_ru_id', $this->wechat_ru_id);
            return $this->show_display('user_info', $this->_data);
        }

        // 抽奖操作
        $act = request()->input('act', '');
        if (request()->isMethod('POST') && $act == 'play') {
            // 未登录
            $openid = $this->get_openid($this->wechat_ru_id);
            if (empty($openid)) {
                return response()->json(['status' => 2, 'msg' => lang('wechat.please_login')]);
            }

            // 活动过期
            $starttime = $this->timeRepository->getLocalStrtoTime($config['starttime']);
            $endtime = $this->timeRepository->getLocalStrtoTime($config['endtime']);

            $nowtime = $this->timeRepository->getGmTime();
            if ($nowtime < $starttime) {
                return response()->json(['status' => 2, 'msg' => lang('wechat.activity_no_start')]);
            }
            if ($nowtime > $endtime) {
                return response()->json(['status' => 2, 'msg' => lang('wechat.activity_is_end')]);
            }
            // 超过次数
            if ($this->wechat_ru_id > 0) {
                // 商家 抽奖次数判断 起止时间
                $num = WechatPrize::where('wechat_id', $this->wechat_id)
                    ->where('openid', $openid)
                    ->where('activity_type', $this->plugin_name)
                    ->whereBetween('dateline', [$starttime, $endtime])
                    ->count();

                if ($num <= 0) {
                    $num = 1;
                } else {
                    $num = $num + 1;
                }
            } else {
                if ($config['point_status'] == 1) {
                    $users = $this->wechatUserService->get_wechat_user_id($openid);
                    $user_pay_points = Users::where(['user_id' => $users['user_id']])->value('pay_points');
                    $num = floor($user_pay_points / $config['point_value']) < 0 ? 0 : floor($user_pay_points / $config['point_value']);
                } else {
                    // 平台判断 起止时间内可抽奖次数
                    $num = WechatPrize::where('wechat_id', $this->wechat_id)
                        ->where('openid', $openid)
                        ->where('activity_type', $this->plugin_name)
                        ->where('dateline', '>', $nowtime - $config['point_interval'])
                        ->count();

                    if ($num <= 0) {
                        $num = 1;
                    } else {
                        $num = $num + 1;
                    }
                }
            }

            if ($config['point_status'] == 1) {
                // 积分扣除
                $res = $this->wechatPointService->takeout_point($openid, $config['extend'], $config['point_value']);
                if ($res === false) {
                    return response()->json(['status' => 2, 'msg' => lang('wechat.no_points')]);
                }
            } else {
                if ($num > $config['prize_num']) {
                    return response()->json(['status' => 2, 'num' => 0, 'msg' => lang('wechat.no_prize_times')]);
                }
            }

            $rs = [];
            $prize = $config['prize'];
            if (!empty($prize)) {
                $prize_prob = []; // 奖品概率
                $prize_name = [];

                $total_prob = 0; // 中奖概率总和
                foreach ($prize as $key => $val) {
                    // 删除数量不足的奖品
                    $count = WechatPrize::where(['wechat_id' => $this->wechat_id, 'prize_name' => $val['prize_name'], 'activity_type' => $this->plugin_name])->count();
                    if ($val['prize_prob'] <= 0 || $count >= $val['prize_count']) {
                        unset($prize[$key]);
                    } else {
                        $prize_prob[$val['prize_level']] = $val['prize_prob'];
                        $prize_name[$val['prize_level']] = $val['prize_name'];
                    }
                    //添加项的总概率
                    $total_prob = $total_prob + $val['prize_prob'];
                }
                // 总概率 不能为负且要小于等于100
                if ($total_prob <= 0 || $total_prob > 100) {
                    exit();
                }
                // 提示奖品数量不足
                if (empty($prize)) {
                    return response()->json(['status' => 2, 'msg' => lang('wechat.unenough_prize_num')]);
                }
                // 最后一个奖项
                $last_prize = end($prize);
                // 获取中奖项
                $level = $this->wechatPointService->get_rand($prize_prob);
                if ($level) {
                    // 0为未中奖,1为中奖
                    if ($level == $last_prize['prize_level']) {
                        $rs['status'] = 0;
                        $data['prize_type'] = 0;
                    } else {
                        $rs['status'] = 1;
                        $data['prize_type'] = 1;
                    }
                    $rs['msg'] = $prize_name[$level];
                }

                if ($config['point_status'] == 1) {
                    $rs['num'] = $num - 1;//
                } else {
                    $rs['num'] = $config['prize_num'] - $num > 0 ? $config['prize_num'] - $num : 0;
                }

                // 抽奖记录
                $data['wechat_id'] = $this->wechat_id;
                $data['openid'] = $openid;
                $data['prize_name'] = $prize_name[$level];
                $data['dateline'] = $nowtime;
                $data['activity_type'] = $this->plugin_name;
                $id = WechatPrize::insertGetId($data);

                //参与人数增加
                $extend_cfg = WechatExtend::where(['wechat_id' => $this->wechat_id, 'command' => $this->plugin_name, 'enable' => 1])
                    ->value('config');

                $cfg_new = empty($extend_cfg) ? [] : unserialize($extend_cfg);
                $cfg_new['people_num'] = isset($cfg_new['people_num']) ? $cfg_new['people_num'] + 1 : 1;
                $cfg['config'] = serialize($cfg_new);
                WechatExtend::where(['wechat_id' => $this->wechat_id, 'command' => $this->plugin_name, 'enable' => 1])->update($cfg);

                if ($level != $last_prize['prize_level'] && !empty($id)) {
                    // 获奖链接
                    $rs['link'] = route('wechat/plugin_action', ['name' => $this->plugin_name, 'id' => $id, 'wechat_ru_id' => $this->wechat_ru_id]);
                    //$rs['link'] = str_replace('&amp;', '&', $rs['link']);
                }
            }

            return response()->json($rs);
        }

        // 我的中奖记录
        if ($act == 'list') {

            $openid = $this->get_openid($this->wechat_ru_id);

            if (empty($openid)) {
                return $this->show_message(lang('wechat.please_login'));
            }

            $page = request()->input('page', 1);
            $size = request()->input('size', 10);

            // 分页
            $offset = [
                'start' => ($page - 1) * $size,
                'limit' => $size,
                'path' => request()->url() . '?act=list&name=' . $this->plugin_name
            ];

            // 活动起止时间
            $condition = [
                'starttime' => $this->timeRepository->getLocalStrtoTime($config['starttime']),
                'endtime' => $this->timeRepository->getLocalStrtoTime($config['endtime']),
            ];
            $list = $this->wechatPluginService->userPrizeList($this->wechat_id, $openid, $this->plugin_name, $offset, $condition);

            $this->plugin_assign('list', $list);
            $this->plugin_assign('plugin_name', $this->plugin_name);
            $this->plugin_assign('wechat_ru_id', $this->wechat_ru_id);
            return $this->show_display('user_prize_list', $this->_data);
        }
    }
}
