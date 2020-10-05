<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Form;
use App\Models\WechatMassHistory;
use App\Models\WechatMedia;
use App\Models\WechatReply;
use App\Models\WechatRuleKeywords;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\OfficeService;
use App\Services\Wechat\WechatHelperService;
use App\Services\Wechat\WechatManageService;
use Chumper\Zipper\Zipper;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WechatController extends BaseController
{
    // 微信对象
    protected $weObj = null;
    // 微信公众号ID
    protected $wechat_id = 1;
    // 插件名称
    protected $plugin_name = '';
    protected $wechat_type = 2;

    protected $market_type = '';  // 营销类型

    protected $ru_id = 0;

    // 分页数量
    protected $page_num = 1;

    protected $wechatManageService;

    protected $timeRepository;
    protected $config;
    protected $wechatHelperService;
    protected $dscRepository;

    public function __construct(
        WechatManageService $wechatManageService,
        TimeRepository $timeRepository,
        WechatHelperService $wechatHelperService,
        DscRepository $dscRepository
    )
    {
        $this->wechatManageService = $wechatManageService;
        $this->timeRepository = $timeRepository;
        $this->wechatHelperService = $wechatHelperService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    protected function initialize()
    {
        parent::initialize();

        L(lang('admin/wechat'));
        $this->assign('lang', L());

        // 当前方法
        $currentMethod = $this->getCurrentMethodName();

        // 公众号信息
        $wechat = $this->wechatManageService->getWechatDefault();
        if (!empty($wechat)) {
            $without = [
                'index',
                'append',
                'modify',
                'delete',
                'set_default'
            ];
            if (!in_array(strtolower($currentMethod), $without)) {
                if (isset($wechat['status']) && $wechat['status'] == 0) {
                    return $this->message(lang('admin/wechat.open_wechat'), route('admin/wechat/modify'), 2);
                }
            }
            // 微信对象
            $this->weObj = $this->wechatManageService->wechatInstance($wechat);
            $this->wechat_type = $wechat['type'];
            $this->wechat_id = $wechat['id'];
        } else {
            // 新增平台微信
            $data = [
                'id' => $this->wechat_id,
                'type' => 2,
                'status' => 1,
                'default_wx' => 1
            ];
            $this->wechatManageService->createWechat($data);

            if ($currentMethod != 'index' && $currentMethod != 'modify') {
                return redirect()->route('admin/wechat/modify');
            }
        }
        $this->assign('type', $this->wechat_type);

        // 插件
        $this->plugin_name = request()->get('ks', '');
        // 营销
        $this->market_type = request()->get('type', '');

        // 初始化 每页分页数量
        $this->init_params();
    }

    /**
     * 处理公共参数
     */
    private function init_params()
    {
        $page_num = request()->cookie('page_size');
        $this->page_num = is_null($page_num) ? 10 : $page_num;
        $this->assign('page_num', $this->page_num);
    }

    /**
     * 我的公众号
     */
    public function index()
    {
        if (request()->isMethod('POST')) {
            //修改每页数量
            $page_num = request()->has('page_num') ? request()->input('page_num') : 0;
            if ($page_num > 0) {
                cookie()->queue('page_size', $page_num, 24 * 60 * 30);
                return response()->json(['status' => 1]);
            }
        }

        return redirect()->route('admin/wechat/modify');
    }

    /**
     * 我的公众号
     */
    public function modify()
    {
        // 公众号设置权限
        $this->admin_priv('wechat_admin');

        // 提交处理
        if (request()->isMethod('POST')) {
            $data = request()->input('data');

            // 验证数据
            $form = new Form();
            if (!$form->isEmpty($data['name'], 1)) {
                return $this->message(lang('admin/wechat.must_name'), null, 2);
            }
            if (!$form->isEmpty($data['orgid'], 1)) {
                return $this->message(lang('admin/wechat.must_id'), null, 2);
            }
            if (!$form->isEmpty($data['token'], 1)) {
                return $this->message(lang('admin/wechat.must_token'), null, 2);
            }

            // 更新数据
            $this->wechatManageService->updateWechat($this->wechat_id, $data);

            return $this->message(lang('admin/wechat.wechat_editor') . lang('admin/common.success'), route('admin/wechat/modify'));
        }

        // 公众号信息
        $data = $this->wechatManageService->getWechatInfo($this->wechat_id);

        if (!empty($data)) {
            $data['secret_key'] = isset($data['orgid']) && isset($data['appid']) ? $data['secret_key'] : '';
            $data['url'] = route('wechat', ['key' => $data['secret_key']]);
            // 用*替换字符显示
            $data['appsecret'] = isset($data['appsecret']) ? string_to_star($data['appsecret']) : '';
        }

        $this->assign('data', $data);

        // 系统环境扩展检测
        // $extensions = get_loaded_extensions(); // 所有扩展信息
        $system_res = [
            ['name' => 'PHP CURL', 'support' => extension_loaded('curl') ? 'on' : 'off'],
            ['name' => 'PHP openssl', 'support' => extension_loaded('openssl') ? 'on' : 'off'],
            ['name' => 'PHP fileinfo', 'support' => extension_loaded('fileinfo') ? 'on' : 'off'],
            ['name' => 'PHP SimpleXML', 'support' => extension_loaded('SimpleXML') ? 'on' : 'off']
        ];
        $this->assign('system_res', $system_res);
        return $this->display();
    }

    /**
     * 设置公众号为默认
     */
    /*
     * public function set_default()
     * {
     * }
     */

    /**
     * 新增公众号
     */
    //public function Append()
    //{
    //}

    /**
     * 删除公众号
     */
    /*
     * public function delete()
     * {
     * }
     */

    /**
     * 公众号菜单
     */
    public function menu_list()
    {
        // 自定义菜单权限
        $this->admin_priv('menu');

        $list = $this->wechatManageService->wechatMenuList($this->wechat_id);

        $this->assign('list', $list);
        return $this->display();
    }

    /**
     * 编辑菜单
     */
    public function menu_edit()
    {
        // 自定义菜单权限
        $this->admin_priv('menu');

        if (request()->isMethod('POST')) {
            $id = request()->input('id', 0);
            $data = request()->input('data');

            $data['wechat_id'] = $this->wechat_id;
            if ('click' == $data['type']) {
                if (empty($data['key'])) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.menu_keyword') . lang('admin/wechat.empty')]);
                }
                $data['url'] = '';
            } elseif ('view' == $data['type']) {
                if (empty($data['url'])) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.menu_url') . lang('admin/wechat.empty')]);
                }
                if (substr($data['url'], 0, 4) !== 'http') {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.menu_url') . lang('admin/wechat.link_err')]);
                }
                if (strlen($data['url']) > 120) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.menu_url_length')]);
                }
                $data['key'] = '';
            } elseif ('miniprogram' == $data['type']) {
                if (strlen($data['url']) > 120) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.menu_url_length')]);
                }
                $data['key'] = '';
            }

            // 检查父级菜单数量 不能超过3个
            if (isset($data['pid']) && $data['pid'] == 0) {
                $pidCount = $this->wechatManageService->checkMenuCount($this->wechat_id, $data['pid'], $id);
                if ($pidCount >= 3) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.pid_menu_limit')]);
                }
            }
            // 检查当前所在父级菜单子菜单数量 不能超过5个
            if (isset($data['pid']) && $data['pid'] > 0) {
                $idCount = $this->wechatManageService->checkMenuCount($this->wechat_id, $data['pid'], $id);
                if ($idCount >= 5) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.id_menu_limit')]);
                }
            }

            // 编辑
            if (!empty($id)) {
                $this->wechatManageService->updateWechatMenu($id, $data);
            } else {
                // 添加
                $this->wechatManageService->createWechatMenu($data);
            }
            return response()->json(['status' => 1, 'msg' => lang('admin/wechat.menu_edit') . lang('admin/common.success')]);
        }

        $id = request()->input('id', 0);

        // 顶级菜单
        $top_menu = $this->wechatManageService->topMenuList($this->wechat_id, $id);

        // 当前菜单详情
        $info = $this->wechatManageService->wechatMenuInfo($id);

        if (empty($info)) {
            // 默认值
            $info['pid'] = 0;
            $info['status'] = 1;
            $info['sort'] = 0;
            $info['type'] = 'click';
        }

        $this->assign('top_menu', $top_menu);
        $this->assign('info', $info);
        return $this->display();
    }

    /**
     * 删除菜单
     */
    public function menu_del()
    {
        // 自定义菜单权限
        $this->admin_priv('menu');

        $id = request()->input('id', 0);
        if (empty($id)) {
            return $this->message(lang('admin/wechat.menu_select_del'), null, 2);
        }

        $menuInfo = $this->wechatManageService->wechatMenuInfo($id);

        if (empty($menuInfo)) {
            return $this->message(lang('admin/wechat.menu_not_exit'), null, 2);
        }

        // 删除顶级栏目
        if ($menuInfo['pid'] == 0) {
            $this->wechatManageService->deleteWechatMenuPid($menuInfo['id']);
        }

        $this->wechatManageService->deleteWechatMenu($menuInfo['id']);
        return $this->message(lang('admin/wechat.drop') . lang('admin/common.success'), route('admin/wechat/menu_list'));
    }

    /**
     * 生成自定义菜单
     */
    public function sys_menu()
    {
        // 自定义菜单权限
        $this->admin_priv('menu');

        // 所有显示的微信菜单
        $list = $this->wechatManageService->wechatMenuAll($this->wechat_id);

        if (empty($list)) {
            return $this->message(lang('admin/wechat.menu_empty'), null, 2);
        }
        // 转换成微信所需的数组
        /*
         * $data = array( 'button'=>array( array('type'=>'click', 'name'=>"今日歌曲", 'key'=>'MENU_KEY_MUSIC'), array('type'=>'view', 'name'=>"歌手简介", 'url'=>'http://www.qq.com/'), array('name'=>"菜单", 'sub_button'=>array(array('type'=>'click', 'name'=>'hello world', 'key'=>'MENU_KEY_MENU'))) ) );
         */
        $menu_list = $this->wechatManageService->transformWechatMenu($list);

        $rs = $this->weObj->createMenu($menu_list);
        if (empty($rs)) {
            return $this->message(lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg, null, 2);
        }
        return $this->message(lang('admin/wechat.menu_create') . lang('admin/common.success'), route('admin/wechat/menu_list'));
    }

    /**
     * 关注用户列表
     */
    public function subscribe_list()
    {
        // 粉丝管理权限
        $this->admin_priv('fans');

        // 分页
        $offset = $this->pageLimit(route('admin/wechat/subscribe_list'), $this->page_num);

        $result = $this->wechatManageService->wechatUserListSubscribe($this->wechat_id, $this->ru_id, $offset);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        // 标签列表
        $tag_list = $this->wechatManageService->getWechatTagList($this->wechat_id);

        $this->assign('tag_list', $tag_list);

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        return $this->display();
    }

    /**
     * 关注用户列表搜索
     */
    public function subscribe_search()
    {
        // 粉丝管理权限
        $this->admin_priv('fans');

        $keywords = request()->input('keywords', '');
        $group_id = request()->input('group_id', 0);
        $tag_id = request()->input('tag_id', 0);

        // 分页
        $filter['group_id'] = $group_id;
        $filter['tag_id'] = $tag_id;
        $filter['keywords'] = $keywords;
        $offset = $this->pageLimit(route('admin/wechat/subscribe_search', $filter), $this->page_num);

        // 搜索粉丝列表
        $result = $this->wechatManageService->wechatUserListSearch($this->wechat_id, $offset, $keywords, $group_id, $tag_id);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        // 标签列表
        $tag_list = $this->wechatManageService->getWechatTagList($this->wechat_id);

        $this->assign('tag_id', $tag_id);
        $this->assign('tag_list', $tag_list);

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        return $this->display('admin.wechat.subscribelist');
    }

    /**
     * 同步粉丝（直接插入数据，不能直接执行）
     */
    public function sysfans()
    {
        //微信用户
        $wechat_user = $this->weObj->getUserList();
        foreach ($wechat_user['data']['openid'] as $v) {
            $info = $this->weObj->getUserInfo($v);
            if (!empty($info)) {
                $info['wechat_id'] = $this->wechat_id;
                $this->wechatManageService->createWechatUser($info);
            }
        }
        return redirect()->route('admin/wechat/subscribe_list', ['wechat_id' => $this->wechat_id]);
    }

    /**
     * 更新用户信息
     */
    public function subscribe_update()
    {
        // 粉丝管理权限
        $this->admin_priv('fans');

        // 公众号本地所有粉丝数量
        $total = $this->wechatManageService->wechatUserCount($this->wechat_id);

        // 微信端数据 缓存
        $cache_key = md5('wechat_' . $this->wechat_id . $total);
        $wechat_user_list = cache($cache_key);
        if (is_null($wechat_user_list)) {
            $wechat_user = $this->weObj->getUserList();

            if ($wechat_user['total'] <= 10000) {
                $wechat_user_list = $wechat_user['data']['openid'];
            } else {
                $num = ceil($wechat_user['total'] / 10000);
                $wechat_user_list = $wechat_user['data']['openid'];
                for ($i = 0; $i <= $num; $i++) {
                    $wechat_user1 = $this->weObj->getUserList($wechat_user['next_openid']);
                    $wechat_user_list = array_merge($wechat_user_list, $wechat_user1['data']['openid']);
                }
            }

            cache([$cache_key => $wechat_user_list], Carbon::now()->addDays(1));
        }

        if (request()->isMethod('POST')) {
            // 分页更新本地粉丝数据
            $page = request()->input('page', 0);

            $offset = $this->pageLimit(route('admin/wechat/subscribe_update'), $this->page_num);

            // 本地数据
            $result = $this->wechatManageService->wechatUserListAll($this->wechat_id, $offset);

            $total = $result['total'] ?? 0;
            $local_user = $result['list'] ?? [];

            $user_list = [];

            if ($local_user) {
                foreach ($local_user as $v) {
                    $user_list[] = $v['openid'];
                }
            }

            // 数据对比
            if ($local_user && $wechat_user_list) {
                foreach ($local_user as $val) {
                    // 数据在微信端存在
                    if ($wechat_user_list && in_array($val['openid'], $wechat_user_list)) {
                        $info = $this->weObj->getUserInfo($val['openid']);
                        //unset($info['tagid_list']);
                        $this->wechatManageService->updateWechatUser($this->wechat_id, $val['openid'], $info);
                    } else {
                        $data['subscribe'] = 0;
                        $this->wechatManageService->updateWechatUser($this->wechat_id, $val['openid'], $data);
                    }
                }
            }

            $pager = $this->pageShow($total);

            $next_page = $page + 1;
            $totalpage = $pager['page_count'];
            $persent = intval($page / $totalpage * 100); // 进度百分比

            if ($persent == 100) // 当完成更新时操作插入新粉丝
            {
                $pull_list = $this->wechatManageService->getPullList($this->wechat_id, $wechat_user_list); // 返回待拉取列表
                $weObj = $this->weObj;
                $wechatManage = $this->wechatManageService;
                $wechat_id = $this->wechat_id;
                array_walk($pull_list, function ($value, $key) use ($wechat_id, $weObj, $wechatManage) { //处理按100条openid分组后的信息
                    $user_list = $weObj->getUserInfoBatch($value);
                    if ($user_list != false) {
                        array_walk($user_list['user_info_list'], function ($val, $k) use ($wechat_id, $wechatManage) { // 处理正确返回后的信息
                            if (!empty($val)) {
                                $val['wechat_id'] = $wechat_id;
                                $wechatManage->createWechatUser($val);
                            }
                        });
                    }
                });
            }
            return response()->json(['status' => 0, 'msg' => 'success', 'persent' => $persent, 'next_page' => $next_page, 'totalpage' => $totalpage]);
        }

        $this->assign('request_url', route('admin/wechat/subscribe_update')); // 请求URL

        $this->assign('persent', 0);
        $this->assign('page_name', lang('admin/wechat.sub_list'));
        return $this->display('admin.wechat.update');
    }

    /**
     * 发送客服消息
     */
    public function send_custom_message()
    {
        // 粉丝管理权限
        $this->admin_priv('fans');

        if (request()->isMethod('POST')) {
            $openid = request()->input('openid');

            $media_id = request()->input('media_id', 0);
            $msgtype = html_in(request()->input('msgtype', ''));

            $data = request()->input('data');

            $form = new Form();
            if (!$form->isEmpty($openid, 1)) {
                return response()->json(['status' => 1, 'msg' => lang('admin/wechat.select_openid')]);
            }

            if ($msgtype == 'text' && !$form->isEmpty($data['msg'], 1)) {
                return response()->json(['status' => 1, 'msg' => lang('admin/wechat.message_content') . lang('admin/wechat.empty')]);
            }
            if ($msgtype != 'text' && empty($media_id)) {
                return response()->json(['status' => 1, 'msg' => lang('admin/wechat.message_content') . lang('admin/wechat.empty')]);
            }

            $data['wechat_id'] = $this->wechat_id;
            $data['is_wechat_admin'] = 1; //  默认微信公众号回复标识
            $data['msgtype'] = $msgtype;

            // 微信端发送消息
            if (!empty($media_id)) {
                $mediaInfo = $this->wechatManageService->getWechatMediaInfo($media_id);
                // 图片 、语音
                if ($msgtype == 'image' || $msgtype == 'voice') {
                    // 上传多媒体文件
                    $filename = storage_public($mediaInfo['file']);

                    // 开启OSS 且本地没有图片的处理
                    if ($this->config['open_oss'] == 1 && !file_exists($filename)) {
                        $this->BatchDownloadOss(['0' => $mediaInfo['file']]);
                    }

                    $rs = $this->weObj->uploadMedia(['media' => realpath_wechat($filename)], $msgtype);
                    if (empty($rs)) {
                        logResult($this->weObj->errMsg);
                    }
                    $msg = [
                        'touser' => $openid,
                        'msgtype' => $msgtype,
                        $msgtype => [
                            'media_id' => $rs['media_id']
                        ]
                    ];
                    $data['msg'] = $msgtype == 'voice' ? lang('seller/wechat.voice') : lang('seller/common.imgage');
                } elseif ($msgtype == 'video') {
                    // 视频
                    $filename = storage_public($mediaInfo['file']);

                    // 开启OSS 且本地没有图片的处理
                    if ($this->config['open_oss'] == 1 && !file_exists($filename)) {
                        $this->BatchDownloadOss(['0' => $mediaInfo['file']]);
                    }

                    $rs = $this->weObj->uploadMedia(['media' => realpath_wechat($filename)], $msgtype);
                    if (empty($rs)) {
                        logResult($this->weObj->errMsg);
                    }
                    // 视频
                    $msg = [
                        'touser' => $openid,
                        'msgtype' => $msgtype,
                        $msgtype => [
                            'media_id' => $rs['media_id'],
                            'thumb_media_id' => $rs['media_id'],
                            'title' => $mediaInfo['title'],
                            'description' => strip_tags($mediaInfo['content'])
                        ]
                    ];
                    $data['msg'] = lang('seller/wechat.video');
                } elseif ($msgtype == 'news') {
                    // 图文素材
                    $articles = [];
                    if (!empty($mediaInfo['article_id'])) {
                        $artids = explode(',', $mediaInfo['article_id']);
                        foreach ($artids as $key => $val) {
                            $artinfo = $this->wechatManageService->getWechatMediaInfo($val);

                            $artinfo['content'] = isset($artinfo['content']) ? $this->dscRepository->subStr(strip_tags(html_out($artinfo['content'])), 100) : '';
                            $articles[$key]['title'] = isset($artinfo['title']) ? $artinfo['title'] : '';
                            $articles[$key]['description'] = empty($artinfo['digest']) ? $artinfo['content'] : $artinfo['digest'];
                            $articles[$key]['picurl'] = empty($artinfo['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($artinfo['file']);
                            $articles[$key]['url'] = empty($artinfo['link']) ? dsc_url('/#/wechatMedia/' . $artinfo['id']) : strip_tags(html_out($artinfo['link']));
                        }
                    } else {
                        $articles[0]['title'] = $mediaInfo['title'];
                        $articles[0]['description'] = empty($mediaInfo['digest']) ? $this->dscRepository->subStr(strip_tags(html_out($mediaInfo['content'])), 100) : $mediaInfo['digest'];
                        $articles[0]['picurl'] = empty($mediaInfo['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($mediaInfo['file']);
                        $articles[0]['url'] = empty($mediaInfo['link']) ? dsc_url('/#/wechatMedia/' . $mediaInfo['id']) : strip_tags(html_out($mediaInfo['link']));
                    }

                    // 图文消息（点击跳转到外链）
                    $msg = [
                        'touser' => $openid,
                        'msgtype' => 'news',
                        'news' => [
                            'articles' => $articles
                        ]
                    ];
                    $data['msg'] = lang('seller/wechat.graphic_message');
                } elseif ($msgtype == 'miniprogrampage') {
                    // 小程序
                    $msg = [
                        'touser' => $openid,
                        'msgtype' => 'miniprogrampage',
                        'miniprogrampage' => [
                            'title' => $mediaInfo['title'],
                            'appid' => 'appid',
                            'pagepath' => $mediaInfo['pagepath'],
                            'thumb_media_id' => 'thumb_media_id'
                        ]
                    ];
                    $data['msg'] = lang('seller/wechat.small_procedures');
                }
            } else {
                // 文本消息
                $data['msg'] = strip_tags(html_out($data['msg']));
                $msg = [
                    'touser' => $openid,
                    'msgtype' => 'text',
                    'text' => [
                        'content' => $data['msg']
                    ]
                ];
            }

            $rs = $this->weObj->sendCustomMessage($msg);
            if (empty($rs)) {
                $errmsg = $this->weObj->errCode . lang('seller/wechat.fail_message');
                // $errmsg = lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg;
                return response()->json(['status' => 1, 'msg' => $errmsg]);
            }
            // 添加数据
            $this->wechatManageService->createWechatCustomMessage($data);
            return response()->json(['status' => 0, 'msg' => lang('seller/common.send_success')]);
        }


        $uid = request()->input('uid', 0);
        $openid = request()->input('openid', '');

        // 粉丝信息
        $map = [
            'openid' => $openid,
            'uid' => $uid,
        ];
        $info = $this->wechatManageService->wechatUserInfo($this->wechat_id, $map);

        if ($info && !empty($info['headimgurl'])) {
            // 将大图转成64小图 0、46、64、96、132
            $n = strlen($info['headimgurl']) - strrpos($info['headimgurl'], '/') - 1;
            $info['headimgurl'] = substr($info['headimgurl'], 0, -$n) . '64';
        }

        // 最新发送的消息6条
        $condition = ['uid' => $uid];
        if ($openid && $info) {
            $condition = ['uid' => $info['uid'], 'is_wechat_admin' => 1];
        }
        $offset = [
            'start' => 0,
            'limit' => 6
        ];
        $result = $this->wechatManageService->transformWechatCustomMessageList($this->wechat_id, $offset, $condition, $info);

        $list = $result['list'] ?? [];
        // 消息总数
        $total = $result['total'] ?? 0;

        // 最新一条数据id
        $last_msg_id = 0;
        if ($list) {
            $collect = collect($list)->last();
            $last_msg_id = Arr::get($collect, 'id');
        }
        $this->assign('last_msg_id', $last_msg_id);

        $this->assign('list', $list);
        $this->assign('info', $info);
        return $this->display();
    }

    /**
     * 异步 查询消息
     * @return \Illuminate\Http\JsonResponse
     */
    public function select_custom_message(Request $request)
    {
        $start = $request->input('start', 0);
        $num = $request->input('num', 6);
        $req = $request->input('req', 0);

        $uid = $request->input('uid', 0);
        $openid = $request->input('openid', '');

        // 粉丝信息
        $map = [
            'openid' => $openid,
            'uid' => $uid,
        ];
        $info = $this->wechatManageService->wechatUserInfo($this->wechat_id, $map);

        if ($info && !empty($info['headimgurl'])) {
            // 将大图转成64小图 0、46、64、96、132
            $n = strlen($info['headimgurl']) - strrpos($info['headimgurl'], '/') - 1;
            $info['headimgurl'] = substr($info['headimgurl'], 0, -$n) . '64';
        }

        // 最新发送的消息6条
        $condition = ['uid' => $uid];
        if ($openid && $info) {
            $condition = ['uid' => $info['uid'], 'is_wechat_admin' => 1];
        }
        $offset = [
            'start' => $start,
            'limit' => $num
        ];
        $result = $this->wechatManageService->transformWechatCustomMessageList($this->wechat_id, $offset, $condition, $info);

        $list = $result['list'] ?? [];

        if ($list) {
            return response()->json(['status' => 1, 'data' => $list]);
        }

        return response()->json(['status' => 0]);
    }

    /**
     * 客服消息列表
     */
    public function custom_message_list()
    {
        // 粉丝管理权限
        $this->admin_priv('fans');

        $uid = request()->input('uid', 0);
        if (empty($uid)) {
            return $this->message(lang('admin/wechat.select_openid'), null, 2);
        }

        // 分页
        $filter['uid'] = $uid;
        $offset = $this->pageLimit(route('admin/wechat/custom_message_list', $filter), $this->page_num);

        $condition = ['uid' => $uid];
        $result = $this->wechatManageService->wechatCustomMessageList($this->wechat_id, $offset, $condition);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $list[$key]['send_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $value['send_time']);
            }
        }

        // 当前粉丝昵称
        $map = [
            'uid' => $uid,
        ];
        $info = $this->wechatManageService->wechatUserInfo($this->wechat_id, $map);
        $nickname = $info['nickname'] ?? '';

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->assign('nickname', $nickname);
        return $this->display();
    }

    /**
     * 标签管理
     */
    public function tags_list()
    {
        $tag_list = $this->wechatManageService->getWechatTagList($this->wechat_id);

        $this->assign('list', $tag_list);
        return $this->display();
    }

    /**
     * 同步标签
     */
    public function sys_tags()
    {
        // 粉丝管理权限
        $this->admin_priv('fans');

        // 获取公众号已创建的标签
        $list = $this->weObj->getTags();
        if (empty($list)) {
            return $this->message(lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg, null, 2);
        }

        // 同步标签列表
        $res = $this->wechatManageService->syncWechatUserTagList($this->wechat_id, $list);

        if ($res == true) {

            // 同步用户标签列表
            return $this->user_tag_update();
        }

        return redirect()->route('admin/wechat/subscribe_list');
    }

    /**
     * 同步用户标签
     * 通过openid 查询出标签列表 更新 wechat_user_tag 表
     * @return
     */
    public function user_tag_update()
    {
        // 粉丝管理权限
        $this->admin_priv('fans');

        if (request()->isMethod('POST')) {
            $page = request()->input('page', 0);

            $offset = $this->pageLimit(route('admin/wechat/user_tag_update'), $this->page_num);

            // 本地关注粉丝列表
            $result = $this->wechatManageService->wechatUserListSubscribe($this->wechat_id, $this->ru_id, $offset);

            $total = $result['total'] ?? 0;
            $local_user = $result['list'] ?? [];

            if (!empty($local_user)) {
                foreach ($local_user as $v) {
                    // 查询微信端粉丝标签列表
                    $rs = $this->weObj->getUserTaglist($v['openid']);
                    // 删除粉丝本地标签
                    $this->wechatManageService->deleteWechatUserTagByopenid($this->wechat_id, $v['openid']);
                    if (!empty($rs)) {
                        foreach ($rs as $key => $val) {
                            $data = [
                                'wechat_id' => $this->wechat_id,
                                'tag_id' => $val,
                                'openid' => $v['openid']
                            ];
                            $this->wechatManageService->createWechatUserTag($data);
                        }
                    }
                }
            }

            $pager = $this->pageShow($total);

            $next_page = $page + 1;
            $totalpage = $pager['page_count'];
            $persent = intval($page / $totalpage * 100); // 进度百分比

            return response()->json(['status' => 0, 'msg' => 'success', 'persent' => $persent, 'next_page' => $next_page, 'totalpage' => $totalpage]);
        }

        $this->assign('request_url', route('admin/wechat/user_tag_update')); // 请求URL

        $this->assign('persent', 0);
        $this->assign('page_name', lang('admin/wechat.user_tag'));
        return $this->display('admin.wechat.update');
    }

    /**
     * 添加、编辑标签
     */
    public function tags_edit()
    {
        // 粉丝管理权限
        $this->admin_priv('fans');

        if (request()->isMethod('POST')) {
            $name = request()->input('name');
            $id = request()->input('id', 0);
            $tag_id = request()->input('tag_id', 0);
            if (empty($name)) {
                return response()->json(['status' => 0, 'msg' => lang('admin/wechat.tag_name') . lang('admin/wechat.empty')]);
            }
            $data['name'] = $name;
            if (!empty($id)) {
                // 微信端编辑标签名称
                $rs = $this->weObj->updateTags($tag_id, $name);
                if (empty($rs)) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg]);
                }
                // 本地标签数据更新
                $data['tag_id'] = !empty($rs['tag']['id']) ? $rs['tag']['id'] : $tag_id;
                $this->wechatManageService->updateWechatUserTagList($this->wechat_id, $id, $data);
            } else {
                // 微信端新增创建标签
                $rs = $this->weObj->createTags($name);
                if (empty($rs)) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg]);
                }

                // 本地数据新增
                $data['tag_id'] = !empty($rs['tag']['id']) ? $rs['tag']['id'] : $tag_id;
                $data['name'] = $rs['tag']['name'];
                $data['wechat_id'] = $this->wechat_id;
                $this->wechatManageService->createWechatUserTagList($data);
            }
            return response()->json(['status' => 1]);
        }

        // 显示
        $id = request()->input('id', 0);

        $taginfo = [];
        if (!empty($id)) {
            $taginfo = $this->wechatManageService->getWechatUserTaglistInfo($this->wechat_id, $id);
        }

        $this->assign('taglist', $taginfo);
        return $this->display();
    }

    /**
     * 删除标签
     * @return \Illuminate\Http\JsonResponse
     */
    public function tags_delete()
    {
        // 粉丝管理权限
        $this->admin_priv('fans');

        if (request()->isMethod('POST')) {
            $id = request()->input('id', 0);

            $taginfo = $this->wechatManageService->getWechatUserTaglistInfo($this->wechat_id, $id);
            $tag_id = $taginfo['id'] ?? 0;

            if (!empty($tag_id)) {
                // 微信端编辑标签名称
                $rs = $this->weObj->deleteTags($tag_id);
                if (empty($rs)) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg]);
                }
                // 本地标签数据删除
                $this->wechatManageService->deleteWechatUserTagListByTagid($this->wechat_id, $tag_id);

                // 关联删除用户标签表
                $this->wechatManageService->deleteWechatUserTagByTagid($this->wechat_id, $tag_id);

                return response()->json(['error' => 0, 'msg' => lang('admin/wechat.tag_delete_sucess'), 'url' => route('admin/wechat/subscribe_list')]);
            } else {
                return response()->json(['error' => 1, 'msg' => lang('admin/wechat.tag_delete_fail')]);
            }
        }
    }

    /**
     * 批量为用户打标签
     */
    public function batch_tagging()
    {
        // 粉丝管理权限
        $this->admin_priv('fans');

        if (request()->isMethod('POST')) {
            $tag_id = request()->input('tag_id', 0);
            $openlist = request()->input('id');

            if (!empty($openlist) && is_array($openlist)) {
                // 批量加入标签数量一次不能超过50
                $num = count($openlist);
                if ($num >= 50) {
                    return $this->message(lang('admin/wechat.batch_tagging_limit'), route('admin/wechat/subscribe_list'), 2);
                }
                // 微信端打标签
                $rs = $this->weObj->batchtaggingTagsMembers($tag_id, $openlist);
                if (empty($rs)) {
                    return $this->message(lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg, route('admin/wechat/subscribe_list'), 2);
                }
                // 本地数据处理
                $is_true = 0;
                foreach ($openlist as $v) {

                    $user_tag_num = $this->wechatManageService->getWechatUserTagNum($this->wechat_id, $v);

                    // 每个用户最多加20个标签
                    if ($user_tag_num >= 0 && $user_tag_num < 20) {
                        // 不能重复加入相同标签
                        $data = [
                            'wechat_id' => $this->wechat_id,
                            'tag_id' => $tag_id,
                            'openid' => $v
                        ];
                        $res = $this->wechatManageService->createWechatUserTag($data);
                        if ($res == false) {
                            $is_true = 1;
                        }
                    } else {
                        $is_true = 3;
                    }

                }
                if ($is_true == 0) {
                    return $this->message(lang('admin/wechat.tag_move_sucess'), route('admin/wechat/subscribe_list'));
                } elseif ($is_true == 1) {
                    return $this->message(lang('admin/wechat.tag_move_fail') . ", " . lang('admin/wechat.tag_move_exit'), route('admin/wechat/subscribe_list'), 2);
                } elseif ($is_true == 3) {
                    return $this->message(lang('admin/wechat.tag_move_fail') . ", " . lang('admin/wechat.tag_move_three'), route('admin/wechat/subscribe_list'), 2);
                }
            } else {
                return $this->message(lang('admin/wechat.select_please'), null, 2);
            }
        }
    }

    /**
     * 批量为用户取消标签
     */
    public function batch_untagging()
    {
        // 粉丝管理权限
        $this->admin_priv('fans');

        if (request()->isMethod('POST')) {
            $tag_id = request()->input('tagid', 0);
            $openid = request()->input('openid');

            $openlist = ['0' => $openid];
            if (is_array($openlist)) {
                // 微信端取消标签
                $rs = $this->weObj->batchuntaggingTagsMembers($tag_id, $openlist);
                if (empty($rs)) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg]);
                }
                // 本地数据处理 删除标签
                foreach ($openlist as $v) {
                    $this->wechatManageService->deleteWechatUserTag($this->wechat_id, $tag_id, $v);
                }
                return response()->json(['status' => 1, 'msg' => lang('admin/wechat.tag_move_sucess')]);
            } else {
                return response()->json(['status' => 0, 'msg' => lang('admin/wechat.select_please') . lang('admin/wechat.empty')]);
            }
        }
    }

    /**
     * 修改用户备注名
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit_user_remark()
    {
        // 粉丝管理权限
        $this->admin_priv('fans');

        if (request()->isMethod('POST')) {
            $remark = html_in(request()->input('remark', ''));
            $uid = request()->input('uid', 0);

            if (!empty($remark)) {
                $condition = [
                    'uid' => $uid
                ];
                $res = $this->wechatManageService->wechatUserInfo($this->wechat_id, $condition);
                $openid = $res['openid'] ?? '';
                if (empty($openid)) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.empty')]);
                }

                $rs = $this->weObj->updateUserRemark($openid, $remark);
                if (empty($rs)) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg]);
                }

                $data = [
                    'remark' => $remark
                ];
                $this->wechatManageService->updateWechatUser($this->wechat_id, $openid, $data);
                return response()->json(['status' => 1, 'msg' => lang('admin/wechat.edit_remark_name_success')]);
            } else {
                return response()->json(['status' => 0, 'msg' => lang('admin/wechat.empty')]);
            }
        }
    }

    /**
     * 渠道二维码
     */
    public function qrcode_list()
    {
        // 二维码管理权限
        $this->admin_priv('qrcode');

        // 分页
        $offset = $this->pageLimit(route('admin/wechat/qrcode_list'), $this->page_num);

        // 渠道二维码
        $result = $this->wechatManageService->channelQrcodeList($this->wechat_id, $offset);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        return $this->display();
    }

    /**
     * 编辑二维码
     */
    public function qrcode_edit()
    {
        // 二维码管理权限
        $this->admin_priv('qrcode');

        if (request()->isMethod('POST')) {
            $data = request()->input('data');
            $data['wechat_id'] = $this->wechat_id;

            // 验证数据
            $form = new Form();
            if (!$form->isEmpty($data['function'], 1)) {
                return response()->json(['status' => 0, 'msg' => lang('admin/wechat.qrcode_function') . lang('admin/wechat.empty')]);
            }
            if (!$form->isEmpty($data['scene_id'], 1)) {
                return response()->json(['status' => 0, 'msg' => lang('admin/wechat.qrcode_scene_value') . lang('admin/wechat.empty')]);
            }
            if (!$form->isEmpty($data['expire_seconds'], 1)) {
                $data['expire_seconds'] = 1800; // 默认1800s
            } else {
                $unit = request()->input('unit', 0);
                if ($unit == 1) {
                    // 小时
                    $data['expire_seconds'] = 3600 * $data['expire_seconds'];
                } elseif ($unit == 2) {
                    // 天
                    $data['expire_seconds'] = 3600 * 24 * $data['expire_seconds'];
                } else {
                    // 分钟
                    $data['expire_seconds'] = 60 * $data['expire_seconds'];
                }
            }
            // 临时二维码
            if (isset($data['type']) && $data['type'] == 0) {
                if (!empty($data['expire_seconds']) && $data['expire_seconds'] > 2592000) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.qrcode_short_limit')]);
                }
                if ($data['scene_id'] < 100001 || $data['scene_id'] > 4294967295) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.qrcode_short_range')]);
                }
            }
            // 永久二维码
            if (isset($data['type']) && $data['type'] == 1) {
                if ($data['scene_id'] < 1 || $data['scene_id'] > 100000) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.qrcode_forever_range')]);
                }
            }

            $num = $this->wechatManageService->getWechatQrcodeCount($this->wechat_id, $data['scene_id']);
            if ($num > 0) {
                return response()->json(['status' => 0, 'msg' => lang('admin/wechat.qrcode_scene_value_limit')]);
            }
            // 添加二维码
            $this->wechatManageService->createWechatQrcode($data);
            return response()->json(['status' => 1, 'msg' => lang('admin/wechat.add') . lang('admin/common.success')]);
        }

        $id = request()->input('id', 0);
        if (!empty($id)) {
            // 更新二维码状态
            $status = request()->input('status', 0);
            $data = [
                'status' => $status
            ];
            $this->wechatManageService->updateWechatQrcode($this->wechat_id, $id, $data);
            return redirect()->route('admin/wechat/qrcode_list');
        }

        // 系统已有关键词+ 自定义关键词
        $keywords_list = $this->wechatHelperService->get_keywords_list($this->wechat_id);
        $this->assign('keywords_list', $keywords_list);

        return $this->display();
    }

    /**
     * 扫码引荐
     */
    public function share_list()
    {
        // 二维码管理权限
        $this->admin_priv('share');

        // 分页
        $offset = $this->pageLimit(route('admin/wechat/share_list'), $this->page_num);

        // 推荐二维码
        $result = $this->wechatManageService->shareQrcodeList($this->wechat_id, $offset);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        return $this->display();
    }

    /**
     * 编辑二维码
     */
    public function share_edit()
    {
        // 二维码管理权限
        $this->admin_priv('qrcode');

        if (request()->isMethod('POST')) {
            $id = request()->input('id', 0);
            $data = request()->input('data');

            $data['wechat_id'] = $this->wechat_id;

            // 验证数据
            $form = new Form();
            if (!$form->isEmpty($data['username'], 1)) {
                return response()->json(['status' => 0, 'msg' => lang('admin/wechat.share_name') . lang('admin/wechat.empty')]);
            }
            if (!$form->isEmpty($data['scene_id'], 1)) {
                return response()->json(['status' => 0, 'msg' => lang('admin/wechat.share_userid') . lang('admin/wechat.empty')]);
            }

            $data['type'] = empty($data['expire_seconds']) ? 1 : 0;

            if ($id) {
                $status = $this->wechatManageService->getWechatQrcodeStatus($this->wechat_id, $id);
                if ($status == 0) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.qrcode_islosed')]);
                }
                // 更新
                $this->wechatManageService->updateWechatQrcode($this->wechat_id, $id, $data);
                return response()->json(['status' => 1]);
            } else {
                $num = $this->wechatManageService->getWechatQrcodeCount($this->wechat_id, $data['scene_id']);
                if ($num > 0) {
                    return response()->json(['status' => 0, 'msg' => lang('admin/wechat.qrcode_scene_limit')]);
                }
                // 添加
                $this->wechatManageService->createWechatQrcode($data);
                return response()->json(['status' => 1]);
            }
        }

        $id = request()->input('id', 0);
        $info = [];
        if (!empty($id)) {
            $info = $this->wechatManageService->getWechatQrcodeInfo($this->wechat_id, $id);
        }

        $this->assign('info', $info);

        // 系统已有关键词+ 自定义关键词
        $keywords_list = $this->wechatHelperService->get_keywords_list($this->wechat_id);
        $this->assign('keywords_list', $keywords_list);

        return $this->display();
    }

    /**
     * 用户名找查会员ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_user_id()
    {
        if (request()->isMethod('POST')) {
            $username = html_in(request()->input('username', ''));
            $user_id = $this->wechatManageService->getUserIdByName($username);
            if (!empty($user_id)) {
                return response()->json(['error' => 0, 'user_id' => $user_id]);
            } else {
                return response()->json(['error' => 1, 'msg' => lang('admin/wechat.users_not_exit')]);
            }
        }
    }

    /**
     * 删除二维码
     */
    public function qrcode_del()
    {
        // 二维码管理权限
        $this->admin_priv('qrcode');

        $id = request()->input('id', 0);
        if (empty($id)) {
            return $this->message(lang('admin/wechat.select_please') . lang('admin/wechat.qrcode'), null, 2);
        }
        // 删除
        $this->wechatManageService->deleteWechatQrcode($this->wechat_id, $id);

        return $this->message(lang('admin/wechat.qrcode') . lang('admin/wechat.drop') . lang('admin/common.success'), route('admin/wechat/qrcode_list'));
    }

    /**
     * 更新并获取二维码
     */
    public function qrcode_get()
    {
        // 二维码管理权限
        $this->admin_priv('qrcode');

        $id = request()->input('id', 0);
        if (empty($id)) {
            return response()->json(['status' => 0, 'msg' => lang('admin/wechat.select_please') . lang('admin/wechat.qrcode')]);
        }

        $rs = $this->wechatManageService->getWechatQrcodeInfo($this->wechat_id, $id);
        if (empty($rs['status'])) {
            return response()->json(['status' => 0, 'msg' => lang('admin/wechat.qrcode_isdisabled')]);
        }
        if (empty($rs['qrcode_url'])) {
            // 获取二维码ticket
            if ($rs['type'] == 1 && !empty($rs['username'])) {
                $scene_id = "u=" . $rs['scene_id'];
                $ticket = $this->weObj->getQRCode($scene_id, 2, $rs['expire_seconds']); // QR_LIMIT_STR_SCENE为永久的字符串参数值
            } else {
                $ticket = $this->weObj->getQRCode((int)$rs['scene_id'], $rs['type'], $rs['expire_seconds']);
            }
            if (empty($ticket)) {
                return response()->json(['status' => 0, 'msg' => lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg]);
            }
            $data['ticket'] = $ticket['ticket'];
            $data['expire_seconds'] = $ticket['expire_seconds'] ?? null;
            $data['endtime'] = $this->timeRepository->getGmTime() + $data['expire_seconds'];
            // 微信二维码地址
            $qrcode_url = $this->weObj->getQRUrl($ticket['ticket']);
            $data['qrcode_url'] = $qrcode_url;

            // 更新
            $this->wechatManageService->updateWechatQrcode($this->wechat_id, $id, $data);
        } else {
            $qrcode_url = $rs['qrcode_url'];
        }
        // 生成短链接
        $short_url = $this->weObj->getShortUrl($qrcode_url);
        $this->assign('short_url', $short_url);

        $this->assign('qrcode_url', $qrcode_url);
        return $this->display();
    }

    /**
     * 图文回复(news)
     */
    public function article()
    {
        // 素材管理权限
        $this->admin_priv('media');

        // 分页
        $offset = $this->pageLimit(route('admin/wechat/article'), $this->page_num);

        // 图文素材 （含多图文、单图文）
        $result = $this->wechatManageService->getWechatMediaList($this->wechat_id, $offset, 'news');

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        if (!empty($list)) {
            foreach ($list as $key => $val) {
                // 多图文
                if (!empty($val['article_id'])) {
                    $article_id = explode(',', $val['article_id']);
                    foreach ($article_id as $k => $v) {
                        $media = $this->wechatManageService->getWechatMediaInfo($v);
                        $list[$key]['articles'][] = $media;
                        $list[$key]['articles'][$k]['file'] = empty($media['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($media['file']);
                    }
                }

                $list[$key]['file'] = empty($val['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($val['file']);
                $list[$key]['content'] = !empty($val['digest']) ? $val['digest'] : (!empty($val['content']) ? $this->dscRepository->subStr(strip_tags(html_out($val['content'])), 50) : '');
            }
        }

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        return $this->display();
    }

    /**
     * 图文回复编辑
     */
    public function article_edit()
    {
        // 素材管理权限
        $this->admin_priv('media');

        if (request()->isMethod('POST')) {
            $id = request()->input('id');
            $data = request()->input('data');

            $data['content'] = new_html_in(request()->input('content', ''));
            $pic_path = request()->input('file_path');

            $form = new Form();
            if (!$form->isEmpty($data['title'], 1)) {
                return $this->message(lang('admin/wechat.title') . lang('admin/wechat.empty'), null, 2);
            }
            if (!$form->isEmpty($data['content'], 1)) {
                return $this->message(lang('admin/wechat.content') . lang('admin/wechat.empty'), null, 2);
            }

            $pic_path = $this->dscRepository->editUploadImage($pic_path);

            // 封面处理
            $file = request()->file('pic');
            if ($file && $file->isValid()) {
                // 验证文件大小
                if ($file->getSize() > 2 * 1024 * 1024) {
                    return $this->message(lang('admin/wechat.file_size_limit'), null, 2);
                }
                // 验证文件格式
                if (!in_array($file->getClientMimeType(), ['image/jpeg', 'image/png'])) {
                    return $this->message(lang('admin/wechat.not_file_type'), null, 2);
                }
                $result = $this->upload('data/attached/article', true);
                if ($result['error'] > 0) {
                    return $this->message($result['message'], null, 2);
                }
                $data['file_name'] = $result['file_name'];
                $data['size'] = $result['size'];
                $data['file'] = 'data/attached/article/' . $result['file_name'];
            } else {
                $data['file'] = $pic_path;
            }

            // oss图片处理
            $file_arr = [
                'file' => $data['file'],
                'pic_path' => $pic_path,
            ];
            $file_arr = $this->dscRepository->transformOssFile($file_arr);

            $data['file'] = $file_arr['file'];
            $pic_path = $file_arr['pic_path'];

            $data['wechat_id'] = $this->wechat_id;
            $data['type'] = 'news';

            // 处理提交的素材数据
            $data = $this->wechatManageService->transformWechatMediaData($id, $data);

            if (!empty($id)) {
                // 删除原图片
                if ($data['file'] && $pic_path != $data['file']) {
                    $pic_path = strpos($pic_path, 'no_image') == false ? $pic_path : ''; // 不删除默认空图片
                    $this->remove($pic_path);
                }
                // 更新素材
                $this->wechatManageService->updateWechatMedia($this->wechat_id, $id, $data);
            } else {
                // 添加素材
                $this->wechatManageService->createWechatMedia($data);
            }
            return $this->message(lang('admin/wechat.wechat_editor') . lang('admin/common.success'), route('admin/wechat/article'));
        }

        $id = request()->input('id', 0);
        $article = ['content' => ''];
        if (!empty($id)) {
            $article = $this->wechatManageService->getWechatMediaInfo($id);
            $article['file'] = empty($article['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($article['file']);
        }

        $this->assign('article', $article);
        return $this->display();
    }

    /**
     * 图片库选择
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function gallery_album()
    {
        // 素材管理权限
        $this->admin_priv('media');

        $cache_key = md5('gallery_album' . $this->ru_id);
        $gallery_album = cache($cache_key);
        if (is_null($gallery_album)) {
            $gallery_album = $this->wechatHelperService->get_gallery_album_tree(0, $this->ru_id);

            cache([$cache_key => $gallery_album], Carbon::now()->addDays(1));
        }
        $gallery_album_id = isset($gallery_album[0]['id']) ? $gallery_album[0]['id'] : 0;

        $album_id = request()->input('album_id', 0);
        $album_id = $album_id > 0 ? $album_id : $gallery_album_id; // 首次加载排序第一的相册ID

        // 分页
        $filter['album_id'] = $album_id;
        $offset = $this->pageLimit(route('admin/wechat/gallery_album'), $this->page_num);

        $result = $this->wechatManageService->picAlbumList($album_id, $offset);

        $total = $result['total'] ?? 0;
        $pic_album_list = $result['list'] ?? [];

        $this->assign('gallery_album', $gallery_album);
        $this->assign('pic_album_list', $pic_album_list);
        $this->assign('album_id', $album_id);
        $this->assign('page', $this->pageShow($total));
        return $this->display();
    }

    /**
     * 多图文回复编辑
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function article_edit_news()
    {
        // 素材管理权限
        $this->admin_priv('media');

        if (request()->isMethod('POST')) {
            $id = request()->input('id', 0);
            $article_id = request()->input('article', '');

            if (!empty($article_id) && is_array($article_id)) {
                $data['sort'] = request()->input('sort', '');
                $data['article_id'] = implode(',', $article_id);
                $data['wechat_id'] = $this->wechat_id;
                $data['type'] = 'news';

                if (!empty($id)) {
                    // 更新素材
                    $this->wechatManageService->updateWechatMedia($this->wechat_id, $id, $data);
                } else {
                    // 添加素材
                    $this->wechatManageService->createWechatMedia($data);
                }

                return redirect()->route('admin/wechat/article');
            } else {
                return $this->message(lang('admin/wechat.please_add_again'), null, 2);
            }
        }

        $media_id = request()->input('id', 0);
        $articles = [];
        $sort = '';
        if (!empty($media_id)) {
            $mediaInfo = $this->wechatManageService->getWechatMediaInfo($media_id);

            if (!empty($mediaInfo['article_id'])) {
                $art = explode(',', $mediaInfo['article_id']);
                foreach ($art as $key => $val) {
                    $media = $this->wechatManageService->getWechatMediaInfo($val);
                    $articles[] = $media;
                    $articles[$key]['file'] = empty($articles[$key]['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($articles[$key]['file']);
                }
            }
            $sort = $mediaInfo['sort'] ?? '';
        }

        $this->assign('articles', $articles);
        $this->assign('sort', $sort);
        $this->assign('id', $media_id);
        return $this->display();
    }

    /**
     * 单图文列表供多图文选择
     */
    public function articles_list()
    {
        $ecscpCookie = request()->cookie('ECSCP');

        // 分页
        $this->page_num = isset($ecscpCookie['page_size']) && !empty($ecscpCookie['page_size']) ? $ecscpCookie['page_size'] : 4;
        $offset = $this->pageLimit(route('admin/wechat/articles_list'), $this->page_num);

        // 单图文素材列表
        $result = $this->wechatManageService->getWechatMediaArticle($this->wechat_id, $offset);

        $total = $result['total'] ?? 0;
        $article_list = $result['list'] ?? [];

        $this->assign('page', $this->pageShow($total));
        $this->assign('page_num', $this->page_num);
        $this->assign('article', $article_list);
        return $this->display();
    }

    /**
     * ajax获取多图文信息
     */
    public function get_article()
    {
        if (request()->isMethod('POST')) {
            $article_ids = request()->input('article', '');
            $article = [];
            if (is_array($article_ids)) {
                $article = $this->wechatManageService->getWechatMediaInfoByArticle($article_ids);
            }

            return response()->json($article);
        }
    }

    /**
     * 多图文回复清空
     */
    public function article_news_del()
    {
        $id = request()->input('id', 0);
        if (!empty($id)) {
            // 更新素材
            $data = [
                'article_id' => 0
            ];
            $this->wechatManageService->updateWechatMedia($this->wechat_id, $id, $data);
        }
        return redirect()->route('admin/wechat/article_edit_news');
    }

    /**
     * 图文回复删除
     */
    public function article_del()
    {
        // 素材管理权限
        $this->admin_priv('media');

        $id = request()->input('id', 0);
        if (empty($id)) {
            return $this->message(lang('admin/wechat.select_please') . lang('admin/wechat.article'), null, 2);
        }

        // 删除素材
        $result = $this->wechatManageService->deleteWechatMedia($this->wechat_id, $id);

        if (!empty($result['pic'])) {
            // 删除原图片
            $this->remove($result['pic']);
        }

        return redirect()->route('admin/wechat/article');
    }

    /**
     * 图片管理(image)
     */
    public function picture()
    {
        // 素材管理权限
        $this->admin_priv('media');

        if (request()->isMethod('POST')) {
            $file = request()->file('pic');
            if ($file && $file->isValid()) {
                // 验证文件格式
                if (!in_array($file->getClientMimeType(), ['image/jpeg', 'image/png'])) {
                    return $this->message(lang('admin/wechat.not_file_type'), null, 2);
                }
                // 验证文件大小
                if ($file->getSize() > 2 * 1024 * 1024) {
                    return $this->message(lang('admin/wechat.file_size_limit'), null, 2);
                }

                $result = $this->upload('data/attached/article', true);
                if ($result['error'] > 0) {
                    return $this->message($result['message'], route('admin/wechat/picture'), 2);
                }

                $data['file'] = 'data/attached/article/' . $result['file_name'];
                $data['file_name'] = $result['file_name'];
                $data['size'] = $result['size'];

                if (empty($data['file_name'])) {
                    return $this->message(lang('admin/wechat.please_upload'), null, 2);
                }

                // oss图片处理
                $file_arr = [
                    'file' => $data['file']
                ];
                $file_arr = $this->dscRepository->transformOssFile($file_arr);

                $data['file'] = $file_arr['file'];
                $data['type'] = 'image';
                $data['wechat_id'] = $this->wechat_id;

                // 添加图片素材
                $this->wechatManageService->createWechatMedia($data);

                return redirect()->route('admin/wechat/picture');
            }
        }

        // 分页
        $offset = $this->pageLimit(route('admin/wechat/picture'), $this->page_num);

        // 图片素材列表
        $result = $this->wechatManageService->getWechatMediaImageList($this->wechat_id, $offset);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        return $this->display();
    }

    /**
     * 语音
     */
    public function voice()
    {
        // 素材管理权限
        $this->admin_priv('media');

        if (request()->isMethod('POST')) {
            $file = request()->file('voice');
            if ($file && $file->isValid()) {
                // 验证文件格式
                if (!in_array($file->getClientMimeType(), ['audio/amr', 'audio/x-mpeg', 'audio/mp3'])) {
                    return $this->message(lang('admin/wechat.not_file_type'), null, 2);
                }
                // 验证文件大小
                if ($file->getSize() > 2 * 1024 * 1024) {
                    return $this->message(lang('admin/wechat.file_size_limit'), null, 2);
                }

                $result = $this->upload('data/attached/voice', true);
                if ($result['error'] > 0) {
                    return $this->message($result['message'], route('admin/wechat/voice'), 2);
                }

                $data['file'] = 'data/attached/voice/' . $result['file_name'];
                $data['file_name'] = $result['file_name'];
                $data['size'] = $result['size'];

                if (empty($data['file_name'])) {
                    return $this->message(lang('admin/wechat.please_upload'), null, 2);
                }

                // oss图片处理
                $file_arr = [
                    'file' => $data['file']
                ];
                $file_arr = $this->dscRepository->transformOssFile($file_arr);

                $data['file'] = $file_arr['file'];
                $data['type'] = 'voice';
                $data['wechat_id'] = $this->wechat_id;

                // 添加语音素材
                $this->wechatManageService->createWechatMedia($data);

                return redirect()->route('admin/wechat/voice');
            }
        }

        // 分页
        $offset = $this->pageLimit(route('admin/wechat/voice'), $this->page_num);

        // 语音素材列表
        $result = $this->wechatManageService->getWechatMediaVoiceList($this->wechat_id, $offset);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        return $this->display();
    }

    /**
     * 视频
     */
    public function video()
    {
        // 素材管理权限
        $this->admin_priv('media');

        // 分页
        $offset = $this->pageLimit(route('admin/wechat/video'), $this->page_num);

        // 视频素材列表
        $result = $this->wechatManageService->getWechatMediaVideoList($this->wechat_id, $offset);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        return $this->display();
    }

    /**
     * 视频编辑
     */
    public function video_edit()
    {
        // 素材管理权限
        $this->admin_priv('media');

        if (request()->isMethod('POST')) {
            $id = request()->input('id');
            $data = request()->input('data');

            if (empty($data['file']) || empty($data['file_name']) || empty($data['size'])) {
                return $this->message(lang('admin/wechat.video_empty'), null, 2);
            }
            $size = round(($data['size'] / (1024 * 1024)), 1);
            if ($size > 2) {
                return $this->message(lang('admin/wechat.file_size_limit'), null, 2);
            }
            if (empty($data['title'])) {
                return $this->message(lang('admin/wechat.title') . lang('admin/wechat.empty'), null, 2);
            }
            $data['type'] = 'video';
            $data['wechat_id'] = $this->wechat_id;

            if (!empty($id)) {
                // 更新素材
                $this->wechatManageService->updateWechatMedia($this->wechat_id, $id, $data);
            } else {
                // 添加素材
                $this->wechatManageService->createWechatMedia($data);
            }

            return $this->message(lang('admin/wechat.upload_video') . lang('admin/common.success'), route('admin/wechat/video'));
        }

        $id = request()->input('id', 0);
        $video = [];
        if (!empty($id)) {
            $video = $this->wechatManageService->getWechatMediaInfo($id);
        }

        $this->assign('video', $video);
        return $this->display();
    }

    /**
     * 视频上传webuploader
     */
    public function video_upload()
    {
        if (request()->isMethod('POST')) {
            $vid = request()->input('vid');
            $file = request()->file('file');
            if ($file && $file->isValid()) {
                // 验证文件大小
                if ($file->getSize() > 2 * 1024 * 1024) {
                    return response()->json(['errcode' => 2, 'errmsg' => lang('admin/wechat.file_size_limit')]);
                }
                // 删除原素材
                if (!empty($vid)) {
                    $result = $this->wechatManageService->getWechatMediaInfo($vid);
                    $file = $result['file'] ?? '';
                    $this->remove($file);
                }
                $result = $this->upload('data/attached/video', true);
                if ($result['error'] > 0) {
                    $data['errcode'] = 1;
                    $data['errmsg'] = $result['message'];
                    return response()->json($data);
                }
                $data['errcode'] = 0;

                $data['file'] = 'data/attached/video/' . $result['file_name'];
                $data['file_name'] = $result['file_name'];
                $data['size'] = $result['size'];

                if (empty($data['file_name'])) {
                    $data['errcode'] = 1;
                    $data['errmsg'] = lang('admin/wechat.please_upload');
                    return response()->json($data);
                }

                // oss图片处理
                $file_arr = [
                    'file' => $data['file']
                ];
                $file_arr = $this->dscRepository->transformOssFile($file_arr);

                $data['file'] = $file_arr['file'];

                return response()->json($data);
            }
        }
    }

    /**
     * 素材编辑
     */
    public function media_edit()
    {
        // 素材管理权限
        $this->admin_priv('media');

        if (request()->isMethod('POST')) {
            $id = request()->input('id');
            $pic_name = request()->input('file_name');

            $form = new Form();
            if (!$form->isEmpty($id, 1)) {
                return $this->message(lang('admin/wechat.empty'), null, 2);
            }
            if (!$form->isEmpty($pic_name, 1)) {
                return $this->message(lang('admin/wechat.empty'), null, 2);
            }

            $data['file_name'] = $pic_name;

            $num = $this->wechatManageService->updateWechatMedia($this->wechat_id, $id, $data);

            return response()->json(['status' => $num]);
        }

        $id = request()->input('id', 0);

        $pic = $this->wechatManageService->getWechatMediaInfo($id);

        $this->assign('pic', $pic);
        return $this->display();
    }

    /**
     * 素材删除
     */
    public function media_del()
    {
        // 素材管理权限
        $this->admin_priv('media');

        $id = request()->input('id', 0);
        if (empty($id)) {
            return $this->message(lang('admin/wechat.empty'), null, 2);
        }

        // 删除素材
        $result = $this->wechatManageService->deleteWechatMedia($this->wechat_id, $id);

        if (!empty($result['pic'])) {
            // 删除原图片
            $this->remove($result['pic']);
        }
        if (!empty($result['thumb'])) {
            // 删除原缩略图片
            $this->remove($result['thumb']);
        }

        return redirect()->back();
    }

    /**
     * 下载
     */
    public function download()
    {
        $id = request()->input('id', 0);

        $pic = $this->wechatManageService->getWechatMediaInfo($id);

        if (empty($pic)) {
            return $this->message(lang('admin/wechat.file_not_exist'), null, 2);
        }

        $file = $pic['file'];
        $filename = storage_public($file);

        // 开启OSS 且本地没有图片的处理
        if ($this->config['open_oss'] == 1 && !file_exists($filename)) {
            $this->BatchDownloadOss(['0' => $file]);
        }
        // 文件存在 则下载
        if (file_exists($filename)) {
            return $this->file_download($file);
        } else {
            return $this->message(lang('admin/wechat.file_not_exist'), null, 2);
        }
    }

    /**
     * 群发消息列表
     */
    public function mass_list()
    {
        // 群发消息权限
        $this->admin_priv('mass_message');

        // 分页
        $offset = $this->pageLimit(route('admin/wechat/mass_list'), $this->page_num);

        // 群发消息列表
        $result = $this->wechatManageService->wechatMassHistoryList($this->wechat_id, $offset);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        if ($list) {
            $list = $this->wechatManageService->transformWechatMassHistoryList($list);
        }

        $this->assign('list', $list);
        $this->assign('page', $this->pageShow($total));
        return $this->display();
    }

    /**
     * 群发消息
     */
    public function mass_message()
    {
        // 群发消息权限
        $this->admin_priv('mass_message');

        if (request()->isMethod('POST')) {
            $tag_id = request()->input('tag_id', 0);
            $media_id = request()->input('media_id', 0);

            if (empty($tag_id) || $tag_id == 0 || empty($media_id)) {
                return $this->message(lang('admin/wechat.please_select_massage'), null, 2);
            }

            $article = [];

            $article_info = $this->wechatManageService->getWechatMediaInfo($media_id);
            // 多图文
            if (!empty($article_info['article_id'])) {
                $articles = explode(',', $article_info['article_id']);
                foreach ($articles as $key => $val) {
                    $artinfo = $this->wechatManageService->getWechatMediaInfo($val);

                    // 上传多媒体文件
                    $filename = storage_public($artinfo['file']);

                    // 开启OSS 且本地没有图片的处理
                    if ($this->config['open_oss'] == 1 && !file_exists($filename)) {
                        $this->BatchDownloadOss(['0' => $artinfo['file']]);
                    }

                    $rs = $this->weObj->uploadMedia(['media' => realpath_wechat($filename)], 'image');
                    if (empty($rs)) {
                        return $this->message(lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg, null, 2);
                    }

                    // 重组数据
                    $article[$key]['thumb_media_id'] = $rs['media_id'];
                    $article[$key]['author'] = $artinfo['author'];
                    $article[$key]['title'] = $artinfo['title'];
                    $article[$key]['content_source_url'] = empty($artinfo['link']) ? url('mobile/#/wechatMedia/' . $artinfo['id']) : strip_tags(html_out($artinfo['link']));
                    $article[$key]['content'] = $this->uploadMassMessageContentImg($artinfo['content']);
                    $article[$key]['digest'] = $artinfo['digest'];
                    $article[$key]['show_cover_pic'] = $artinfo['is_show'];
                }
            } else {
                // 单图文
                // 上传多媒体文件
                $filename = storage_public($article_info['file']);

                // 开启OSS 且本地没有图片的处理
                if ($this->config['open_oss'] == 1 && !file_exists($filename)) {
                    $this->BatchDownloadOss(['0' => $article_info['file']]);
                }

                $rs = $this->weObj->uploadMedia(['media' => realpath_wechat($filename)], 'image');
                if (empty($rs)) {
                    return $this->message(lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg, null, 2);
                }
                // 重组数据
                $article[0]['thumb_media_id'] = $rs['media_id'];
                $article[0]['author'] = $article_info['author'];
                $article[0]['title'] = $article_info['title'];
                $article[0]['content_source_url'] = empty($article_info['link']) ? url('mobile/#/wechatMedia/' . $article_info['id']) : strip_tags(html_out($article_info['link']));
                $article[0]['content'] = $this->uploadMassMessageContentImg($article_info['content']);
                $article[0]['digest'] = $article_info['digest'];
                $article[0]['show_cover_pic'] = $article_info['is_show'];
            }
            $article_list = ['articles' => $article];
            // 图文消息上传
            $rs1 = $this->weObj->uploadArticles($article_list);
            if (empty($rs1)) {
                return $this->message(lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg, null, 2);
            }
            // $rs1 = array('type'=>'image', 'media_id'=>'joUuDBc-9-sJp1U6vZpWYKiaS5XskqxJxGMm5HBf9q9Zs7DoKlSXVKUR3JIsfW_7', 'created_at'=>'1407482934');

            /**
             * 根据标签组进行群发sendGroupMassMessage
             * 群发接口新增原创校验流程
             * 当 send_ignore_reprint 参数设置为1时，文章被判定为转载时，将继续进行群发操作。
             * 当 send_ignore_reprint 参数设置为0时，文章被判定为转载时，将停止群发操作。
             * send_ignore_reprint 默认为0。
             * clientmsgid  群发接口新增 clientmsgid 参数，开发者调用群发接口时可以主动设置，避免重复推送。
             *
             */
            // 最新群发消息信息
            $massInfo = $this->wechatManageService->getWechatMassHistoryInfoLast($this->wechat_id);
            $mass_id = $massInfo['id'] ?? 0;
            $clientmsgid = !empty($mass_id) ? $mass_id + 1 : 0;
            $massmsg = [
                'filter' => [
                    'is_to_all' => false,
                    'tag_id' => $tag_id
                ],
                'mpnews' => [
                    'media_id' => $rs1['media_id']
                ],
                'msgtype' => 'mpnews',
                'send_ignore_reprint' => 0,
                'clientmsgid' => $clientmsgid
            ];
            $rs2 = $this->weObj->sendGroupMassMessage($massmsg);
            if (empty($rs2)) {
                return $this->message(lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg, null, 2);
            }

            $time = $this->timeRepository->getGmTime();

            // 数据处理
            $msg_data['wechat_id'] = $this->wechat_id;
            $msg_data['media_id'] = $article_info['id'];
            $msg_data['type'] = $article_info['type'];
            $msg_data['send_time'] = $time;
            $msg_data['msg_id'] = $rs2['msg_id'];

            $this->wechatManageService->createWechatMassHistory($msg_data);

            return $this->message(lang('admin/wechat.mass_sending_wait'), route('admin/wechat/mass_message'));
        }


        // 标签组信息
        $tags = $this->wechatManageService->getWechatTagList($this->wechat_id);

        // 图文信息
        $offset = $this->pageLimit(route('admin/wechat/mass_message'), $this->page_num);

        // 所有图文（含多图文、单图文）
        $result = $this->wechatManageService->getWechatMediaList($this->wechat_id, $offset);

        $total = $result['total'] ?? 0;
        $article = $result['list'] ?? [];

        if ($article) {
            foreach ($article as $key => $val) {
                if (!empty($val['article_id'])) {
                    $articles = explode(',', $val['article_id']);
                    foreach ($articles as $k => $v) {
                        $media = $this->wechatManageService->getWechatMediaInfo($v);
                        $article[$key]['articles'][] = $media;
                        $article[$key]['articles'][$k]['file'] = empty($media['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($media['file']);
                    }
                }
                // 多图文的子图文不重复处理
                if (empty($val['article_id'])) {
                    $article[$key]['file'] = empty($val['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($val['file']);
                }
                $article[$key]['content'] = empty($val['digest']) ? (empty($val['content']) ? '' : $this->dscRepository->subStr(strip_tags(html_out($val['content'])), 50)) : $val['digest'];
            }
        }

        $this->assign('tags', $tags);
        $this->assign('article', $article);
        return $this->display();
    }

    /**
     * 群发消息 内容上传图片（不是封面上传）
     * @param string $content
     * @return
     */
    public function uploadMassMessageContentImg($content = '')
    {
        $content = html_out($content);
        $pattern = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png|\.bmp|\.jpeg]))[\'|\"].*?[\/]?>/";
        preg_match_all($pattern, $content, $match);
        if (count($match[1]) > 0) {
            foreach ($match[1] as $img) {
                // 本地远程图片
                if (strtolower(substr($img, 0, 4)) == 'http') {
                    $img = str_replace(url('/'), '', $img);
                }
                $filename = storage_public($img);
                if (file_exists($filename)) {
                    $rs = $this->weObj->uploadImg(['media' => realpath_wechat($filename)], 'image');
                    if (!empty($rs)) {
                        $replace = $rs['url'];// http://mmbiz.qpic.cn/mmbiz/gLO17UPS6FS2xsypf378iaNhWacZ1G1UplZYWEYfwvuU6Ont96b1roYs CNFwaRrSaKTPCUdBK9DgEHicsKwWCBRQ/0
                        $content = str_replace($img, $replace, $content);
                    }
                }
            }
        }

        return $content;
    }

    /**
     * 群发消息删除
     */
    public function mass_del()
    {
        // 群发消息权限
        $this->admin_priv('mass_message');

        $id = request()->input('id', 0);

        $model = WechatMassHistory::where(['id' => $id, 'wechat_id' => $this->wechat_id]);

        $msg_id = $model->value('msg_id');
        if (empty($msg_id)) {
            return $this->message(lang('admin/wechat.massage_not_exist'), null, 2);
        }
        // 删除群发接口新增 article_idx 参数, 默认 0 删除全部文章
        $delmass = [
            'msg_id' => $msg_id,
            'article_idx' => 0
        ];
        $rs = $this->weObj->deleteMassMessage($delmass);
        if (empty($rs)) {
            return $this->message(lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg, null, 2);
        }

        $data['status'] = 'send success(delete)';
        $model->update($data);

        return redirect()->route('admin/wechat/mass_list');
    }

    /**
     * 自动回复
     */
    public function auto_reply()
    {
        // 自动回复权限
        $this->admin_priv('auto_reply');

        // 素材数据
        $type = request()->input('type');
        $reply_id = request()->input('reply_id', 0);
        $result = [];
        if (!empty($type)) {
            // 分页
            $filter['type'] = $type;
            $filter['reply_id'] = $reply_id;
            $offset = $this->pageLimit(route('admin/wechat/auto_reply', $filter), $this->page_num);

            if ('image' == $type) {
                $result = $this->wechatManageService->getWechatMediaImageList($this->wechat_id, $offset);
            } elseif ('voice' == $type) {
                $result = $this->wechatManageService->getWechatMediaVoiceList($this->wechat_id, $offset);
            } elseif ('video' == $type) {
                $result = $this->wechatManageService->getWechatMediaVideoList($this->wechat_id, $offset);
            } elseif ('news' == $type) {

                $no_list = request()->input('no_list', 0);
                $this->assign('no_list', $no_list);

                if (!empty($no_list)) {
                    // 只显示单图文
                    $result = $this->wechatManageService->getWechatMediaArticle($this->wechat_id, $offset);
                } else {
                    // 所有图文（含多图文、单图文）
                    $result = $this->wechatManageService->getWechatMediaList($this->wechat_id, $offset, 'news');
                }

                if (!empty($result['list'])) {
                    foreach ($result['list'] as $key => $val) {
                        if (!empty($val['article_id'])) {
                            $id = explode(',', $val['article_id']);
                            foreach ($id as $k => $v) {
                                $media = $this->wechatManageService->getWechatMediaInfo($v);
                                $result['list'][$key]['articles'][] = $media;
                                $result['list'][$key]['articles'][$k]['file'] = empty($media['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($media['file']);
                            }
                        }
                        $result['list'][$key]['content'] = empty($val['digest']) ? (empty($val['content']) ? '' : $this->dscRepository->subStr(strip_tags(html_out($val['content'])), 50)) : $val['digest'];
                        // 多图文的子图文不重复处理
                        if (empty($val['article_id'])) {
                            $result['list'][$key]['file'] = empty($val['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($val['file']);
                        }
                    }
                }
            }

            $total = $result['total'] ?? 0;
            $list = $result['list'] ?? [];

            $this->assign('reply_id', $reply_id);
            $this->assign('page', $this->pageShow($total));
            $this->assign('list', $list);
            $this->assign('type', $type);
            return $this->display();
        }
    }

    /**
     * 关注回复(subscribe)
     */
    public function reply_subscribe()
    {
        // 自动回复权限
        $this->admin_priv('auto_reply');

        if (request()->isMethod('POST')) {
            $content_type = request()->input('content_type', '');
            if ($content_type == 'text') {
                $content = request()->input('content', '');
                $data['content'] = new_html_in($content);
                $data['media_id'] = 0;
            } else {
                $data['media_id'] = request()->input('media_id', 0);
                $data['content'] = '';
            }

            // 验证
            if ($content_type == 'text' && empty($data['content'])) {
                return $this->message(lang('admin/wechat.empty'), null, 2);
            } else {
                if ($content_type != 'text' && empty($data['media_id'])) {
                    return $this->message(lang('admin/wechat.empty'), null, 2);
                }
            }

            $id = request()->input('id', 0);

            $data['type'] = 'subscribe';
            $data['wechat_id'] = $this->wechat_id;

            if (!empty($id)) {
                $this->wechatManageService->updateWechatReply($this->wechat_id, $id, $data);
            } else {
                $this->wechatManageService->createWechatReply($data);
            }

            return $this->message(lang('admin/wechat.wechat_editor') . lang('admin/common.success'), route('admin/wechat/reply_subscribe'));
        }
        // 关注自动回复数据
        $subscribe = $this->wechatManageService->getWechatReply($this->wechat_id, 'subscribe');

        $subscribe['media'] = [];
        if (!empty($subscribe['media_id'])) {
            $media = $this->wechatManageService->getWechatMediaInfo($subscribe['media_id']);
            $subscribe['media'] = $media;
            $subscribe['media']['file'] = empty($media['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($media['file']);
        }

        $this->assign('subscribe', $subscribe);
        return $this->display();
    }

    /**
     * 消息回复(msg)
     */
    public function reply_msg()
    {
        // 自动回复权限
        $this->admin_priv('auto_reply');

        if (request()->isMethod('POST')) {
            $content_type = request()->input('content_type', '');
            if ($content_type == 'text') {
                $data['content'] = new_html_in(request()->input('content'));
                $data['media_id'] = 0;
            } else {
                $data['media_id'] = request()->input('media_id', 0);
                $data['content'] = '';
            }

            // 验证
            if ($content_type != 'text' && empty($data['media_id'])) {
                return $this->message(lang('admin/wechat.empty'), null, 2);
            }

            $id = request()->input('id', 0);

            $data['type'] = 'msg';
            $data['wechat_id'] = $this->wechat_id;

            if (!empty($id)) {
                $this->wechatManageService->updateWechatReply($this->wechat_id, $id, $data);
            } else {
                $this->wechatManageService->createWechatReply($data);
            }

            return $this->message(lang('admin/wechat.wechat_editor') . lang('admin/common.success'), route('admin/wechat/reply_msg'));

        }
        // 消息自动回复数据
        $msg = $this->wechatManageService->getWechatReply($this->wechat_id, 'msg');

        $msg['media'] = [];
        if (!empty($msg['media_id'])) {
            $media = $this->wechatManageService->getWechatMediaInfo($msg['media_id']);
            $msg['media'] = $media;
            $msg['media']['file'] = empty($media['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($media['file']);
        }

        $this->assign('msg', $msg);
        return $this->display();
    }

    /**
     * 关键词自动回复
     */
    public function reply_keywords()
    {
        // 自动回复权限
        $this->admin_priv('auto_reply');

        // 关键词自动回复列表
        $list = $this->wechatManageService->wechatReplyKeywordsList($this->wechat_id);

        $this->assign('list', $list);
        return $this->display();
    }

    /**
     * 关键词回复添加规则
     */
    public function rule_edit()
    {
        if (request()->isMethod('POST')) {
            $id = request()->input('id', 0);
            $content_type = request()->input('content_type', '');
            $rule_keywords = request()->input('rule_keywords', '');

            // 主表数据
            $data['rule_name'] = html_in(request()->input('rule_name', ''));
            $data['media_id'] = request()->input('media_id', 0);
            $data['content'] = new_html_in(request()->input('content', ''));
            $data['reply_type'] = $content_type;

            if ($content_type == 'text') {
                $data['media_id'] = 0;
            } else {
                $data['content'] = '';
            }

            $form = new Form();
            if (!$form->isEmpty($data['rule_name'], 1)) {
                return $this->message(lang('admin/wechat.rule_name_empty'), null, 2);
            }
            if (!$form->isEmpty($rule_keywords, 1)) {
                return $this->message(lang('admin/wechat.rule_keywords_empty'), null, 2);
            }
            if (empty($data['content']) && empty($data['media_id'])) {
                return $this->message(lang('admin/wechat.rule_content_empty'), null, 2);
            }
            if (strlen($data['rule_name']) > 60) {
                return $this->message(lang('admin/wechat.rule_name_length_limit'), null, 2);
            }

            // 验证关键词是否重复
            $rule_keywords = explode(',', $rule_keywords);
            foreach ($rule_keywords as $val) {
                // 编辑验证
                $model = WechatRuleKeywords::where('wechat_id', $this->wechat_id)
                    ->where('rule_keywords', $val);
                if ($id) {
                    $model = $model->where('rid', '<>', $id);
                }
                $count = $model->count();
                if ($count >= 1) {
                    return $this->message(lang('admin/wechat.rule_keywords_exit', ['val' => $val]), null, 2);
                }
            }

            $data['type'] = 'keywords';
            if (!empty($id)) {
                $this->wechatManageService->updateWechatReply($this->wechat_id, $id, $data);
                // 删除关键词规则表
                $this->wechatManageService->deleteWechatRuleKeywords($this->wechat_id, $id);
            } else {
                $time = $this->timeRepository->getGmTime();

                $data['add_time'] = $time;
                $data['wechat_id'] = $this->wechat_id;
                $id = WechatReply::insertGetId($data);
            }
            // 编辑关键词
            if (isset($id) && !empty($rule_keywords)) {
                // $rule_keywords = explode(',', $rule_keywords);
                foreach ($rule_keywords as $val) {
                    $kdata['rid'] = $id;
                    $kdata['rule_keywords'] = $val;
                    $kdata['wechat_id'] = $this->wechat_id;
                    WechatRuleKeywords::create($kdata);
                }
            }

            return $this->message(lang('admin/wechat.wechat_editor') . lang('admin/common.success'), route('admin/wechat/reply_keywords'));
        }
    }

    /**
     * 关键词回复规则删除
     */
    public function reply_del()
    {
        // 自动回复权限
        $this->admin_priv('auto_reply');

        $id = request()->input('id', 0);

        if (empty($id)) {
            return $this->message(lang('admin/wechat.empty'), null, 2);
        }

        $this->wechatManageService->deleteWechatReplyKeywords($this->wechat_id, $id);

        return redirect()->route('admin/wechat/reply_keywords');
    }

    /**
     * 素材管理
     */
    public function media_list()
    {
        return $this->display();
    }

    /**
     * 模板消息
     */
    public function template()
    {
        // 模板消息权限
        $this->admin_priv('template');

        if (request()->isMethod('POST')) {
            $data = request()->input('data');
            array_walk_recursive($data, function (&$val, $key) {
                $val = is_null($val) ? '' : $val;
            });
            if (empty($data['primary_industry']) || empty($data['secondary_industry'])) {
                return $this->message(lang('admin/wechat.please_select_industry'), null, 2);
            }

            $res = $this->weObj->setTMIndustry($data['primary_industry'], $data['secondary_industry']);
            if (empty($res)) {
                return $this->message(lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg, null, 2);
            }
            Storage::disk('public')->delete('wechat/TMIndustry.php');
            return $this->message(lang('admin/wechat.wechat_editor') . lang('admin/common.success'), null);
        }

        // 模板消息列表
        $list = $this->wechatManageService->getWechatTemplateList($this->wechat_id);

        // 显示所在行业
        $industry = Storage::disk('public')->exists('wechat/TMIndustry.php');
        if ($industry === false) {
            $industry = $this->weObj->getTMIndustry();
            if (empty($industry)) {
                $error = lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg;
                $this->assign('error', lang('admin/wechat.please_apply_template'));
            } else {
                Storage::disk('public')->put('wechat/TMIndustry.php', serialize($industry));
            }
        } else {
            $industry = Storage::disk('public')->get('wechat/TMIndustry.php');
            $industry = unserialize($industry);
        }
        $this->assign('industry', $industry);

        $this->assign('list', $list);
        return $this->display();
    }

    /**
     * 编辑模板消息
     */
    public function edit_template()
    {
        // 模板消息权限
        $this->admin_priv('template');

        if (request()->isMethod('POST')) {
            $id = request()->input('id', 0);
            $data = request()->input('data');
            if ($id) {
                // 更新
                $this->wechatManageService->updateWechatTemplate($this->wechat_id, $id, $data);
                return response()->json(['status' => 1]);
            } else {
                return response()->json(['status' => 0, 'msg' => lang('admin/wechat.template_edit_fail')]);
            }
        }

        $id = request()->input('id', 0);
        $template = [];
        if ($id) {
            $template = $this->wechatManageService->getWechatTemplateInfo($this->wechat_id, $id);
        }

        $this->assign('template', $template);
        return $this->display();
    }

    /**
     * 启用或禁止模板消息
     */
    public function switch_template()
    {
        // 模板消息权限
        $this->admin_priv('template');

        $id = request()->input('id', 0);
        $status = request()->input('status', 0);
        if (empty($id)) {
            return $this->message(lang('admin/wechat.empty'), null, 2);
        }

        $data = [];
        // 启用模板消息
        if ($status == 1) {
            $template = $this->wechatManageService->getWechatTemplateInfo($this->wechat_id, $id);

            if (empty($template['template_id'])) {
                $template_id = $this->weObj->addTemplateMessage($template['code']);
                // 已经存在模板ID
                if ($template_id) {
                    $data['template_id'] = $template_id;
                    $this->wechatManageService->updateWechatTemplate($this->wechat_id, $id, $data);
                } else {
                    return $this->message($this->weObj->errMsg, null, 2);
                }
            }
            // 重新启用 更新状态status
            $data['status'] = 1;
            $this->wechatManageService->updateWechatTemplate($this->wechat_id, $id, $data);
        } else {
            // 禁用 更新状态status
            $data['status'] = 0;
            $this->wechatManageService->updateWechatTemplate($this->wechat_id, $id, $data);
        }

        return redirect()->route('admin/wechat/template');
    }

    /**
     * 重置模板消息
     * @return
     */
    public function reset_template()
    {
        // 模板消息权限
        $this->admin_priv('template');

        if (request()->isMethod('POST')) {
            $json_result = ['error' => 0, 'msg' => '', 'url' => ''];

            $id = request()->input('id', 0);
            if (!empty($id)) {
                $template = $this->wechatManageService->getWechatTemplateInfo($this->wechat_id, $id);

                if (!empty($template['template_id'])) {
                    $rs = $this->weObj->delTemplate($template['template_id']);
                    // 本地
                    $data = ['template_id' => '', 'status' => 0];
                    $this->wechatManageService->updateWechatTemplate($this->wechat_id, $id, $data);
                    if (empty($rs)) {
                        $json_result['msg'] = lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg;
                        return response()->json($json_result);
                    }
                }
                $json_result['msg'] = lang('admin/common.reset') . lang('admin/common.success');
                return response()->json($json_result);
            }
            $json_result['error'] = 1;
            $json_result['msg'] = lang('admin/common.reset') . lang('admin/common.fail');
            return response()->json($json_result);
        }
    }

    /**
     * 微信JSSDK分享统计
     * @return
     */
    public function share_count()
    {
        // 粉丝管理权限
        $this->admin_priv('fans');

        $keywords = html_in(request()->input('keywords', ''));

        // 分页
        $offset = $this->pageLimit(route('admin/wechat/share_count'), $this->page_num);

        $result = $this->wechatManageService->getWechatShareCountList($this->wechat_id, $offset, $keywords);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        return $this->display();
    }

    /**
     * 删除分享记录
     * @return
     */
    public function share_count_delete()
    {
        // 粉丝管理权限
        $this->admin_priv('fans');

        $id = request()->input('id', 0);
        if (empty($id)) {
            return $this->message(lang('admin/wechat.empty'), null, 2);
        }

        $this->wechatManageService->deleteWechatShareCount($this->wechat_id, $id);

        return $this->message(lang('admin/wechat.drop') . lang('admin/common.success'), route('admin/wechat/share_count'));
    }

    /**
     * 功能扩展
     */
    public function extend_index()
    {
        // 功能扩展管理权限
        $this->admin_priv('extend');

        // 功能扩展列表
        $plugins = $this->wechatManageService->wechatExtendList($this->wechat_id, $this->wechat_type);

        $this->assign('plugins', $plugins);
        return $this->display();
    }

    /**
     * 功能扩展安装/编辑
     */
    public function extend_edit()
    {
        // 功能扩展管理权限
        $this->admin_priv('extend');

        if (request()->isMethod('POST')) {
            $handler = request()->input('handler', '');
            $cfg_value = request()->input('cfg_value', '');
            $data = request()->input('data', '');

            if (empty($data['keywords'])) {
                return $this->message(lang('admin/wechat.must_keywords'), null, 2);
            }

            $data['type'] = 'function';
            $data['wechat_id'] = $this->wechat_id;

            $this->plugin_name = !empty($this->plugin_name) ? $this->plugin_name : $data['command'];

            if (!empty($this->plugin_name)) {

                // 数据库是否存在该数据
                $rs = $this->wechatManageService->wechatExtendInfo($this->wechat_id, $this->plugin_name);

                // 格式化处理功能扩展安装信息
                $cfg_value = $this->wechatManageService->transformWechatExtendCfgValue($cfg_value);

                if (!empty($rs)) {
                    // 已安装
                    if (empty($handler) && !empty($rs['enable'])) {
                        return $this->message(lang('admin/wechat.extend_is_enabled'), null, 2);
                    } else {

                        // 缺少素材
                        if (empty($cfg_value['media_id'])) {
                            $media_id = WechatMedia::where(['command' => $this->plugin_name, 'wechat_id' => $this->wechat_id])->value('id');
                            if ($media_id) {
                                $cfg_value['media_id'] = $media_id;
                            } else {
                                // 安装素材
                                $media_id = $this->wechatManageService->installWechatMediaForExtend($this->wechat_id, $this->plugin_name);
                                if (!empty($media_id)) {
                                    $cfg_value['media_id'] = $media_id;
                                }
                            }
                        }

                        $data['config'] = serialize($cfg_value);
                        $data['enable'] = 1;
                        // 更新
                        $this->wechatManageService->updateWechatExtend($this->wechat_id, $data['command'], $data);

                        // 提示信息
                        $msg = lang('admin/common.editor');
                    }
                } else {

                    // 安装素材
                    $media_id = $this->wechatManageService->installWechatMediaForExtend($this->wechat_id, $this->plugin_name);
                    if (!empty($media_id)) {
                        $cfg_value['media_id'] = $media_id;
                    }

                    $data['config'] = serialize($cfg_value);
                    $data['enable'] = 1;

                    // 添加
                    $this->wechatManageService->createWechatExtend($data);

                    // 提示信息
                    $msg = lang('admin/common.install');
                }

                return $this->message($msg . lang('admin/common.success'), route('admin/wechat/extend_index'));
            }

            return $this->message(lang('admin/common.install') . lang('admin/common.editor') . lang('admin/common.fail'), route('admin/wechat/extend_index'));
        }

        $handler = html_in(request()->input('handler', ''));

        $info = [];
        // 编辑操作
        if (!empty($handler)) {
            // 获取配置信息
            $info = $this->wechatManageService->wechatExtendInfo($this->wechat_id, $this->plugin_name);
            // 修改页面显示
            if (empty($info)) {
                return $this->message(lang('admin/wechat.please_select_commond'), null, 2);
            }
            $info['config'] = empty($info['config']) ? [] : unserialize($info['config']);
        }

        // 插件实例
        $obj = $this->wechatManageService->wechatPluginInstance($this->plugin_name, $info);

        if (!is_null($obj)) {
            return $obj->install();
        }
    }

    /**
     * 功能扩展卸载
     */
    public function extend_uninstall()
    {
        // 功能扩展管理权限
        $this->admin_priv('extend');

        $plugin_name = request()->input('ks', '');
        if (empty($plugin_name)) {
            return $this->message(lang('admin/wechat.please_select_commond'), null, 2);
        }

        // 功能扩展卸载
        $this->wechatManageService->uninstallWechatExtend($this->wechat_id, $plugin_name);

        return $this->message(lang('admin/common.uninstall') . lang('admin/common.success'), route('admin/wechat/extend_index'));
    }

    /**
     * 获取中奖记录
     */
    public function winner_list()
    {
        // 功能扩展管理权限
        $this->admin_priv('extend');

        $plugin_name = html_in(request()->input('ks', ''));
        if (empty($plugin_name)) {
            return $this->message(lang('admin/wechat.please_select_commond'), null, 2);
        }

        $keywords = html_in(request()->input('keywords', '')); // 搜索关键词

        // 分页
        $filter['ks'] = $plugin_name;
        $offset = $this->pageLimit(route('admin/wechat/winner_list', $filter), $this->page_num);

        // 中奖记录列表
        $condition = [
            'keywords' => $keywords
        ];
        $result = $this->wechatManageService->transformWechatPrizeForList($this->wechat_id, $offset, $plugin_name, $condition);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        $this->assign('activity_type', $plugin_name);

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->assign('page_num', $this->page_num);
        return $this->display();
    }

    /**
     * 发放奖品
     */
    public function winner_issue()
    {
        // 功能扩展管理权限
        $this->admin_priv('extend');

        $id = request()->input('id', 0);
        $cancel = request()->input('cancel');
        $activity_type = request()->input('ks', '');
        if (empty($id)) {
            return $this->message(lang('admin/wechat.please_select_prize'), null, 2);
        }
        if (empty($activity_type)) {
            return $this->message(lang('admin/wechat.please_select_commond'), null, 2);
        }

        if (!empty($cancel)) {
            $data['issue_status'] = 0;
            $this->wechatManageService->updateWechatPrize($this->wechat_id, $id, $data);

            return $this->message(lang('admin/wechat.unset_issue_status') . lang('admin/common.success'), route('admin/wechat/winner_list', ['ks' => $activity_type]));
        } else {
            $data['issue_status'] = 1;
            $this->wechatManageService->updateWechatPrize($this->wechat_id, $id, $data);

            return $this->message(lang('admin/wechat.set_issue_status') . lang('admin/common.success'), route('admin/wechat/winner_list', ['ks' => $activity_type]));
        }
    }

    /**
     * 删除记录
     */
    public function winner_del()
    {
        // 功能扩展管理权限
        $this->admin_priv('extend');

        $id = request()->input('id', 0);
        $activity_type = request()->input('ks', '');
        if (empty($id)) {
            return $this->message(lang('admin/wechat.please_select_prize'), null, 2);
        }
        if (empty($activity_type)) {
            return $this->message(lang('admin/wechat.please_select_commond'), null, 2);
        }

        // 删除
        $this->wechatManageService->deleteWechatPrize($this->wechat_id, $id);

        return $this->message(lang('admin/common.delete') . lang('admin/common.success'), route('admin/wechat/winner_list', ['ks' => $activity_type]));
    }

    /**
     * 导出插件活动中奖记录
     */
    public function export_winner()
    {
        // 权限
        $this->admin_priv('extend');

        $plugin_name = request()->input('ks', '');

        if (request()->isMethod('POST')) {
            $starttime = request()->input('starttime', '');
            $endtime = request()->input('endtime', '');
            $plugin_name = request()->input('ks', '');
            if (empty($plugin_name)) {
                return $this->message(lang('admin/wechat.please_select_commond'), null, 2);
            }
            if (empty($starttime) || empty($endtime)) {
                return $this->message(lang('admin/wechat.select_start_end_time'), null, 2);
            }

            $starttime = $this->timeRepository->getLocalStrtoTime($starttime);
            $endtime = $this->timeRepository->getLocalStrtoTime($endtime);

            if ($starttime > $endtime) {
                return $this->message(lang('admin/wechat.start_lt_end_time'), null, 2);
            }

            // 按时间导出
            $condition = [
                'starttime' => $starttime,
                'endtime' => $endtime
            ];
            $total = $this->wechatManageService->transformWechatPrizeTotal($this->wechat_id, [], $plugin_name, $condition);

            $size = $this->page_num;
            $total_page = ceil($total / $size); //计算总分页数

            if (!empty($total_page)) {
                // 设置 表头标题
                /**
                 * column_name 列名
                 * width 列宽
                 * wrap_text // 是否自动换行 0 否 1 是
                 * text_align  // 文字对齐方式 默认 left, right, center
                 * draw_img // 是否生成图片 0 否 1 是
                 * num  // 是否使用科学计数法 0 否 1 是
                 */
                $head = [
                    ['column_name' => lang('admin/wechat.num_order')],
                    ['column_name' => lang('admin/wechat.sub_nickname'), 'width' => '25'],
                    ['column_name' => lang('admin/wechat.prize_name'), 'width' => '25'],
                    ['column_name' => lang('admin/wechat.issue_status')],
                    ['column_name' => lang('admin/wechat.winner_info'), 'width' => '50', 'wrap_text' => 1],
                    ['column_name' => lang('admin/wechat.prize_time'), 'width' => '25']
                ];

                // 需要导出字段 须和查询数据里的字段名保持一致
                $fields = [
                    'id',
                    'nickname',
                    'prize_name',
                    'issue_status',
                    'winner',
                    'dateline'
                ];

                // 文件名
                $title = lang('admin/wechat.winner_list');

                $spreadsheet = new OfficeService();

                // 文件下载目录
                $dir = 'data/attached/file/';
                $file_path = storage_public($dir);
                if (!is_dir($file_path)) {
                    Storage::disk('public')->makeDirectory($dir);
                }

                $options = [
                    'savePath' => $file_path, // 指定文件下载目录
                ];

                // 默认样式
                $spreadsheet->setDefaultStyle();

                for ($page = 1; $page <= $total_page; $page++) {

                    // 按分页获取数据
                    $offset = [
                        'start' => ($page - 1) * $size,
                        'limit' => $size
                    ];
                    $result = $this->wechatManageService->transformWechatPrizeForExcel($this->wechat_id, $offset, $plugin_name, $condition);

                    $list = $result['list'] ?? [];

                    // 文件名按分页命名
                    $out_title = $title . '-' . $page;

                    if ($list) {
                        $spreadsheet->exportExcel($out_title, $head, $fields, $list, $options);
                    }
                }
                // 关闭
                $spreadsheet->disconnect();

                // 压缩打包文件并下载
                $zip_path = storage_public($dir . 'zip/');
                if (!is_dir($zip_path)) {
                    Storage::disk('public')->makeDirectory($dir . 'zip/');
                }

                $zip_name = $title . '_' . date('Ymd') . '.zip';

                $zipper = new Zipper();
                $files = glob($file_path . '*.*'); // 排除子目录
                $zipper->make($zip_path . $zip_name)->add($files)->close();
                if (file_exists($zip_path . $zip_name)) {
                    // 删除文件
                    $files = Storage::disk('public')->files($dir);
                    Storage::disk('public')->delete($files);
                    return response()->download($zip_path . $zip_name)->deleteFileAfterSend(); // 下载完成删除zip压缩包
                }

            } else {
                return $this->message(lang('admin/wechat.data_null'), null, 2);
            }
        }

        return redirect()->route('admin/wechat/winner_list', ['ks' => $plugin_name]);
    }

    /**
     * 获取粉丝签到记录
     */
    public function sign_list()
    {
        // 功能扩展管理权限
        $this->admin_priv('extend');

        $plugin_name = html_in(request()->input('ks', ''));
        if (empty($plugin_name)) {
            return $this->message(lang('admin/wechat.please_select_commond'), null, 2);
        }

        $keywords = html_in(request()->input('keywords', '')); // 搜索关键词

        // 分页
        $filter['ks'] = $plugin_name;
        $offset = $this->pageLimit(route('admin/wechat/sign_list', $filter), $this->page_num);

        // 签到记录
        $condition = [
            'keywords' => $keywords
        ];
        $result = $this->wechatManageService->transformWechatPointList($this->wechat_id, $offset, $plugin_name, $condition);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        $this->assign('activity_type', $plugin_name);

        $this->assign('page', $this->pageShow($total));
        $this->assign('page_num', $this->page_num);
        $this->assign('list', $list);
        return $this->display();
    }

    /**
     * 删除签到记录
     */
    public function sign_del()
    {
        // 功能扩展管理权限
        $this->admin_priv('extend');

        $id = request()->input('id', 0);
        $activity_type = request()->input('ks', '');
        if (empty($id)) {
            return $this->message(lang('admin/wechat.please_select_prize'), null, 2);
        }
        if (empty($activity_type)) {
            return $this->message(lang('admin/wechat.please_select_commond'), null, 2);
        }

        $this->wechatManageService->deleteWechatPoint($this->wechat_id, $id);

        return $this->message(lang('admin/common.delete') . lang('admin/common.success'), route('admin/wechat/sign_list', ['ks' => $activity_type]));
    }

    /**
     * 导出插件活动签到记录
     */
    public function export_sign()
    {
        // 权限
        $this->admin_priv('extend');

        $plugin_name = request()->input('ks', '');

        if (request()->isMethod('POST')) {
            $starttime = request()->input('starttime', '');
            $endtime = request()->input('endtime', '');
            $plugin_name = request()->input('ks', '');
            if (empty($plugin_name)) {
                return $this->message(lang('admin/wechat.please_select_commond'), null, 2);
            }
            if (empty($starttime) || empty($endtime)) {
                return $this->message(lang('admin/wechat.select_start_end_time'), null, 2);
            }

            $starttime = $this->timeRepository->getLocalStrtoTime($starttime);
            $endtime = $this->timeRepository->getLocalStrtoTime($endtime);

            if ($starttime > $endtime) {
                return $this->message(lang('admin/wechat.start_lt_end_time'), null, 2);
            }

            // 按时间导出
            $condition = [
                'starttime' => $starttime,
                'endtime' => $endtime
            ];
            $result = $this->wechatManageService->transformWechatPointList($this->wechat_id, [], $plugin_name, $condition);

            $total = $result['total'] ?? 0;
            $list = $result['list'] ?? [];

            if ($list) {
                // 设置 表头标题、列宽 默认10, true 是否自动换行  格式：列名|10|true
                $head = [
                    lang('admin/wechat.sub_nickname') . '|25',
                    lang('admin/wechat.sign_time') . '|25',
                    lang('admin/wechat.sign_prize') . '|30',
                    lang('admin/wechat.sign_remark') . '|50|true',
                ];

                // 导出字段
                $fields = [
                    'nickname',
                    'createtime_format',
                    'sign_prize',
                    'change_desc',
                ];
                // 文件名
                $title = lang('admin/wechat.sign_list');

                $spreadsheet = new OfficeService();

                $spreadsheet->outdata($title, $head, $fields, $list);
                return;
            } else {
                return $this->message(lang('admin/wechat.data_null'), null, 2);
            }
        }

        return redirect()->route('admin/wechat/sign_list', ['ks' => $plugin_name]);
    }

    /**
     * 营销活动首页
     * @return
     */
    public function market_index()
    {
        // 营销功能管理权限
        $this->admin_priv('market');

        $markets = $this->wechatManageService->readPlugins('Market');

        if (!empty($markets)) {
            foreach ($markets as $key => $val) {
                $markets[$key]['url'] = route('admin/wechat/market_list', ['type' => $val['keywords']]);
            }

            // 数组排序--根据键 sort 的值的数值重新排序
            $markets = app(BaseRepository::class)->getSortBy($markets, 'sort', 'asc');
        }

        $this->assign('list', $markets);
        return $this->display();
    }

    /**
     * 营销活动后台管理列表
     */
    public function market_list()
    {
        // 营销功能管理权限
        $this->admin_priv('market');

        $extend_config['wechat_id'] = $this->wechat_id;
        $extend_config['page_num'] = $this->page_num;

        // 营销插件实例
        $obj = $this->wechatManageService->wechatMarketAdminInstance($this->market_type, $extend_config);

        if (!is_null($obj)) {
            return $obj->marketList();
        }
    }

    /**
     * 营销活动后台安装/编辑
     */
    public function market_edit()
    {
        // 营销功能管理权限
        $this->admin_priv('market');

        // 显示
        $market_id = request()->input('id', 0);

        // 编辑
        if (!empty($market_id)) {
            $extend_config['market_id'] = $market_id;
            $extend_config['handler'] = 'edit';
        }
        $extend_config['wechat_id'] = $this->wechat_id;

        // 取得当前活动条数 作为关键词后缀 如wall1
        $key = $this->wechatManageService->wechatMarketingLastId($this->wechat_id, $this->market_type);
        $extend_config['command'] = $this->market_type . $this->wechat_id . $key;

        // 营销插件实例
        $obj = $this->wechatManageService->wechatMarketAdminInstance($this->market_type, $extend_config);

        if (!is_null($obj)) {
            return $obj->marketEdit();
        }
    }

    /**
     * 删除活动
     * @return
     */
    public function market_del()
    {
        // 营销功能管理权限
        $this->admin_priv('market');

        $id = request()->input('id', 0);
        if (!$id) {
            return $this->message(lang('admin/wechat.empty'), null, 2);
        }

        // 删除活动
        $this->wechatManageService->deleteWechatMarket($this->wechat_id, $id);

        return $this->message(lang('admin/wechat.market_delete') . lang('admin/common.success'), route('admin/wechat/market_list', ['type' => $this->market_type]));
    }

    /**
     * 记录列表
     * @param id 活动ID
     * @param function 操作类型 如 messages, users, prizes
     * @return
     */
    public function data_list()
    {
        // 营销功能管理权限
        $this->admin_priv('market');

        $market_id = request()->input('id', 0);

        $function = request()->input('function', '');

        if (!empty($function)) {

            $extend_config['wechat_id'] = $this->wechat_id;
            $extend_config['page_num'] = $this->page_num;
            $extend_config['market_id'] = $market_id;

            // 营销插件实例
            $obj = $this->wechatManageService->wechatMarketAdminInstance($this->market_type, $extend_config);

            if (!is_null($obj)) {
                // 方法名
                $function_name = 'market' . Str::camel($function);

                return $obj->$function_name();
            }
        }
    }


    /**
     * 活动二维码
     * @return mixed
     */
    public function market_qrcode()
    {
        // 营销功能管理权限
        $this->admin_priv('market');

        $extend_config['wechat_id'] = $this->wechat_id;

        // 营销插件实例
        $obj = $this->wechatManageService->wechatMarketAdminInstance($this->market_type, $extend_config);

        if (!is_null($obj)) {
            return $obj->marketQrcode();
        }
    }

    /**
     * 插件行为处理方法 异步 例如: 审核 删除
     */
    public function market_action()
    {
        // 营销功能管理权限
        $this->admin_priv('market');

        $extend_config['wechat_id'] = $this->wechat_id;

        // 营销插件实例
        $obj = $this->wechatManageService->wechatMarketAdminInstance($this->market_type, $extend_config);

        if (!is_null($obj)) {
            return $obj->executeAction();
        }
    }
}
