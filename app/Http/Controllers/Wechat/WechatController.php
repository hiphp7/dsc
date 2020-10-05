<?php

namespace App\Http\Controllers\Wechat;

use App\Http\Controllers\Controller;
use App\Libraries\Wechat;
use App\Models\WechatExtend;
use App\Models\WechatMarketing;
use App\Models\WechatTemplate;
use App\Models\WechatTemplateLog;
use App\Models\WechatUser;
use App\Repositories\Common\TimeRepository;
use App\Services\Wechat\WechatHelperService;
use App\Services\Wechat\WechatMediaService;
use App\Services\Wechat\WechatService;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Encryptor;
use EasyWeChat\Kernel\Messages\Image;
use EasyWeChat\Kernel\Messages\Music;
use EasyWeChat\Kernel\Messages\News;
use EasyWeChat\Kernel\Messages\NewsItem;
use EasyWeChat\Kernel\Messages\Text;
use EasyWeChat\Kernel\Messages\Transfer;
use EasyWeChat\Kernel\Messages\Video;
use EasyWeChat\Kernel\Messages\Voice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WechatController extends Controller
{
    protected $weObj = null;
    protected $wechat_id = 0;
    protected $wechat_ru_id = 0;
    protected $timeRepository;
    protected $wechatService;
    protected $config;
    protected $wechatHelperService;
    protected $wechatMediaService;

    public function __construct(
        TimeRepository $timeRepository,
        WechatService $wechatService,
        WechatHelperService $wechatHelperService,
        WechatMediaService $wechatMediaService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->wechatService = $wechatService;
        $this->wechatHelperService = $wechatHelperService;
        $this->wechatMediaService = $wechatMediaService;

        $shopConfig = cache('shop_config');
        $shopConfig = !is_null($shopConfig) ? $shopConfig : false;
        if ($shopConfig === false) {
            $this->config = app(\App\Services\Common\ConfigService::class)->getConfig();
        } else {
            $this->config = $shopConfig;
        }
    }

    public function initialize()
    {
        $secret_key = request()->input('key', '');
        if ($secret_key) {
            // 获取公众号配置
            $wechat = $this->wechatService->getWechatConfigByKey($secret_key);
            if ($wechat && $wechat['status'] == 1) {
                // 微信实例
                $this->weObj = $this->instantiation($wechat);

                $this->wechat_id = $wechat['id'];
                $this->wechat_ru_id = $wechat['ru_id'];

                $this->init_params();

                load_helper(['mobile']);

                // 验证token
                if (request()->isMethod('GET')) {
                    return $this->checkSignature($wechat);
                }
            }
        }
    }

    /**
     * 接口对象
     * @param array $wechat
     * @return \EasyWeChat\OfficialAccount\Application
     */
    protected function instantiation($wechat = [])
    {
        if (empty($wechat)) {
            return null;
        }

        if ($wechat['status'] == 1) {
            $config = [
                'app_id' => $wechat['appid'],
                'secret' => $wechat['appsecret'],
                'token' => $wechat['token'],
                'aes_key' => $wechat['encodingaeskey'] ?? '',
                'response_type' => 'array',
            ];
            if (config('app.debug')) {
                $config['log'] = config('wechat.defaults.log');
            }
            return Factory::officialAccount($config);
        }
    }

    /**
     * 处理参数
     */
    private function init_params()
    {
        if ($this->wechat_ru_id > 0) {
            cookie()->queue('wechat_ru_id', $this->wechat_ru_id, 24 * 60);
        } else {
            cookie()->queue(cookie()->forget('wechat_ru_id'));
        }

        return response()->json(['status' => 1]);
    }

    /**
     * 执行方法
     */
    public function index()
    {
        if (is_null($this->weObj)) {
            return '';
        }

        $this->weObj->server->push(function ($message) {

            $event = strtolower($message['Event']);

            // 微信消息日志队列之存入数据库
            if ((isset($message['Event']) && in_array($event, ['click'])) || (isset($message['Content']) && !empty($message['Content']))) {
                $this->wechatService->messageLogAlignmentAdd($this->wechat_id, $message);
            }
            // 兼容更新用户关注状态（未配置微信通之前关注的粉丝）
            $this->update_wechatuser_subscribe($this->wechat_id, $message['FromUserName']);
            $keywords = '';
            $scene_id = '';

            // 事件类型
            switch ($message['MsgType']) {
                // 收到事件消息
                case 'event':
                    switch ($event) {
                        // 关注事件
                        case 'subscribe':
                            if (isset($message['EventKey'])) {
                                // 用户扫描带参数二维码(未关注)
                                $scene_id = $this->wechatService->getRevSceneId($message['EventKey']);
                                $this->subscribe($message['FromUserName'], $scene_id);
                            } else {
                                $this->subscribe($message['FromUserName']);
                            }
                            // 关注自动回复信息
                            return $this->msg_reply('subscribe', $message);
                            break;
                        case 'unsubscribe':
                            // 取消关注事件
                            $this->unsubscribe($message['FromUserName']);
                            return '';
                            break;
                        case 'scan':
                            // 扫描带参数二维码(用户已关注)
                            $scene_id = $this->wechatService->getRevSceneId($message['EventKey']);
                            break;
                        case 'location':
                            // 上报地理位置事件
                            $this->wechatService->updateLocation($this->wechat_id, $message);
                            return '';
                            break;
                        case 'click':
                            // 自定义菜单事件 点击菜单拉取消息
                            $keywords = $message['EventKey'];
                            break;
                        case 'view':
                            // 自定义菜单事件 点击菜单跳转链接
                            redirect()->to($message['EventKey'])->send();
                            break;
                        case 'kf_create_session':
                            return '';
                            break;
                        case 'kf_close_session':
                            return '';
                            break;
                        case 'kf_switch_session':
                            return '';
                            break;
                        case 'masssendjobfinish':
                            // 更新群发消息结果
                            $this->wechatService->updateWechatMassHistory($this->wechat_id, $message);
                            return '';
                            break;
                        case 'templatesendjobfinish':
                            // 更新模板消息通知结果
                            $this->wechatService->updateWechatTeamplateLog($this->wechat_id, $message);
                            return '';
                            break;
                    }
                    break;
                case 'text':
                    $keywords = $message['Content']; // '收到文本消息'
                    break;
                case 'image':
                    return '';// '收到图片消息'
                    break;
                case 'voice':
                    return '';// '收到语音消息'
                    break;
                case 'video':
                    return '';// '收到视频消息'
                    break;
                case 'location':
                    return '';// '收到坐标消息'
                    break;
                case 'link':
                    return '';// '收到链接消息'
                    break;
                case 'file':
                    return '';// '收到文件消息'
                    break;
                // ... 其它消息
                default:
                    return $this->msg_reply('msg', $message); // 消息自动回复
                    break;
            }

            // 扫二维码回复消息
            if ($scene_id) {
                return $this->scan_reply($scene_id, $message);
            }

            // 微信消息日志队列 开始 查询发送状态
            $contents = $this->wechatService->wechatMessageLog($this->wechat_id, $keywords, $message);
            if (!empty($contents) && !empty($contents['keywords'])) {
                $message['Content'] = html_in($contents['keywords']);
                $message['FromUserName'] = $contents['fromusername'];
                // 记录用户操作信息
                $this->record_msg($message);
                // 微信消息日志队列之处理发送状态
                $this->wechatService->messageLogAlignmentSend($this->wechat_id, $contents);

                // 多客服
                $rs = $this->customer_service($message);
                if ($rs) {
                    return $rs;
                }
                // 功能插件
                $rs1 = $this->get_function($message);
                if ($rs1) {
                    return $rs1;
                }
                // 微信营销
                $rs2 = $this->get_marketing($message);
                if ($rs2) {
                    return $rs2;
                }
                // 关键词回复
                $rs3 = $this->keywords_reply($message);
                if ($rs3) {
                    return $rs3;
                }
                // 消息自动回复
                return $this->msg_reply('msg', $message);
            }
        });

        return $this->weObj->server->serve();
    }

    /**
     * 验证token
     * @param array $config
     * @return mixed|string
     */
    protected function checkSignature($config = [])
    {
        if ($decrypted = request()->get('echostr')) {
            $decrypt = new Encryptor($config['appid'], $config['token'], $config['encodingaeskey'] ?? null);

            $signature = request()->get('signature', '');
            $msgSignature = request()->get('msg_signature') ?? $signature;

            $signature = $decrypt->signature($config['token'], request()->get('timestamp'), request()->get('nonce'), '');

            if (config('app.debug')) {
                Log::info($signature);
                Log::info($msgSignature);
            }

            if ($signature == $msgSignature) {
                return $decrypted;
            }
        }

        return 'no access';
    }

    /**
     * 关注处理
     *
     * @param string $openid
     * @param string $scene_id
     */
    protected function subscribe($openid = '', $scene_id = '')
    {
        if (!empty($openid)) {
            // 获取微信用户信息
            $user = $this->weObj->user->get($openid);
            // 关注更新
            $this->wechatService->subscribeAction($this->wechat_id, $user, $scene_id, $this->wechat_ru_id);

            // 检测是否有模板消息待发送
            $this->check_template_log($this->wechat_id, $user['openid']);
        }
    }

    /**
     * 取消关注处理
     *
     * @param string $openid
     */
    protected function unsubscribe($openid = '')
    {
        $this->wechatService->unsubscribeAction($this->wechat_id, $openid);
    }

    /**
     * 扫描二维码回复消息
     *
     * @param int $scene_id
     * @param array $message
     * @param bool $flag
     * @return bool
     */
    protected function scan_reply($scene_id = 0, $message = [], $flag = true)
    {
        // 扫描二维码
        if (!empty($scene_id)) {
            $qr_keyword = $this->wechatService->qrcodeSubscribeAction($scene_id);
            if (!empty($qr_keyword)) {
                // 功能插件
                $message['Content'] = $qr_keyword;

                $rs1 = $this->get_function($message, $flag);
                if ($rs1) {
                    return $rs1;
                } else {
                    // 关键词回复
                    return $this->keywords_reply($message, $flag);
                }
            }
        }
    }

    /**
     * 被动关注，消息回复
     *
     * @param string $type
     * @param array $message
     * @return Image|Text|Video|Voice
     */
    protected function msg_reply($type = '', $message = [])
    {
        $replyInfo = $this->wechatMediaService->wechatReply($this->wechat_id, $type);

        if ($replyInfo) {
            // 记录微信回复信息
            $this->record_msg($message, 1);

            if (!empty($replyInfo['media_id'])) {
                $media = $this->wechatMediaService->wechatMediaInfo($replyInfo['media_id']);
                if ($media && isset($media['type'])) {
                    if ($media['type'] == 'news') {
                        $media['type'] = 'image';
                    }
                    // 上传多媒体文件
                    $filename = storage_public($media['file']);

                    // 开启OSS 且本地没有图片的处理
                    if ($this->config['open_oss'] == 1 && !file_exists($filename)) {
                        $filelist = ['0' => $media['file']];
                        $this->BatchDownloadOss($filelist);
                    }

                    $rs = $this->weObj->media->upload($media['type'], $filename);
                    if ($rs) {
                        // 回复
                        if ($rs['type'] == 'image') {
                            // 图片回复
                            return new Image($rs['media_id']);
                        } elseif ($rs['type'] == 'voice') {
                            // 声音回复
                            return new Voice($rs['media_id']);
                        } elseif ($rs['type'] == 'video') {
                            // 视频回复
                            return new Video($rs['media_id'], [
                                'title' => $media['title'],
                                'description' => strip_tags($media['content']),
                            ]);
                        }
                    }
                }
            } else {
                // 文本回复
                if ($replyInfo['content']) {
                    $replyInfo['content'] = html_out($replyInfo['content']);
                    return new Text($replyInfo['content']);
                }
            }
        }
    }

    /**
     * 关键词回复
     *
     * @param array $message
     * @param bool $flag 是否普通消息
     * @return bool|Text|Image|Video|Voice|News
     */
    protected function keywords_reply($message = [], $flag = false)
    {
        $endrs = false;

        $keywords = '';
        if (isset($message['Content']) || $message['MsgType'] == 'text') {
            $keywords = $message['Content'];
        } elseif ($message['MsgType'] == 'event' && $message['Event'] == 'CLICK') {
            // 自定义菜单事件 点击菜单拉取消息
            $keywords = $message['EventKey'];
        }

        $result = $this->wechatMediaService->wechatReplyKeywords($this->wechat_id, $keywords);

        if (!empty($result)) {

            //记录微信回复信息
            $this->record_msg($message, 1);

            // 素材回复
            if (!empty($result['media_id'])) {
                $mediaInfo = $this->wechatMediaService->wechatMediaInfo($result['media_id']);

                if ($mediaInfo) {
                    if (in_array($result['reply_type'], ['image', 'voice', 'video'])) {
                        // 上传多媒体文件
                        $filename = storage_public($mediaInfo['file']);

                        // 开启OSS 且本地没有图片的处理
                        if ($this->config['open_oss'] == 1 && !file_exists($filename)) {
                            $filelist = ['0' => $mediaInfo['file']];
                            $this->BatchDownloadOss($filelist);
                        }

                        $rs = $this->weObj->media->upload($mediaInfo['type'], $filename);

                        if ($rs) {
                            if ($result['reply_type'] == 'image') {
                                if ($flag == true) {
                                    // 发送普通客服消息
                                    $this->send_custom_message($message['FromUserName'], 'image', $rs['media_id']);
                                    return true;
                                } else {
                                    // 图片回复
                                    return new Image($rs['media_id']);
                                }
                            } elseif ($result['reply_type'] == 'voice') {
                                if ($flag == true) {
                                    // 发送普通客服消息
                                    $this->send_custom_message($message['FromUserName'], 'voice', $rs['media_id']);
                                    return true;
                                } else {
                                    // 声音回复
                                    return new Voice($rs['media_id']);
                                }
                            } elseif ($result['reply_type'] == 'video') {
                                if ($flag == true) {
                                    // 发送普通客服消息
                                    $replyData = [
                                        'media_id' => $rs['media_id'],
                                        'title' => $mediaInfo['title'],
                                        'description' => strip_tags($mediaInfo['content']),
                                    ];
                                    $this->send_custom_message($message['FromUserName'], 'video', $replyData);
                                    return true;
                                } else {
                                    // 视频回复
                                    return new Video($rs['media_id'], [
                                        'title' => $mediaInfo['title'],
                                        'description' => strip_tags($mediaInfo['content']),
                                    ]);
                                }
                            }
                        }
                    } elseif ($result['reply_type'] == 'news') {
                        // 图文素材
                        $replyData = [];
                        if (!empty($mediaInfo['article_id'])) {
                            $article_ids = explode(',', $mediaInfo['article_id']);
                            foreach ($article_ids as $key => $val) {
                                $artinfo = $this->wechatMediaService->wechatMediaInfo($val);

                                $replyData[$key] = [
                                    'title' => $artinfo['title'],
                                    'description' => empty($artinfo['digest']) ? Str::limit(strip_tags(html_out($artinfo['content'])), 100) : $artinfo['digest'],
                                    'url' => empty($artinfo['link']) ? dsc_url('/#/wechatMedia', ['id' => $artinfo['id']]) : strip_tags(html_out($artinfo['link'])),
                                    'image' => empty($artinfo['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($artinfo['file']),
                                ];
                            }
                        } else {
                            $replyData[] = [
                                'title' => $mediaInfo['title'],
                                'description' => empty($mediaInfo['digest']) ? Str::limit(strip_tags(html_out($mediaInfo['content'])), 100) : $mediaInfo['digest'],
                                'url' => empty($mediaInfo['link']) ? dsc_url('dsc_url/#/wechatMedia', ['id' => $mediaInfo['id']]) : strip_tags(html_out($mediaInfo['link'])),
                                'image' => empty($mediaInfo['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($mediaInfo['file']),
                            ];
                        }

                        if ($flag == true) {
                            $this->send_custom_message($message['FromUserName'], 'news', $replyData);
                            return true;
                        } else {
                            // 图文回复
                            $replyData = $this->news_item($replyData);
                            return new News($replyData);
                        }
                    }
                }
            } else {
                // 文本回复
                if ($result['content']) {
                    $result['content'] = html_out($result['content']);
                    if ($flag == true) {
                        $this->send_custom_message($message['FromUserName'], 'text', $result['content']);
                        return true;
                    } else {
                        return new Text($result['content']);
                    }
                }
            }
        }

        return $endrs;
    }

    /**
     * 功能变量查询
     *
     * @param array $message
     * @param bool $flag
     * @return boolean|Text|Image|Video|Voice|News
     */
    public function get_function($message = [], $flag = false)
    {
        $return = false;

        $keywords = '';
        if (isset($message['Content']) || $message['MsgType'] == 'text') {
            $keywords = $message['Content'];
        } elseif ($message['MsgType'] == 'event' && $message['Event'] == 'CLICK') {
            // 自定义菜单事件 点击菜单拉取消息
            $keywords = $message['EventKey'];
        }

        $rs = WechatExtend::select('name', 'keywords', 'command', 'config')
            ->where('enable', 1)
            ->where('wechat_id', $this->wechat_id)
            ->where(function ($query) use ($keywords) {
                $query->where('keywords', 'like', '%' . $keywords . '%')
                    ->orWhere('command', 'like', '%' . $keywords . '%');
            })
            ->orderBy('id', 'DESC')
            ->get();
        $rs = $rs ? $rs->toArray() : [];

        if (empty($rs)) {
            $rs = WechatExtend::select('name', 'keywords', 'command', 'config')
                ->where('enable', 1)
                ->where('wechat_id', $this->wechat_id)
                ->where('command', 'search')
                ->get();
            $rs = $rs ? $rs->toArray() : [];
        }
        $info = reset($rs);
        if ($info) {
            $info['user_keywords'] = $keywords;

            $command = Str::studly($info['command']);
            $file = plugin_path('Wechat/' . $command . '/' . $command . '.php');
            if (file_exists($file)) {
                $plugin = '\\App\\Plugins\\Wechat\\' . $command . '\\' . $command;
                $config = [
                    'wechat_id' => $this->wechat_id,
                    'wechat_ru_id' => $this->wechat_ru_id
                ];
                $obj = new $plugin($config);
                $data = $obj->returnData($message['FromUserName'], $info);
                if ($data) {
                    //记录用户操作信息
                    $this->record_msg($message, 1);

                    // 数据回复类型
                    if (in_array($data['type'], ['image', 'voice', 'video'])) {
                        // 上传多媒体文件
                        $filename = storage_public($data['path']);

                        // 开启OSS 且本地没有图片的处理
                        if ($this->config['open_oss'] == 1 && !file_exists($filename)) {
                            $filelist = ['0' => $data['path']];
                            $this->BatchDownloadOss($filelist);
                        }

                        $rs = $this->weObj->media->upload($data['type'], $filename);

                        if ($rs) {
                            if ($data['type'] == 'image') {
                                if ($flag == true) {
                                    // 发送普通客服消息
                                    $this->send_custom_message($message['FromUserName'], 'image', $rs['media_id']);
                                    return true;
                                } else {
                                    // 图片回复
                                    return new Image($rs['media_id']);
                                }
                            } elseif ($data['type'] == 'voice') {
                                if ($flag == true) {
                                    // 发送普通客服消息
                                    $this->send_custom_message($message['FromUserName'], 'voice', $rs['media_id']);
                                    return true;
                                } else {
                                    // 声音回复
                                    return new Voice($rs['media_id']);
                                }
                            } elseif ($data['type'] == 'video') {
                                if ($flag == true) {
                                    // 发送普通客服消息
                                    $replyData = [
                                        'media_id' => $rs['media_id'],
                                        'title' => $data['title'],
                                        'description' => strip_tags($data['content']),
                                    ];
                                    $this->send_custom_message($message['FromUserName'], 'video', $replyData);
                                    return true;
                                } else {
                                    // 视频回复
                                    return new Video($rs['media_id'], [
                                        'title' => $data['title'],
                                        'description' => strip_tags($data['content']),
                                    ]);
                                }
                            }
                        }
                    } elseif ($data['type'] == 'news') {
                        $replyData = [];
                        foreach ($data['content'] as $val) {
                            $replyData[] = [
                                'title' => $val['Title'],
                                'description' => isset($val['Description']) ? $val['Description'] : '',
                                'url' => isset($val['Url']) ? $val['Url'] : '',
                                'image' => isset($val['PicUrl']) ? $val['PicUrl'] : '',
                            ];
                        }
                        if ($flag == true) {
                            $this->send_custom_message($message['FromUserName'], 'news', $replyData);
                            return true;
                        } else {
                            // 图文回复
                            $replyData = $this->news_item($replyData);
                            return new News($replyData);
                        }
                    } elseif ($data['type'] == 'text') {
                        $data['content'] = html_out($data['content']);
                        if ($flag == true) {
                            // 发送普通客服消息
                            $this->send_custom_message($message['FromUserName'], 'text', $data['content']);
                            return true;
                        } else {
                            // 文本回复
                            return new Text($data['content']);
                        }
                    }
                }
            }
        }

        return $return;
    }

    /**
     * 微信营销功能查询
     *
     * @param array $message
     * @return bool|News|Text
     */
    public function get_marketing($message = [])
    {
        $return = false;

        $keywords = '';
        if (isset($message['Content']) || $message['MsgType'] == 'text') {
            $keywords = $message['Content'];
        } elseif ($message['MsgType'] == 'event' && $message['Event'] == 'CLICK') {
            // 自定义菜单事件 点击菜单拉取消息
            $keywords = $message['EventKey'];
        }

        $now = $this->timeRepository->getGmTime();

        $rs = WechatMarketing::select('id', 'name', 'command', 'background', 'description', 'status', 'url')
            ->where('wechat_id', $this->wechat_id)
            ->where(function ($query) use ($keywords) {
                $query->where('marketing_type', 'like', '%' . $keywords . '%')
                    ->orWhere('command', $keywords);
            })
            ->where('starttime', '<', $now)
            ->where('endtime', '>', $now)
            ->orderBy('id', 'DESC')
            ->first();
        $rs = $rs ? $rs->toArray() : [];

        if ($rs) {
            $replyData = ['type' => 'text', 'content' => '活动未开始或未启用'];
            if ($rs['status'] == 1) {
                $replyData = [];
                // 数据
                $replyData['type'] = 'news';
                $replyData['content'][0]['Title'] = $rs['name'];
                $replyData['content'][0]['Description'] = isset($rs['description']) ? $rs['description'] : '';
                $replyData['content'][0]['PicUrl'] = isset($rs['background']) ? $this->wechatHelperService->get_wechat_image_path($rs['background']) : '';
                $replyData['content'][0]['Url'] = isset($rs['url']) ? strip_tags(html_out($rs['url'])) : '';
            }

            //记录用户操作信息
            $this->record_msg($message, 1);

            // 数据回复类型
            if ($replyData['type'] == 'text') {
                $replyData['content'] = html_out($replyData['content']);
                return new Text($replyData['content']);
            } elseif ($replyData['type'] == 'news') {
                $items = [];
                foreach ($replyData['content'] as $item) {
                    $items[] = new NewsItem([
                        'title' => $item['Title'],
                        'description' => $item['Description'],
                        'url' => $item['Url'],
                        'image' => $item['PicUrl'],
                    ]);
                }
                return new News($items);
            }
        }

        return $return;
    }

    /**
     * 多客服
     *
     * @param array $message
     * @return bool|Transfer
     */
    public function customer_service($message = [])
    {
        $result = false;

        // 是否处在多客服流程
        $openid = $message['FromUserName'];
        $keywords = $message['Content'];
        $kfsession = $this->weObj->customer_service_session->get($openid);
        if (empty($kfsession) || empty($kfsession['kf_account'])) {
            $kefu = WechatUser::where(['openid' => $openid, 'wechat_id' => $this->wechat_id])->value('openid');
            if ($kefu && $keywords == 'kefu') {
                $rs = WechatExtend::where(['command' => 'kefu', 'enable' => 1, 'wechat_id' => $this->wechat_id])->value('config');
                if (!empty($rs)) {
                    $msg = new Text('欢迎进入多客服系统');
                    // $this->weObj->sendCustomMessage($msg);
                    $this->weObj->customer_service->message($msg)->to($openid)->send();

                    // 在线客服列表
                    $online_list = $this->weObj->customer_service->online();
                    $customer = '';
                    $config = unserialize($rs);
                    if ($online_list['kf_online_list']) {
                        foreach ($online_list['kf_online_list'] as $key => $val) {
                            if ($config['customer'] == $val['kf_account'] || ($val['status'] > 0 && $val['accepted_case'] < $val['auto_accept'])) {
                                $customer = $config['kf_account'];
                            }
                        }
                    }
                    // 转发客服消息
                    if ($customer) {
                        $result = new Transfer($customer);
                    } else {
                        $result = new Transfer();
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 关闭多客服菜单
     *
     * @param string $openid
     * @param string $keywords
     * @return bool
     */
    public function close_kf($openid = '', $keywords = 'q')
    {
        $openid = WechatUser::where(['openid' => $openid, 'wechat_id' => $this->wechat_id])->value('openid');
        if ($openid) {
            $kfsession = $this->weObj->customer_service_session->get($openid);
            if ($keywords == 'q' && isset($kfsession['kf_account']) && !empty($kfsession['kf_account'])) {
                $rs = $this->weObj->customer_service_session->close($kfsession['kf_account'], $openid);
                if ($rs) {
                    $msg = [
                        'touser' => $openid,
                        'msgtype' => 'text',
                        'text' => [
                            'content' => '您已退出多客服系统'
                        ]
                    ];
                    //$this->weObj->sendCustomMessage($msg);
                    $this->weObj->customer_service->message($msg)->to($openid)->send();
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 记录用户操作信息/ 微信回复消息
     *
     * @param array $message
     * @param int $is_wechat_admin
     */
    public function record_msg($message = [], $is_wechat_admin = 0)
    {
        $user = $this->weObj->user->get($message['FromUserName']);
        if ($user) {
            if ($message['MsgType'] == 'text') {
                $msg = $message['Content'];
            } elseif ($message['MsgType'] == 'image') {
                $msg = $message['MediaId'];
            } elseif ($message['MsgType'] == 'voice') {
                $msg = $message['MediaId'];
            } elseif ($message['MsgType'] == 'video') {
                $msg = $message['MediaId'];
            } elseif ($message['MsgType'] == 'shortvideo') {
                $msg = $message['MediaId'];
            } elseif ($message['MsgType'] == 'event') {
                $msg = ($message['Event'] == 'CLICK') ? $message['EventKey'] : $message['Event'];
            } elseif ($message['MsgType'] == 'location') {
                $msg = $message['Location_X'] . ',' . $message['Location_Y'];
            } elseif ($message['MsgType'] == 'link') {
                $msg = $message['Url'];
            } elseif ($message['MsgType'] == 'file') {
                $msg = $message['Title'];
            }
            $content = [
                'msg' => $msg,
                'msgtype' => $message['MsgType'],
                'createtime' => $message['CreateTime']
            ];
            $this->wechatService->recordMsgAction($this->wechat_id, $user, $content, $is_wechat_admin);
        }
    }

    /**
     * 插件页面显示方法
     */
    public function plugin_show()
    {
        $plugin_name = request()->get('name', '');
        $get_ru_id = request()->get('wechat_ru_id', 0);

        $wechat_ru_id = $get_ru_id > 0 ? $get_ru_id : $this->get_wechat_ru_id();

        if ($wechat_ru_id > 0) {
            $openid = session()->has('seller_openid') ? session()->get('seller_openid') : '';
        } else {
            $openid = session()->has('openid') ? session()->get('openid') : '';
        }

        if (is_wechat_browser() && empty($openid)) {
            // 获取 openid
            $redirectUrl = request()->getSchemeAndHttpHost() . request()->getRequestUri();
            if ($res = $this->getOauth($wechat_ru_id, $redirectUrl)) {
                return redirect($redirectUrl);
            }
        }

        $command = Str::studly($plugin_name);
        $file = plugin_path('Wechat/' . $command . '/' . $command . '.php');
        if (file_exists($file)) {
            $new_plugin = '\\App\\Plugins\\Wechat\\' . $command . '\\' . $command;
            $config = [
                'wechat_ru_id' => $wechat_ru_id,
            ];
            $obj = new $new_plugin($config);
            return $obj->html_show();
        }
    }

    /**
     * 插件处理方法
     */
    public function plugin_action()
    {
        $plugin_name = request()->get('name', '');
        $get_ru_id = request()->get('wechat_ru_id', 0);

        $wechat_ru_id = $get_ru_id > 0 ? $get_ru_id : $this->get_wechat_ru_id();

        if ($wechat_ru_id > 0) {
            $openid = session()->has('seller_openid') ? session()->get('seller_openid') : '';
        } else {
            $openid = session()->has('openid') ? session()->get('openid') : '';
        }

        if (is_wechat_browser() && empty($openid)) {
            // 获取 openid
            $redirectUrl = request()->getSchemeAndHttpHost() . request()->getRequestUri();
            if ($res = $this->getOauth($wechat_ru_id, $redirectUrl)) {
                return redirect($redirectUrl);
            }
        }

        $command = Str::studly($plugin_name);
        $file = plugin_path('Wechat/' . $command . '/' . $command . '.php');
        if (file_exists($file)) {
            $new_plugin = '\\App\\Plugins\\Wechat\\' . $command . '\\' . $command;
            $config = [
                'wechat_ru_id' => $wechat_ru_id,
            ];
            $obj = new $new_plugin($config);
            return $obj->executeAction();
        }
    }

    /**
     * 营销页面显示方法
     */
    public function market_show()
    {
        $market_type = request()->input('type', '');
        $function = request()->input('function', '');
        $get_ru_id = request()->input('wechat_ru_id', 0);

        $wechat_ru_id = $get_ru_id > 0 ? $get_ru_id : $this->get_wechat_ru_id();

        if ($wechat_ru_id > 0) {
            $openid = session()->has('seller_openid') ? session()->get('seller_openid') : '';
        } else {
            $openid = session()->has('openid') ? session()->get('openid') : '';
        }

        if (is_wechat_browser() && empty($openid)) {
            // 获取 openid
            $redirectUrl = request()->getSchemeAndHttpHost() . request()->getRequestUri();
            if ($res = $this->getOauth($wechat_ru_id, $redirectUrl)) {
                return redirect($redirectUrl);
            }
        }

        $market = Str::studly($market_type);
        $file = plugin_path('Market/' . $market . '/' . $market . '.php');
        if (file_exists($file) && !empty($function)) {
            $plugin = '\\App\\Plugins\\Market\\' . $market . '\\' . $market;
            $config = [
                'wechat_ru_id' => $wechat_ru_id,
            ];
            $obj = new $plugin($config);

            $function_name = 'action' . Str::camel($function);

            return $obj->$function_name();
        }
    }


    /**
     * 主动发送消息给用户 统一方法
     *
     * @param string $openid
     * @param string $msgtype
     * @param string|array $replyData
     */
    public function send_custom_message($openid = '', $msgtype = '', $replyData)
    {
        $msg = [];
        if ($msgtype == 'text') {
            $msg = new Text($replyData);
        } elseif ($msgtype == 'image') {
            $msg = new Image($replyData);
        } elseif ($msgtype == 'voice') {
            $msg = new Voice($replyData);
        } elseif ($msgtype == 'video') {
            $msg = new Video($replyData['media_id'], [
                'title' => $replyData['title'],
                'description' => $replyData['description'],
            ]);
        } elseif ($msgtype == 'music') {
            $msg = new Music([
                'title' => $replyData['title'],
                'description' => $replyData['description'],
                'url' => $replyData['musicurl'],
                'hq_url' => $replyData['hqmusicurl'],
            ]);
        } elseif ($msgtype == 'news') {
            $items = $this->news_item($replyData);
            $msg = new News($items);
        }

        $this->weObj->customer_service->message($msg)->to($openid)->send();
    }

    /**
     * 图文消息数据组装
     * @param array $replyData
     * @return array
     */
    public function news_item($replyData = [])
    {
        $items = [];
        foreach ($replyData as $item) {
            $items[] = new NewsItem([
                'title' => $item['title'],
                'description' => $item['description'],
                'url' => $item['url'],
                'image' => $item['image'],
            ]);
        }

        return $items;
    }

    /**
     * 兼容更新用户关注状态（未配置微信通之前关注的粉丝）
     */
    public function update_wechatuser_subscribe($wechat_id = 0, $openid = '')
    {
        if (!empty($openid)) {
            $user = $this->weObj->user->get($openid);
            if ($user) {
                $this->wechatService->updateWechatuserSubscribeAction($wechat_id, $user);
            }
        }
    }

    /**
     * 检测是否有模板消息待发送(最新一条记录)
     *
     * @param int $wechat_id 微信通ID
     * @param string $openid 微信用户标识
     */
    public function check_template_log($wechat_id = 0, $openid = '')
    {
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
            $rs = $this->weObj->template_message->send($message);
            if ($rs) {
                // 更新记录模板消息ID
                WechatTemplateLog::where(['code' => $logs['code'], 'openid' => $logs['openid'], 'wechat_id' => $wechat_id])->update(['msgid' => $rs['msgid']]);
            }
        }
    }

    /**
     * 获取用户授权信息 openid、unionid
     *
     * @param int $wechat_ru_id
     * @return array|bool
     */
    protected function getOauth($wechat_ru_id = 0, $redirectUrl)
    {
        $wxinfo = $this->wechatService->getWechatConfigByRuId($wechat_ru_id);

        if (empty($wxinfo)) {
            return false;
        }

        $config = [
            'appid' => $wxinfo['appid'],
            'appsecret' => $wxinfo['appsecret']
        ];
        $oauth = new Wechat($config);

        // 商家静默授权
        $scope = $wechat_ru_id > 0 ? 'snsapi_base' : 'snsapi_userinfo';
        $first_url = $oauth->getOauthRedirect($redirectUrl, 'repeat', $scope);

        $code = request()->get('code');
        $state = request()->get('state');

        if (isset($code) && isset($state) && $state == 'repeat') {
            $token = $oauth->getOauthAccessToken();

            if (!$token) {
                if ($oauth->errCode == 40029 || $oauth->errCode == 40163) {
                    return redirect()->to($first_url)->send();
                }
                return false;
            }

            $user = $oauth->getOauthUserinfo($token['access_token'], $token['openid']);

            if (!$user) {
                logResult($oauth->errCode . ':' . $oauth->errMsg);
                return false;
            }
            // 更新粉丝信息
            $this->wechatService->updateWechatUserByOpenid($wechat_ru_id, $user);

            if ($wechat_ru_id > 0) {
                session(['seller_openid' => $user['openid']]);
            } else {
                session(['openid' => $user['openid'], 'unionid' => $user['unionid']]);
            }
            session()->save();
            return true;
        }
        // 授权开始
        return redirect()->to($first_url)->send();
    }

    /**
     * 从cookie获取商家ru_id
     * @return int|mixed
     */
    public function get_wechat_ru_id()
    {
        $wechat_ru_id = request()->cookie('wechat_ru_id', 0);

        // 解密
        return $wechat_ru_id > 0 ? decrypt($wechat_ru_id) : 0;
    }
}
