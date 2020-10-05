<?php

namespace App\Services\Wechat;

use App\Extensions\CustomCache;
use App\Models\ConnectUser;
use App\Models\DrpShop;
use App\Models\Users;
use App\Models\Wechat;
use App\Models\WechatCustomMessage;
use App\Models\WechatExtend;
use App\Models\WechatMassHistory;
use App\Models\WechatMessageLog;
use App\Models\WechatQrcode;
use App\Models\WechatTemplate;
use App\Models\WechatTemplateLog;
use App\Models\WechatUser;
use App\Models\WechatUserTag;
use App\Repositories\Common\TimeRepository;
use App\Services\User\ConnectUserService;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Messages\Text;
use Illuminate\Support\Str;
use Overtrue\LaravelWeChat\Facade;

class WechatService
{
    /**
     * @var TimeRepository
     */
    protected $timeRepository;

    /**
     * @var ConnectUserService
     */
    protected $connectUserService;

    /**
     * @var WechatUserService
     */
    protected $wechatUserService;

    /**
     * WechatService constructor.
     * @param TimeRepository $timeRepository
     * @param ConnectUserService $connectUserService
     * @param WechatUserService $wechatUserService
     */
    public function __construct(
        TimeRepository $timeRepository,
        ConnectUserService $connectUserService,
        WechatUserService $wechatUserService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->connectUserService = $connectUserService;
        $this->wechatUserService = $wechatUserService;
    }

    /**
     * 微信公众号信息（通过id查询）
     * @param int $wechat_id
     * @return array
     */
    public function getWechatConfigById($wechat_id = 0)
    {
        $res = Wechat::where('id', $wechat_id)->first();

        return $res ? $res->toArray() : [];
    }

    /**
     * 平台默认微信公众号信息
     * @return array
     */
    public function getWechatConfigDefault()
    {
        $res = Wechat::where('default_wx', 1)->where('ru_id', 0)->first();

        return $res ? $res->toArray() : [];
    }

    /**
     * 微信公众号信息 （通过secret_key查询）
     * @param string $secret_key
     * @return array
     */
    public function getWechatConfigByKey($secret_key = '')
    {
        $res = Wechat::where('secret_key', $secret_key)->first();

        return $res ? $res->toArray() : [];
    }

    /**
     * 微信公众号信息 （通过ru_id查询）
     * @param int $ru_id
     * @return array
     */
    public function getWechatConfigByRuId($ru_id = 0)
    {
        $res = Wechat::where('ru_id', $ru_id)->first();

        return $res ? $res->toArray() : [];
    }

    /**
     * 返回微信公众号id
     * @param int $ru_id
     * @return int|mixed
     */
    public function getWechatId($ru_id = 0)
    {
        $res = $this->getWechatConfigByRuId($ru_id);

        return $res['id'] ?? 0;
    }


    /**
     * 查询消息队列
     * @param int $wechat_id
     * @param string $keywords
     * @param array $message
     * @return array
     */
    public function wechatMessageLog($wechat_id = 0, $keywords = '', $message = [])
    {
        if (empty($wechat_id) || empty($message)) {
            return [];
        }

        if (isset($message['MsgType']) && $message['MsgType'] == 'event') {
            $where = [
                'fromusername' => $message['FromUserName'],
                'createtime' => $message['CreateTime'],
                'keywords' => $keywords,
                'is_send' => 0
            ];
        } else {
            $where = [
                'msgid' => $message['MsgId'],
                'keywords' => $keywords,
                'is_send' => 0
            ];
        }
        $contents = WechatMessageLog::where('wechat_id', $wechat_id)->where($where)->first();
        return $contents ? $contents->toArray() : [];
    }

