<?php

namespace App\Http\Controllers\Wechat;

use App\Http\Controllers\Controller;
use App\Models\WechatShareCount;
use App\Repositories\Common\TimeRepository;
use App\Services\Wechat\WechatService;
use App\Services\Wechat\WechatUserService;

/**
 * Class ApiController
 * @package App\Http\Controllers\Wechat
 */
class ApiController extends Controller
{
    protected $wechatService;
    protected $timeRepository;
    protected $wechatUserService;

    /**
     * ApiController constructor.
     * @param WechatService $wechatService
     * @param TimeRepository $timeRepository
     * @param WechatUserService $wechatUserService
     */
    public function __construct(
        WechatService $wechatService,
        TimeRepository $timeRepository,
        WechatUserService $wechatUserService
    )
    {
        $this->wechatService = $wechatService;
        $this->timeRepository = $timeRepository;
        $this->wechatUserService = $wechatUserService;
    }

    /**
     * PC后台发送发货通知模板消息接口方法
     */
    public function index()
    {
        $user_id = request()->input('user_id', 0);
        $code = request()->input('code', '');
        $pushData = request()->input('pushData', '');
        $url = request()->input('url', '');
        $url = $url ? base64_decode(urldecode($url)) : '';

        $ru_id = request()->input('ru_id', 0);

        if ($user_id && $code) {
            $pushData = stripslashes(urldecode($pushData));
            //转换成数组
            $pushData = unserialize($pushData);
            // 发送微信通模板消息
            $this->wechatService->push_template($code, $pushData, $url, $user_id, $ru_id);
        }
    }

    /**
     * JSSDK
     *
     * @return array
     */
    public function jssdk()
    {
        $url = request()->input('url', '');
        if (!empty($url)) {
            $ru_id = request()->input('ru_id', 0);

            $data = $this->wechatService->getJssdk($ru_id, $url);

            return $data;
        } else {
            return ['status' => '100', 'message' => '缺少参数'];
        }
    }

    /**
     * 分享统计
     *
     * @return
     */
    public function share_count()
    {
        $jsApiname = request()->input('jsApiname', '');
        $link = request()->input('link', '');

        $uid = request()->input('uid', 0);
        $ru_id = request()->input('ru_id', 0);

        $share_type = 0;
        switch ($jsApiname) {
            case 'shareTimeline':
                $share_type = 1;//对应分享到朋友圈接口 onMenuShareTimeline
                break;
            case 'sendAppMessage':
                $share_type = 2;// 对应分享给朋友接口 onMenuShareAppMessage
                break;
            case 'shareQQ':
                $share_type = 3;// 对应分享到QQ接口 onMenuShareQQ
                break;
            case 'shareQZone':
                $share_type = 4;// 对应分享到QQ空间接口 onMenuShareQZone
                break;
            default:
                break;
        }

        $time = $this->timeRepository->getGmTime();
        $openid = $this->wechatUserService->get_openid($uid);
        if (!empty($share_type) && !empty($openid)) {
            $wechat = $this->wechatService->getWechatConfigByRuId($ru_id);
            if ($wechat) {
                $data = [
                    'wechat_id' => $wechat['id'],
                    'openid' => $openid,
                    'share_type' => $share_type,
                    'link' => $link,
                    'share_time' => $time
                ];
                WechatShareCount::create($data);
                $result = ['status' => '200', 'msg' => '统计成功'];
            }
        } else {
            $result = ['status' => 'fail', 'msg' => '统计失败'];
        }
        return response()->json($result);
    }
}
