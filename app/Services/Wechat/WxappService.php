<?php

namespace App\Services\Wechat;

use App\Libraries\Wxapp;
use App\Models\OrderInfo;
use App\Models\WechatUser;
use App\Models\WxappConfig;
use App\Models\WxappTemplate;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Team\TeamService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Class WxappService
 * @package App\Services\Wechat
 */
class WxappService
{
    /**
     * @var WxappConfig
     */
    private $wxappConfig;
    protected $config;
    protected $timeRepository;
    protected $teamService;
    protected $dscRepository;

    public function __construct(
        WxappConfig $wxappConfig,
        TimeRepository $timeRepository,
        TeamService $teamService,
        DscRepository $dscRepository
    )
    {
        $this->wxappConfig = $wxappConfig;
        $this->timeRepository = $timeRepository;
        $this->teamService = $teamService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 小程序配置(仅支持平台小程序)
     * @param null $code
     * @return array|mixed|null
     */
    public function wxappConfig($code = null)
    {
        $config = Cache::get('wx_app_config');

        if (empty($config)) {
            $config = WxappConfig::orderBy('id', 'asc')->first();

            $config = is_null($config) ? [] : $config->toArray();

            Cache::put('wx_app_config', $config, Carbon::now()->addHours(1));
        }

        if (is_null($code)) {
            return $config;
        }

        return $config[$code] ?? null;
    }

    /**
     * 根据code获取模板配置
     * @param $code
     * @return mixed
     */
    public function templateInfo($code)
    {
        $wxapp_template = WxappTemplate::where('wx_code', $code)->first();

        return is_null($wxapp_template) ? [] : $wxapp_template->toArray();
    }

    /**
     * 小程序贡云确认订单&消息推送
     * @param $order_sn
     * @param $attach
     * @return
     */
    public function messageNotify($order_sn = '', $attach = '')
    {
        $order = OrderInfo::where('order_sn', $order_sn)
            ->first();

        if ($order == null) {
            return [];
        }

        $form_id = $attach['form_id'] ?? '';

        //贡云确认订单
        //$this->flowService->cloudConfirmOrder($order['order_id']);

        //修改拼团状态 sty
        if ($order['extension_code'] == 'team_buy' && $order['team_id'] > 0) {
            //拼团信息
            $team_info = $this->teamService->teamIsFailure($order['team_id']);
            //统计参团人数
            $team_count = $this->teamService->surplusNum($order['team_id']);
            if ($team_count >= $team_info['team_num']) { //拼团成功
                //更改拼团状态
                $this->teamService->updateTeamLogStatua($order['team_id']);
            }
            //统计拼团人数
            $limit_num = $team_info['limit_num'] + 1;
            //更改拼团参团数量
            $this->teamService->updateTeamLimitNum($team_info['id'], $team_info['goods_id'], $limit_num);

            $end_time = $team_info['start_time'] + ($team_info['validity_time'] * 3600);//剩余时间

            if ($order['team_parent_id'] > 0) {//开团成功提醒
                $pushData = [
                    'keyword1' => ['value' => $team_info['goods_name'], 'color' => '#000000'],
                    'keyword2' => ['value' => $team_info['team_num'], 'color' => '#000000'],
                    'keyword3' => ['value' => $this->timeRepository->getLocalDate($this->config['time_format'], $end_time), 'color' => '#000000'],
                    'keyword4' => ['value' => $this->dscRepository->getPriceFormat($team_info['team_price'], true), 'color' => '#000000']
                ];
                $url = 'pages/group/wait?objectId=' . $order['team_id'] . '&user_id=' . $order['user_id'];

                $this->wxappPushTemplate('AT0541', $pushData, $url, $order['user_id'], $form_id);
            } else {//参团成功通知
                $pushData = [
                    'keyword1' => ['value' => $team_info['goods_name'], 'color' => '#000000'],
                    'keyword2' => ['value' => $this->dscRepository->getPriceFormat($team_info['team_price'], true), 'color' => '#000000'],
                    'keyword3' => ['value' => $this->timeRepository->getLocalDate($this->config['time_format'], $end_time), 'color' => '#000000']
                ];
                $url = 'pages/group/wait?objectId=' . $order['team_id'] . '&user_id=' . $order['user_id'];

                $this->wxappPushTemplate('AT0933', $pushData, $url, $order['user_id'], $form_id);
            }
        }

    }

    /**
     * 发送小程序模板消息
     * @param string $code 模板标识
     * @param array $content 发送模板消息内容
     * @param string $url 消息链接
     * @param int $uid 发送人user_id
     * @param string $form_id 表单提交场景下，为 submit 事件带上的 formId；支付场景下，为本次支付的 prepay_id
     * @return bool
     */
    public function wxappPushTemplate($code = '', $content = [], $url = '', $uid = 0, $form_id = '')
    {
        $wxappConfig = $this->wxappConfig();

        $config = [
            'appid' => $wxappConfig['wx_appid'],
            'secret' => $wxappConfig['wx_appsecret'],
        ];

        $wxapp = new Wxapp($config);

        $template = $this->templateInfo($code);
        if (!empty($template) && $template['status'] == 1) {
            $user = $this->getUserOpenid($uid);
            if ($user && !empty($user['openid'])) {
                $data['touser'] = $user['openid'];
                $data['template_id'] = $template['wx_template_id'];
                $data['page'] = $url;
                $data['form_id'] = $form_id;
                $data['data'] = $content;
                $data['color'] = '#FF0000';
                $data['emphasis_keyword'] = '';
                $result = $wxapp->sendTemplateMessage($data);
                if (empty($result)) {
                    // $wxapp->errCode . $wxapp->errMsg;
                    return false;
                } else {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 获取用户Openid
     * @param $uid
     * @return mixed
     */
    public function getUserOpenid($uid = '')
    {
        $list = WechatUser::from('wechat_user as wu')
            ->select('wu.openid')
            ->leftjoin('connect_user as cu', 'cu.open_id', '=', 'wu.unionid')
            ->where('cu.user_id', $uid)
            ->first();

        if ($list === null) {
            return [];
        }

        return $list->toArray();
    }
}