    /**
     * 微信消息日志队列之存入数据库
     * @param integer $wechat_id
     * @param array $message
     * @return bool
     */
    public function messageLogAlignmentAdd($wechat_id = 0, $message = [])
    {
        if (empty($wechat_id) || empty($message)) {
            return false;
        }

        //判断菜单点击事件
        if ($message['MsgType'] == 'event') {
            $data = [
                'wechat_id' => $wechat_id,
                'fromusername' => $message['FromUserName'],
                'createtime' => $message['CreateTime'],
                'msgtype' => $message['MsgType'],
                'keywords' => $message['EventKey'],
            ];
            // 使用FromUserName + CreateTime + keywords 排重
            $where = [
                'fromusername' => $message['FromUserName'],
                'createtime' => $message['CreateTime'],
                'keywords' => $data['keywords'],
            ];
        } else {
            $data = [
                'wechat_id' => $wechat_id,
                'fromusername' => $message['FromUserName'],
                'createtime' => $message['CreateTime'],
                'msgtype' => $message['MsgType'],
                'keywords' => $message['Content'],
                'msgid' => $message['MsgId'],
            ];
            // 使用msgid + keywords排重
            $where = [
                'msgid' => $data['msgid'],
                'keywords' => $data['keywords'],
            ];
        }

        if (empty($data['keywords']) || is_null($data['keywords'])) {
            return false;
        }

        // 插入
        $rs = WechatMessageLog::where('wechat_id', $wechat_id)->where($where)->count();
        if (empty($rs)) {
            return WechatMessageLog::create($data);
        }
    }

    /**
     * 微信消息日志队列之处理发送状态
     * @param integer $wechat_id
     * @param array $message
     * @return bool
     */
    public function messageLogAlignmentSend($wechat_id = 0, $message = [])
    {
        if (empty($wechat_id) || empty($message)) {
            return false;
        }

        // 查询并更新发送状态
        if ($message['msgtype'] == 'event') {
            // 使用FromUserName + CreateTime + keywords 排重
            $where = [
                'fromusername' => $message['fromusername'],
                'createtime' => $message['createtime'],
                'keywords' => $message['keywords'],
                'is_send' => 0
            ];
        } else {
            // 使用msgid + keywords 排重
            $where = [
                'msgid' => $message['msgid'],
                'keywords' => $message['keywords'],
                'is_send' => 0
            ];
        }
        WechatMessageLog::where('wechat_id', $wechat_id)->where($where)->update(['is_send' => 1]);
        // 删除已发送的消息记录
        $map['fromusername'] = $message['fromusername'];
        $map['keywords'] = $message['keywords'];
        $lastId = WechatMessageLog::where($map)
            ->orderBy('id', 'desc')
            ->value('id');
        if (!empty($lastId)) {
            //$map['is_send'] = 1;
            WechatMessageLog::where($map)
                ->where('id', '<', $lastId)
                ->delete();
        }
    }

    /**
     * 关注处理更新
     * @param $wechat_id
     * @param array $user
     * @param string $scene_id
     * @param int $ru_id
     */
    public function subscribeAction($wechat_id = 0, $user = [], $scene_id = '', $ru_id = 0)
    {
        if ($user && $wechat_id) {
            // 组合数据
            $data['wechat_id'] = $wechat_id;
            $data['subscribe'] = $user['subscribe'];
            $data['openid'] = $user['openid'];
            $data['nickname'] = $user['nickname'];
            $data['sex'] = $user['sex'];
            $data['language'] = $user['language'];
            $data['city'] = $user['city'];
            $data['province'] = $user['province'];
            $data['country'] = $user['country'];
            $data['headimgurl'] = $user['headimgurl'];
            $data['subscribe_time'] = $user['subscribe_time'];
            $data['remark'] = $user['remark'];
            $data['groupid'] = isset($user['groupid']) ? $user['groupid'] : '';
            $data['unionid'] = isset($user['unionid']) ? $user['unionid'] : '';

            // 公众号启用微信开发者平台，平台检查unionid, 商家不检查unionid
            if ($ru_id > 0) {
                unset($data['unionid']);
                $condition = ['openid' => $data['openid'], 'wechat_id' => $wechat_id];
            } else {
                if (empty($data['unionid'])) {
                    return false;
                }
                $condition = ['unionid' => $data['unionid'], 'wechat_id' => $wechat_id];
            }
            $result = WechatUser::select('openid', 'unionid')->where($condition)->first();

            // 未关注
            if (empty($result)) {
                if ($ru_id == 0) {
                    // 查询推荐人ID
                    $scenes = $this->return_is_drp($scene_id);
                    if ($scenes['is_drp'] == true) {
                        $data['drp_parent_id'] = !empty($scenes['drp_parent_id']) ? $scenes['drp_parent_id'] : 0;
                        $data['parent_id'] = !empty($scenes['drp_parent_id']) ? $scenes['drp_parent_id'] : 0;
                    } else {
                        $data['drp_parent_id'] = !empty($scenes['parent_id']) ? $scenes['parent_id'] : 0;
                        $data['parent_id'] = !empty($scenes['parent_id']) ? $scenes['parent_id'] : 0;
                    }
                    // 更新扫码引荐二维码 推荐扫描量
                    if ($scenes['is_drp'] == false && $data['parent_id'] > 0) {
                        $this->shareQrcodeSubscribeAction($wechat_id, $data['parent_id']);
                    }
                }
                $data['from'] = 0; // 微信粉丝来源 0 关注公众号
                // 新增微信粉丝
                WechatUser::create($data);
            } else {
                // 更新微信用户资料
                WechatUser::where($condition)->update($data);
            }

            // 已关注用户基本信息
            if ($result && $ru_id == 0) {
                $this->updateWechatUserUnionid($wechat_id, $user); //兼容更新平台粉丝unionid
            }
        }
    }

