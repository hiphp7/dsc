<?php

namespace App\Plugins\Wechat\Sign;

use App\Http\Controllers\Wechat\PluginController;
use App\Models\AccountLog;
use App\Models\Coupons;
use App\Models\WechatPoint;
use Illuminate\Support\Str;

/**
 * 签到送积分
 *
 * @author wanglu
 *
 */
class Sign extends PluginController
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
     * @param  $cfg
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
        // 显示免邮优惠券列表 未过期的
        $coupons_list = Coupons::where('cou_type', 5)
            ->where('cou_end_time', '>', $this->timeRepository->getGmTime())
            ->get();

        $coupons_list = $coupons_list ? $coupons_list->toArray() : [];

        if (!empty($coupons_list)) {
            foreach ($coupons_list as $k => $value) {
                $coupons_list[$k]['cou_man_format'] = empty($value['cou_man']) ? lang('admin/wechat.cou_man_0') : lang('admin/wechat.cou_man_1', ['cou_man' => $value['cou_man']]);
            }
        }

        $this->cfg['coupons_list'] = $coupons_list;
        $this->plugin_assign('config', $this->cfg);

        return $this->plugin_display('install', $this->_data);
    }

    /**
     * 获取数据
     */
    public function returnData($fromusername = '', $info = [])
    {
        $articles = ['type' => 'text', 'content' => lang('wechat.sign_fail')];

        // 插件配置
        $config = $this->get_plugin_config($this->wechat_id, $this->plugin_name);
        if (isset($config['point_status']) && $config['point_status'] == 1) {
            $users = $this->wechatUserService->get_wechat_user_id($fromusername);
            if (empty($users) || empty($users['mobile_phone'])) {
                $articles = ['type' => 'text', 'content' => lang('wechat.sign_mobile_phone_empty')];
                return $articles;
            }

            if ($users) {
                // 数据
                $articles = [];
                $articles['type'] = 'news';
                $articles['content'][0]['Title'] = $config['media']['title'];
                $articles['content'][0]['Description'] = empty($config['media']['digest']) ? Str::limit($config['media']['content'], 100) : $config['media']['digest'];
                $articles['content'][0]['PicUrl'] = $this->wechatHelperService->get_wechat_image_path($config['media']['file']);
                $articles['content'][0]['Url'] = html_out($config['media']['link']);
            }
        } else {
            $articles['content'] = lang('wechat.sign_empty');
        }
        return $articles;
    }

    /**
     * 积分赠送
     *
     * @param string $fromusername
     * @param array $info
     */
    protected function updatePoint($fromusername = '', $info = [], $rank_point_value = 0, $pay_point_value = 0)
    {
        return $this->wechatPointService->do_point($fromusername, $info, $rank_point_value, $pay_point_value);
    }

    /**
     * 页面显示
     */
    public function html_show()
    {
        // 插件配置
        $config = $this->get_plugin_config($this->wechat_id, $this->plugin_name);

        // 当前用户 当月签到记录
        $openid = $this->get_openid($this->wechat_ru_id);

        $result = $this->wechatPointListFormat($openid);

        $myday_str = $result['myday_str'] ?? '';
        $sign_day = $result['sign_day'] ?? 0;
        $continue_day = $result['continue_day'] ?? 0;

        // 今天 1 已签到, 0 未签到
        $can_sign = $this->wechatPointService->todayPointTimes($openid, $this->plugin_name);
        $can_sign = $can_sign == true ? 0 : 1;

        $this->plugin_assign('myday_str', $myday_str);
        $this->plugin_assign('sign_day', $sign_day);
        $this->plugin_assign('continue_day', $continue_day);
        $this->plugin_assign('can_sign', $can_sign);

        // 历史签到记录链接
        $config['sign_list_url'] = route('wechat/plugin_action', ['name' => $this->plugin_name, 'act' => 'list', 'wechat_ru_id' => $this->wechat_ru_id]);

        $this->plugin_assign('data', $config);

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
        $act = request()->input('act', '');
        // 签到记录
        if (request()->isMethod('GET') && $act == 'list') {

            $this->plugin_assign('wechat_ru_id', $this->wechat_ru_id);
            return $this->show_display('sign_list', $this->_data);
        }

        // 签到操作
        if (request()->isMethod('POST') && $act == 'do') {

            // 未登录
            $openid = $this->get_openid($this->wechat_ru_id);
            if (empty($openid)) {
                return response()->json(['status' => 2, 'msg' => lang('wechat.please_login')]);
            }

            // 今天 1 已签到, 0 未签到
            $can_sign = $this->wechatPointService->todayPointTimes($openid, $this->plugin_name);
            $can_sign = $can_sign == true ? 0 : 1;

            if ($can_sign == 0) {

                $config = $this->get_plugin_config($this->wechat_id, $this->plugin_name);

                if (empty($config)) {
                    return response()->json(['status' => 1, 'msg' => 'error']);
                }

                $info = [
                    'name' => $config['extend']['name'],
                    'command' => $config['extend']['command']
                ];
                if (!empty($config['rank_point_value']) || !empty($config['pay_point_value'])) {
                    // 积分赠送
                    $we_point_id = $this->updatePoint($openid, $info, $config['rank_point_value'], $config['pay_point_value']);
                    if ($we_point_id) {
                        $tips = !empty($config['rank_point_value']) ? lang('wechat.rank_points') . "+" . $config['rank_point_value'] : '';
                        $tips .= !empty($config['pay_point_value']) ? " " . lang('wechat.pay_points') . "+" . $config['pay_point_value'] : '';

                        // 当前用户 当月签到记录
                        $result = $this->wechatPointListFormat($openid);
                        $continue_day = $result['continue_day'] ?? 0; // 已连续签到天数

                        // 连续签到奖励
                        $config_continue_day = $config['continue_day'] ?? 0; // 设置的连续天数
                        $config_coupons_id = $config['coupons_id'] ?? 0; // 设置优惠券

                        // 设置的连续天数 等于 当前用户签到连续天数
                        if (!empty($config_continue_day) && !empty($continue_day) && $config_continue_day == $continue_day) {
                            // 奖励优惠券
                            $cou_uid = $this->wechatPointService->sendCoupons($openid, $config_coupons_id);
                            if ($cou_uid) {
                                $tips .= lang('wechat.free_mail_coupon') . '+1';

                                // 更新会员账户记录 备注
                                $change_desc = lang('wechat.sign_prize', ['config_continue_day' => $config_continue_day]) . lang('wechat.free_mail_coupon') . '+1';
                                $this->updateAccountLog($we_point_id, $change_desc);
                            }
                        }

                        $msg = lang('wechat.sign_success') . $tips;

                        return response()->json(['status' => 0, 'msg' => $msg]);
                    }
                }
            } else {
                $msg = lang('wechat.sign_exist');
                return response()->json(['status' => 1, 'msg' => $msg]);
            }
        }

        return response()->json(['status' => 1, 'msg' => 'error']);
    }

    /**
     * 已签到记录
     * @param string $openid
     * @param int $wechat_id
     * @return array
     */
    private function wechatPointListFormat($openid = '', $wechat_id = 0)
    {
        $list = $this->wechatPointService->wechatPointList($this->plugin_name, $wechat_id, $openid, 'M');

        $myday_str = ''; // 已签到
        $sign_day = 0; // 已签到天数
        $continue_day = 0; // 连续签到天数
        if (!empty($list['list'])) {
            // 已签到数组
            $myday = [];
            $myday_format = [];
            foreach ($list['list'] as $k => $v) {
                $myday[$k] = $v['createtime'];
                $myday_format[$k] = $this->timeRepository->getLocalDate('Y-m-d', $v['createtime']);
            }
            $myday_str = \GuzzleHttp\json_encode($myday);

            // 已签到天数
            $sign_day = count($myday);

            // 连续签到天数
            $continue_day = $this->wechatPointService->continue_day($myday_format);
        }

        return [
            'myday_str' => $myday_str,
            'sign_day' => $sign_day,
            'continue_day' => $continue_day,
        ];
    }

    /**
     * 更新会员账户记录 备注
     * @param $we_point_id
     * @param $change_desc
     * @return bool
     */
    public function updateAccountLog($we_point_id = 0, $change_desc = '')
    {
        if (empty($we_point_id)) {
            return false;
        }

        $model = WechatPoint::where('id', $we_point_id)
            ->whereHas('getAccountLog')
            ->first();

        if ($model) {
            if ($model->log_id > 0) {
                $account_log = AccountLog::where('log_id', $model->log_id)->first();

                $account_log->change_desc .= ',' . $change_desc;
                $account_log->save();

                return true;
            }
        }

        return false;
    }
}
