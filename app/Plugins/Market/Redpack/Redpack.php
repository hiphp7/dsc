<?php

namespace App\Plugins\Market\Redpack;

use App\Http\Controllers\Wechat\PluginController;
use App\Libraries\Wechat;
use App\Models\WechatMarketing;
use App\Models\WechatRedpackAdvertice;
use App\Models\WechatRedpackLog;
use App\Models\WechatUser;

/**
 * 微信现金红包前台模块
 * Class Redpack
 * @package App\Plugins\Market\Redpack
 */
class Redpack extends PluginController
{
    private $weObj = null;
    private $wechat_id = 0;
    private $wechat_ru_id = 0;
    private $market_id = 0;
    private $marketing_type = 'redpack';

    private $plugin_themes = '';

    protected $config = [];

    private $parameters; // cft 参数

    /**
     * 构造函数
     */
    public function __construct($config = [])
    {
        parent::__construct();

        $this->plugin_name = $this->marketing_type = strtolower(basename(__FILE__, '.php'));
        $this->config = $config;
        $this->wechat_ru_id = isset($this->config['wechat_ru_id']) ? $this->config['wechat_ru_id'] : 0;

        $this->wechat_id = $this->wechatService->getWechatId($this->wechat_ru_id);
        $this->config['plugin_path'] = 'Market';

        $this->market_id = request()->get('market_id', 0);

        $this->plugin_themes = asset('assets/wechat/redpack/');

        //活动配置
        $data = WechatMarketing::select('name', 'starttime', 'endtime', 'config')->where(['id' => $this->market_id, 'marketing_type' => 'redpack', 'wechat_id' => $this->wechat_id])
            ->first();
        $data = $data ? $data->toArray() : [];

        if ($data) {
            $this->config['config'] = unserialize($data['config']);
            $this->config['config']['act_name'] = $data['name'];
            $this->config['starttime'] = $data['starttime'];
            $this->config['endtime'] = $data['endtime'];
        }

        $this->plugin_assign('config', $this->config);
    }

    /**
     * 网页摇一摇入口活动页面
     */
    public function actionActivity()
    {
        // 页面显示
        $info = WechatMarketing::select('id', 'name', 'logo', 'background', 'description', 'support')->where(['id' => $this->market_id, 'marketing_type' => 'redpack', 'wechat_id' => $this->wechat_id])
            ->first();
        $info = $info ? $info->toArray() : [];

        $flag = '';
        if (!empty($info)) {
            $info['background'] = $this->wechatHelperService->get_wechat_image_path($info['background']); // 背景图片
            if (strpos($info['background'], 'no_image') !== false) {
                unset($info['background']);
            }

            $status = $this->wechatService->get_status($this->config['starttime'], $this->config['endtime']); // 活动状态 0未开始,1正在进行,2已结束

            if ($status == 0) {
                $flag = lang('wechat.activity_no_start'); // 未开始
            }
            if ($status == 2) {
                $flag = lang('wechat.activity_is_end'); // 已结束
            }
        }

        $openid = $this->get_openid($this->wechat_ru_id);
        $is_subscribe = WechatUser::where(['openid' => $openid, 'wechat_id' => $this->wechat_id])->value('subscribe');
        if ($is_subscribe == 0) {
            $flag = lang('wechat.please_subscribe_wechat');
        }

        $this->plugin_assign('flag', $flag);
        $this->plugin_assign('is_subscribe', $is_subscribe);

        $shake_url = route('wechat/market_show', ['type' => 'redpack', 'function' => 'shake', 'market_id' => $this->market_id]); // 摇一摇页面地址
        $this->plugin_assign('shake_url', $shake_url);

        // 分享
        $page_title = isset($info['name']) ? $info['name'] : '';
        $description = isset($info['description']) ? $info['description'] : '';
        $page_img = isset($info['background']) ? $this->wechatHelperService->get_wechat_image_path($info['background']) : '';

        $is_wechat = (is_wechat_browser() && file_exists(MOBILE_WECHAT)) ? 1 : 0;
        $this->plugin_assign('is_wechat', $is_wechat);
        // 微信JSSDK分享
        $share_data = [
            'title' => $page_title, //分享标题
            'desc' => $description, //分享描述
            'link' => $shake_url, //分享链接
            'img' => $page_img, //分享图片
        ];
        $this->plugin_assign('share_data', $share_data);

        $this->plugin_assign('info', $info);
        $this->plugin_assign('page_title', $page_title);
        $this->plugin_assign('description', $description);
        return $this->show_display('activity', $this->_data);
    }