    /**
     * 取消关注处理
     * @param int $wechat_id
     * @param string $openid
     */
    public function unsubscribeAction($wechat_id = 0, $openid = '')
    {
        // 未关注
        $where['openid'] = $openid;
        $where['wechat_id'] = $wechat_id;

        $model = WechatUser::where($where);
        $rs = $model->count();
        // 修改关注状态
        if ($rs > 0) {
            $model->where($where)->update(['subscribe' => 0]);

            // 同步用户标签 (取消关注 微信端标签也删除了)
            WechatUserTag::where($where)->delete();
        }
    }

    /**
     * 更新渠道二维码扫描量、返回关键词
     *
     * @param int $wechat_id
     * @param string $scene_id
     * @return mixed|string
     */
    public function qrcodeSubscribeAction($wechat_id = 0, $scene_id = '')
    {
        // 推荐uid
        if (stripos($scene_id, 'u') !== false) {
            $scene_id = str_replace('u=', '', $scene_id);
        }
        $scene_id = intval($scene_id);
        $qrcode = WechatQrcode::select('function', 'username')
            ->where(['scene_id' => $scene_id, 'wechat_id' => $wechat_id])
            ->first();
        $qrcode = $qrcode ? $qrcode->toArray() : [];

        if ($qrcode) {
            // 增加渠道二维码的扫描量
            if (empty($qrcode['username'])) {
                WechatQrcode::where(['scene_id' => $scene_id, 'wechat_id' => $wechat_id])->increment('scan_num', 1);
            }

            return $qrcode['function'] ?? '';
        }
        return '';
    }

    /**
     * 更新扫码引荐二维码 推荐扫描量
     * @param int $wechat_id
     * @param int $parent_id
     */
    public function shareQrcodeSubscribeAction($wechat_id = 0, $parent_id = 0)
    {
        $qr_username = WechatQrcode::where(['scene_id' => $parent_id, 'wechat_id' => $wechat_id])->value('username');
        if ($qr_username) {
            WechatQrcode::where(['scene_id' => $parent_id, 'wechat_id' => $wechat_id])->increment('scan_num', 1);
        }
    }

    /**
     * 上报地理位置事件
     *
     * @param int $wechat_id
     * @param array $message
     */
    public function updateLocation($wechat_id = 0, $message = [])
    {
        if ($message) {
            if ($message['Latitude'] && $message['Longitude']) {
            }
        }
    }

    /**
     * 兼容更新平台粉丝unionid
     * 原无unionid，现在unionid 通过 openid 更新 unionid
     * @param int $wechat_id
     * @param array $user
     */
    public function updateWechatUserUnionid($wechat_id = 0, $user = [])
    {
        if ($user) {
            // 组合数据
            $data = [
                'wechat_id' => $wechat_id,
                'openid' => $user['openid'],
                'unionid' => $user['unionid'] ?? ''
            ];
            // unionid 微信开放平台唯一标识
            if (!empty($data['unionid'])) {
                // 兼容查询用户openid
                $where = ['openid' => $user['openid'], 'wechat_id' => $wechat_id];
                $res = WechatUser::select('unionid', 'ect_uid')->where($where)->first();
                $res = $res ? $res->toArray() : [];
                if (empty($res['unionid'])) {
                    WechatUser::where($where)->update($data);
                    if (!empty($res['ect_uid'])) {
                        // 更新社会化登录用户信息
                        $connect_userinfo = $this->connectUserService->getConnectUserinfo($user['unionid'], 'wechat');
                        if (empty($connect_userinfo)) {
                            ConnectUser::where(['open_id' => $user['openid']])->update(['open_id' => $user['unionid']]);
                        }
                        $user['user_id'] = $res['ect_uid'];
                        $this->connectUserService->updateConnectUser($user, 'wechat');
                    }
                }
            }
        }
    }

    /**
     * 获取二维码的场景值
     */
    public function getRevSceneId($eventKey = '')
    {
        if (isset($eventKey)) {
            return str_replace('qrscene_', '', $eventKey);
        } else {
            return false;
        }
    }

    /**
     * 更新群发消息结果
     * @param int $wechat_id
     * @param array $message
     */
    public function updateWechatMassHistory($wechat_id = 0, $message = [])
    {
        if ($message) {
            $data = [
                'status' => $message['Status'],
                'totalcount' => $message['TotalCount'],
                'filtercount' => $message['FilterCount'],
                'sentcount' => $message['SentCount'],
                'errorcount' => $message['ErrorCount'],
            ];
            // 更新群发结果
            WechatMassHistory::where(['msg_id' => $message['MsgID'], 'wechat_id' => $wechat_id])->update($data);
        }
    }

    /**
     * 更新模板消息通知结果
     * @param int $wechat_id
     * @param array $message
     */
    public function updateWechatTeamplateLog($wechat_id = 0, $message = [])
    {
        if ($message) {
            // 模板消息发送结束事件
            if ($message['Status'] == 'success') {
                // 推送成功
                $data = ['status' => 1];
            } elseif ($message['Status'] == 'failed:user block') {
                // 用户拒收
                $data = ['status' => 2];
            } else {
                // 发送失败
                $data = ['status' => 0]; // status 0 发送失败，1 发送与接收成功，2 用户拒收
            }
            // 更新模板消息发送状态
            WechatTemplateLog::where(['msgid' => $message['MsgID'], 'openid' => $message['FromUserName'], 'wechat_id' => $wechat_id])->update($data);
        }
    }

    /**
     * 记录操作信息
     * @param int $wechat_id
     * @param array $user
     * @param array $content
     * @param int $is_wechat_admin
     */
    public function recordMsgAction($wechat_id = 0, $user = [], $content = [], $is_wechat_admin = 0)
    {
        if ($user) {
            $time = $this->timeRepository->getGmTime();

            if (isset($user['unionid']) && $user['unionid']) {
                $uid = WechatUser::where(['openid' => $user['openid'], 'unionid' => $user['unionid'], 'subscribe' => 1, 'wechat_id' => $wechat_id])->value('uid');
            } else {
                $uid = WechatUser::where(['openid' => $user['openid'], 'subscribe' => 1, 'wechat_id' => $wechat_id])->value('uid');
            }
            if ($uid && $content['msg']) {
                $data = [
                    'uid' => $uid,
                    'msg' => $content['msg'],
                    'msgtype' => $content['msgtype'],
                    'wechat_id' => $wechat_id,
                    'send_time' => isset($content['createtime']) ? $content['createtime'] : $time
                ];
                // 微信公众号回复标识
                if ($is_wechat_admin > 0) {
                    $data['is_wechat_admin'] = $is_wechat_admin;
                }
                WechatCustomMessage::create($data);
            }
        }
    }

    /**
     * 兼容更新用户信息 关注状态（未配置微信通之前关注的粉丝）
     * @param string $wechat_id
     * @param array $new_user
     */
    public function updateWechatuserSubscribeAction($wechat_id = 0, $new_user = [])
    {
        if ($new_user && !empty($new_user['unionid'])) {
            $user_data = [
                'subscribe' => $new_user['subscribe'],
                'subscribe_time' => $new_user['subscribe_time'],
                'openid' => $new_user['openid']
            ];
            $res = WechatUser::select('openid', 'unionid')->where(['unionid' => $new_user['unionid'], 'wechat_id' => $wechat_id])->first();
            $res = $res ? $res->toArray() : [];
            if ($res) {
                WechatUser::where(['unionid' => $new_user['unionid'], 'wechat_id' => $wechat_id])->update($user_data);
            }
        }
    }

    /**
     * 查询状态
     * @param string $starttime
     * @param string $endtime
     * @return int 0 未开始, 1 正在进行, 2 已结束
     */
    public function get_status($starttime = '', $endtime = '')
    {
        $nowtime = $this->timeRepository->getGmTime();
        if (!empty($starttime) && !empty($endtime)) {
            if ($starttime > $nowtime) {
                return 0; //未开始
            } elseif ($starttime < $nowtime && $endtime > $nowtime) {
                return 1; //进行中
            } elseif ($endtime < $nowtime) {
                return 2; //已结束
            }
            return -1;
        }
        return -1;
    }