    /**
     * 微信摇一摇领取红包页面
     */
    public function actionShake()
    {
        if (request()->isMethod('post')) {
            $time = request()->get('time');
            $last = request()->get('last');

            $openid = $this->get_openid($this->wechat_ru_id);
            if (empty($openid) || $openid == '' || $openid == null) {
                $result = [
                    'icon' => $this->plugin_themes . "/images/error.png",
                    'content' => lang('wechat.please_subscribe_wechat'),
                    'url' => ''
                ];
                return response()->json($result);
            }

            $cha = $time - $last; // 计算与上一次摇一摇的时间差(单位ms 毫秒),需要间隔4000ms以上
            if ($cha < 4000) {
                $result = [
                    'status' => 0,
                    'icon' => $this->plugin_themes . "/images/icon.jpg",
                    'content' => lang('wechat.please_wait_4s'),
                    'url' => ''
                ];
                return response()->json($result);
            }
            // 计算随机数最小值和最大值之间的值，当产生的随机数与此处填的一个值相符即发放红包
            $min = $this->config['config']['randmin'];
            $max = $this->config['config']['randmax'];
            $sendNum = $this->config['config']['sendnum'];
            $sendArr = explode(',', $sendNum);
            $rand = rand($min, $max);
            $isInclude = in_array($rand, $sendArr);

            $hb_type = $this->config['config']['hb_type'];

            if ($isInclude) {
                $status = $this->wechatService->get_status($this->config['starttime'], $this->config['endtime']); // 活动状态 0未开始,1正在进行,2已结束
                if ($status == 0) {
                    // 未开始
                    $result = [
                        'status' => 0,
                        'icon' => $this->plugin_themes . "/images/icon.jpg",
                        'content' => lang('wechat.activity_no_start'),
                        'url' => ''
                    ];
                    return response()->json($result);
                } elseif ($status == 2) {
                    // 已结束
                    $result = [
                        'status' => 0,
                        'icon' => $this->plugin_themes . "/images/icon.jpg",
                        'content' => lang('wechat.activity_is_end'),
                        'url' => ''
                    ];
                    return response()->json($result);
                } else {
                    $temp = $this->sendRedpack($openid, $hb_type);
                    $result = [
                        'status' => 1,
                        'icon' => $this->plugin_themes . "/images/icon.jpg",
                        'content' => $temp,
                        'url' => ''
                    ];
                    return response()->json($result);
                }
            } else {
                // 当用户没有摇到红包时展示广告内容
                $total = WechatRedpackAdvertice::where(['wechat_id' => $this->wechat_id, 'market_id' => $this->market_id])->count();
                if ($total == 0) {
                    $result = [
                        'status' => 0,
                        'icon' => $this->plugin_themes . "/images/icon.jpg",
                        'content' => lang('wechat.nothing'),
                        'url' => ''
                    ];
                    return response()->json($result);
                }
                // 随机一张广告
                $pageindex = rand(0, $total - 1);
                $temp = WechatRedpackAdvertice::select('icon', 'content', 'url')->where(['wechat_id' => $this->wechat_id, 'market_id' => $this->market_id])
                    ->offset($pageindex)
                    ->limit(1)
                    ->get();
                $temp = $temp ? $temp->toArray() : [];

                $temp = reset($temp);
                $temp['icon'] = $this->wechatHelperService->get_wechat_image_path($temp['icon']);

                $result = [
                    'status' => 0,
                    'icon' => $temp['icon'],
                    'content' => $temp['content'],
                    'url' => $temp['url']
                ];
                return response()->json($result);
            }
        }

        $this->plugin_assign('back_url', route('wechat/market_show', ['type' => 'redpack', 'function' => 'activity', 'market_id' => $this->market_id]));
        $this->plugin_assign('market_id', $this->market_id);
        $this->plugin_assign('page_title', lang('wechat.redpack_title'));
        return $this->show_display('shake', $this->_data);
    }