    /**
     * 返回扫码推荐或分销推荐信息
     * @param $scene_id
     * @return
     */
    public function return_is_drp($scene_id = '')
    {
        $scenes = [
            'is_drp' => false,
            'parent_id' => 0,
            'drp_parent_id' => 0,
        ];
        if (stripos($scene_id, 'u') !== false) {
            // 推荐uid
            $parent_id = str_replace('u=', '', $scene_id);
            $parent_id = intval($parent_id);

            $users = Users::query()->where('user_id', $parent_id)->count();
            $parent_id = empty($users) ? 0 : $parent_id;

            $scenes['parent_id'] = $parent_id;
            $scenes['is_drp'] = false;
        } elseif (stripos($scene_id, 'd') !== false && file_exists(MOBILE_DRP)) {
            // 推荐分销商id
            $drp_parent_id = str_replace('d=', '', $scene_id);
            $drp_parent_id = intval($drp_parent_id);

            $drp = DrpShop::query()->where(['user_id' => $drp_parent_id, 'audit' => 1])->count();

            $drp_parent_id = empty($drp) ? 0 : $drp_parent_id;

            $scenes['drp_parent_id'] = $drp_parent_id;
            $scenes['is_drp'] = true;
        }

        return $scenes;
    }

    /**
     * 发送微信通模板消息
     *
     * @param string $code
     * @param array $content
     * @param string $url
     * @param int $uid
     * @param int $ru_id
     */
    public function push_template($code = '', $content = [], $url = '', $uid = 0, $ru_id = 0)
    {
        //公众号信息
        $wxinfo = $this->getWechatConfigByRuId($ru_id);
        if ($wxinfo && $wxinfo['status'] == 1) {
            $config = [
                'app_id' => $wxinfo['appid'],
                'secret' => $wxinfo['appsecret'],
                'token' => $wxinfo['token'],
                'aes_key' => $wxinfo['encodingaeskey'],
                'response_type' => 'array',
            ];
            $weObj = Factory::officialAccount($config);

            $wechat_id = $wxinfo['id'] ?? 1;

            // 查询openid
            $openid = $this->wechatUserService->get_openid($uid);

            $template = WechatTemplate::where('code', $code)
                ->where('status', 1)
                ->first();
            $template = $template ? $template->toArray() : [];

            if ($template && $template['title'] && !empty($openid)) {
                $content['first'] = !empty($content['first']) ? $content['first'] : ['value' => $template['title'], 'color' => '#173177'];
                // 自定义备注信息
                if (isset($content['remark']) && $content['remark']) {
                    $remark = $content['remark'];
                } elseif (isset($template['content']) && !empty($template['content'])) {
                    $remark = ['value' => $template['content'], 'color' => '#FF0000'];
                }
                if (!empty($remark)) {
                    $content['remark'] = $remark;
                }

                $rs['code'] = $code;
                $rs['openid'] = $openid;
                $rs['data'] = serialize($content);
                $rs['url'] = $url;
                $rs['wechat_id'] = $wechat_id;
                WechatTemplateLog::create($rs);

                $logs = WechatTemplateLog::select('wechat_id', 'code', 'openid', 'data', 'url')
                    ->where(['openid' => $openid, 'wechat_id' => $wechat_id, 'status' => 0])
                    ->orderBy('id', 'desc')
                    ->first();
                $logs = $logs ? $logs->toArray() : [];

                if ($logs) {
                    // 组合发送数据
                    $template_id = WechatTemplate::where(['code' => $logs['code']])->value('template_id');

                    $message = [
                        'touser' => $logs['openid'],
                        'template_id' => $template_id,
                        'url' => $logs['url'],
                        'data' => unserialize($logs['data'])
                    ];
                    $rs = $weObj->template_message->send($message);
                    if ($rs) {
                        $msgid = $rs['msgid'] ?? '';
                        if (empty($msgid)) {
                            return false;
                        }
                        // 更新记录模板消息ID
                        WechatTemplateLog::where(['code' => $logs['code'], 'openid' => $logs['openid'], 'wechat_id' => $logs['wechat_id']])->update(['msgid' => $msgid]);
                    }
                }
            }
        }
    }