    /**
     * 发送红包
     * @param string $param_openid 用户openid
     * @param int $hb_type 发送红包类型 0 普通、1 裂变
     *
     */
    public function sendRedpack($param_openid = '', $hb_type = 0)
    {
        // 随机计算发放红包
        // $randmin = $this->config['config']['randmin'];
        // $randmax = $this->config['config']['randmax'];
        // $sendnum = $this->config['config']['sendnum'];
        $total_num = $this->config['config']['total_num'];

        // $sendArr = explode(',', $sendnum);
        // $rand = rand($randmin, $randmax);
        // $isInclude = in_array($rand, $sendArr);
        load_helper('payment');
        $payment = get_payment('wxpay');
        if (!empty($payment)) {
            $isInclude = true;
        } else {
            $isInclude = false;
        }

        if ($isInclude) {
            // 调用红包类
            $options = [
                'appid' => $payment['wxpay_appid'],
                'mch_id' => $payment['wxpay_mchid'],
                'key' => $payment['wxpay_key']
            ];
            $WxHongbao = new Wechat($options); //new WxHongbao($configure);
            // 证书
            $sslcert = storage_path('app/certs/wxpay/') . md5($options['mch_id']) . "_apiclient_cert.pem";
            $sslkey = storage_path('app/certs/wxpay/') . md5($options['mch_id']) . "_apiclient_key.pem";

            // 设置参数
            $mch_billno = $payment['wxpay_mchid'] . date('YmdHis') . rand(1000, 9999);
            // 红包金额
            $money = $this->config['config']['base_money'] + rand(0, $this->config['config']['money_extra']);
            $money = $money * 100; // 转换为分
            if ($hb_type == 0) {
                $total_num = 1;
            } else {
                $total_num = $total_num > 3 ? $total_num : 3; // 裂变红包发放总人数，最小3人
            }

            $nick_name = $this->config['config']['nick_name'];
            $send_name = $this->config['config']['send_name'];
            $wishing = $this->config['config']['wishing'];
            $act_name = $this->config['config']['act_name'];  //活动名称
            $remark = $this->config['config']['remark'];
            // 场景ID
            $scene_id = strtoupper($this->config['config']['scene_id']);

            if ($hb_type == 0) {
                // 普通红包参数
                $this->setParameter("mch_billno", $mch_billno); // 商户订单号（每个订单号必须唯一）组成：mch_id+yyyymmdd+10位一天内不能重复的数字。
                $this->setParameter("nick_name", $nick_name); //提供方名称
                $this->setParameter("send_name", $send_name); //红包发送者名称,商户名称
                $this->setParameter("re_openid", $param_openid); // 接受红包的用户,用户在wxappid下的openid
                $this->setParameter("total_amount", $money); // 付款金额，单位分
                $this->setParameter("min_value", $money); // 最小红包金额，单位分
                $this->setParameter("max_value", $money); // 最大红包金额，单位分  发放金额、最小金额、最大金额必须相等
                $this->setParameter("total_num", $total_num); // 红包发放总人数 1
                $this->setParameter("wishing", $wishing); // 红包祝福语
                $this->setParameter("client_ip", request()->getClientIp());
                $this->setParameter("act_name", $act_name); // 活动名称
                $this->setParameter("remark", $remark); // 备注信息
            } elseif ($hb_type == 1) {
                // 裂变红包参数
                $this->setParameter("mch_billno", $mch_billno); // 商户订单号（每个订单号必须唯一）组成：mch_id+yyyymmdd+10位一天内不能重复的数字。
                $this->setParameter("nick_name", $nick_name); //提供方名称
                $this->setParameter("send_name", $send_name); //红包发送者名称,商户名称
                $this->setParameter("re_openid", $param_openid); // 接受红包的用户,用户在wxappid下的openid
                $this->setParameter("total_amount", $money); // 付款金额，单位分 最少300
                // $this->setParameter("min_value", $money); // 最小红包金额，单位分
                // $this->setParameter("max_value", $money); // 最大红包金额，单位分  发放金额、最小金额、最大金额必须相等
                $this->setParameter("total_num", $total_num); // 红包发放总人数，最小3人
                $this->setParameter("amt_type", 'ALL_RAND'); // 红包金额设置方式
                $this->setParameter("wishing", $wishing); // 红包祝福语
                $this->setParameter("act_name", $act_name); // 活动名称
                $this->setParameter("remark", $remark); // 备注信息
            }
            // 发放红包使用场景，红包金额大于200时必传
            if ($scene_id && $scene_id > 0) {
                $this->setParameter("scene_id", $scene_id);
            }
            $hb_type = $hb_type == 1 ? 'GROUP' : 'NORMAL';
            $respond = $WxHongbao->CreatSendRedpack($this->parameters, $hb_type, $sslcert, $sslkey);
            // logResult($respond);

            if ($respond['return_code'] == 'SUCCESS' && $respond['result_code'] == 'SUCCESS') {
                // 发送成功
                $where = [
                    'wechat_id' => $this->wechat_id,
                    'market_id' => $this->market_id,
                    'openid' => $param_openid,
                    'hassub' => 0,
                ];
                $count = WechatRedpackLog::where($where)->count();
                if ($count == 0 || $count < 10) {
                    $total_amount = $respond['total_amount'] * 1.0 / 100;
                    $re_openid = $respond['re_openid'];
                    $mch_billno = $respond['mch_billno'];
                    $mch_id = $respond['mch_id'];
                    $wxappid = $respond['wxappid'];

                    // 返回成功 更新发送红包记录
                    $data = [
                        'wechat_id' => $this->wechat_id,
                        'market_id' => $this->market_id,
                        'hb_type' => $hb_type,
                        'openid' => $re_openid,
                        'hassub' => 1,
                        'money' => $total_amount,
                        'time' => $this->timeRepository->getGmTime(),
                        'mch_billno' => $mch_billno,
                        'mch_id' => $mch_id,
                        'wxappid' => $wxappid,
                        'bill_type' => 'MCHT',
                        'notify_data' => serialize($respond),
                    ];
                    WechatRedpackLog::where($where)->updateOrCreate($data);

                    if ($hb_type == 1) {
                        return lang('wechat.congratulations_0');
                    }
                    return lang('wechat.congratulations_1', ['total_amount' => $total_amount]);
                }

                return lang('wechat.redpack_limit');
            } else {
                return $WxHongbao->errCode . ':' . $WxHongbao->errMsg;
            }
        } else {
            return lang('wechat.please_install_wxpay');
        }
    }


    /**
     * 作用：设置请求参数
     */
    protected function setParameter($parameter, $parameterValue)
    {
        $this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
    }

    protected function trimString($value)
    {
        $ret = null;
        if (null != $value) {
            $ret = $value;
            if (strlen($ret) == 0) {
                $ret = null;
            }
        }
        return $ret;
    }
}