    /**
     * 获取微信jssdk服务
     * @param int $ru_id
     * @param string $url
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getJssdk($ru_id = 0, $url = '')
    {
        if (!is_file(MOBILE_WECHAT)) {
            return ['status' => '404', 'message' => 'no data'];
        }

        $weObj = [];
        $wxinfo = $this->getWechatConfigByRuId($ru_id);
        if ($wxinfo && $wxinfo['status'] == 1) {
            $config = [
                'app_id' => $wxinfo['appid'],
                'secret' => $wxinfo['appsecret'],
                'token' => $wxinfo['token'],
                'aes_key' => $wxinfo['encodingaeskey'],
                'response_type' => 'array',
            ];
            $weObj = Factory::officialAccount($config);
            $weObj->rebind('cache', new CustomCache());
        }

        if (is_null($weObj)) {
            $weObj = Facade::officialAccount('default');
        }
        if ($weObj) {
            $weObj->jssdk->setUrl($url); // 设置当前地址
            $jsApiList = [
                'updateAppMessageShareData',
                'updateTimelineShareData',
                'openAddress'
            ];
            $sdk = $weObj->jssdk->buildConfig($jsApiList, config('wechat.debug'), false, false);

            if ($sdk) {
                return ['status' => '200', 'data' => $sdk];
            }

            return ['status' => '404', 'message' => 'no sdk'];
        } else {
            return ['status' => '404', 'message' => 'no config'];
        }
    }

    /**
     * 关注送红包
     * @param string $openid
     * @return bool
     */
    public function sendBonus($openid = '')
    {
        //公众号信息
        $wxinfo = $this->getWechatConfigDefault();
        if ($wxinfo && $wxinfo['status'] == 1) {
            $config = [
                'app_id' => $wxinfo['appid'],
                'secret' => $wxinfo['appsecret'],
                'token' => $wxinfo['token'],
                'aes_key' => $wxinfo['encodingaeskey'],
                'response_type' => 'array',
            ];
            $weObj = Factory::officialAccount($config);

            $wechat_id = $wxinfo['id'] ?? 1;

            // 查询功能扩展 是否安装
            $rs = WechatExtend::select('name', 'keywords', 'command', 'config')
                ->where('enable', 1)
                ->where('wechat_id', $wechat_id)
                ->where('command', 'bonus')
                ->first();
            $rs = $rs ? $rs->toArray() : [];

            if (empty($rs)) {
                return false;
            }

            $command = Str::studly($rs['command']);
            $file = plugin_path('Wechat/' . $command . '/' . $command . '.php');
            if (file_exists($file)) {
                $plugin = '\\App\\Plugins\\Wechat\\' . $command . '\\' . $command;
                $config = [
                    'wechat_ru_id' => $wechat_id
                ];
                $obj = new $plugin($config);
                $data = $obj->returnData($openid, $rs);
                if ($data) {
                    // 发送普通客服消息
                    $msg = new Text($data['content']);
                    $weObj->customer_service->message($msg)->to($openid)->send();
                }
            }
        }
    }

    /**
     * 授权登录更新粉丝信息（平台或商家）
     *
     * @param int $wechat_ru_id
     * @param array $user
     */
    public function updateWechatUserByOpenid($wechat_ru_id = 0, $user = [])
    {
        if ($user) {
            $wechat_id = Wechat::where(['status' => 1, 'ru_id' => $wechat_ru_id])->value('id');
            $user_data = [
                'wechat_id' => $wechat_id,
                'openid' => $user['openid'],
                'nickname' => $user['nickname'] ?? '',
                'sex' => $user['sex'] ?? '',
                'language' => $user['language'] ?? '',
                'city' => $user['city'] ?? '',
                'province' => $user['province'] ?? '',
                'country' => $user['country'] ?? '',
                'headimgurl' => $user['headimgurl'] ?? '',
                'unionid' => $user['unionid'] ?? '',
            ];
            if ($wechat_ru_id > 0) {
                $codition = ['openid' => $user['openid'], 'wechat_id' => $wechat_id];
            } else {
                $codition = ['unionid' => $user['unionid'], 'wechat_id' => $wechat_id];
            }

            $count = WechatUser::where($codition)->count();
            if (empty($count)) {
                WechatUser::insert($user_data);
            } else {
                WechatUser::where($codition)->update($user_data);
            }
        }
    }
}
