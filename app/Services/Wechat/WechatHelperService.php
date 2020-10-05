<?php

namespace App\Services\Wechat;

use App\Models\AdminUser;
use App\Models\GalleryAlbum;
use App\Models\Users;
use App\Models\WechatExtend;
use App\Models\WechatQrcode;
use App\Models\WechatRuleKeywords;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;

/**
 * 微信原helper函数转 service
 * Class WechatHelperService
 * @package App\Services\Wechat
 */
class WechatHelperService
{
    protected $baseRepository;
    protected $dscRepository;
    protected $sessionRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        SessionRepository $sessionRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->sessionRepository = $sessionRepository;
    }

    /**
     * 处理微信素材图片
     * @param string $image
     * @param string $path
     * @return string
     */
    public function get_wechat_image_path($image = '', $path = '')
    {
        $path = empty($path) ? '' : rtrim($path, '/') . '/';

        // 过滤抽奖活动图片
        if (strstr($image, 'assets/wechat/')) {
            return asset('/') . preg_replace('/^public\//', '', $image, 1);
        }

        $url = $this->dscRepository->getImagePath($image);

        return $url;
    }

    /**
     * 处理URL 加上后缀参数 如 ?id=1  &id=1
     * @param string $url URL表达式，格式：'?参数1=值1&参数2=值2...'
     * @param string|array $vars 传入的参数，支持数组和字符串
     * @return string $url
     */
    public function add_url_suffix($url = '', $vars = '')
    {
        // 解析URL
        $info = parse_url($url);

        $depr = '?';
        if (isset($info['query'])) {
            // query = ?id=100
            $info['query'] = htmlspecialchars_decode($info['query']); // 处理html字符 &amp, 导致的参数重复

            // 解析地址里面参数 合并到 query
            if (!empty($vars)) {
                parse_str($info['query'], $params);
                $vars = array_merge($params, $vars);
                $info['query'] = http_build_query($vars);
            }
        }
        if (isset($info['fragment'])) {
            $string = http_build_query($vars);
            // fragment = #/user/order?parent_id=6
            if (strpos($info['fragment'], '?') !== false && strpos($info['fragment'], $string) === false) {
                $depr = '&';
            } // fragment = #/user/order?parent_id=6&wechat_ru_id=1
            elseif (strpos($info['fragment'], '&') !== false && strpos($info['fragment'], $string) !== false) {
                $depr = '&';
            }
            // fragment = #/user/order
            $new_string = $depr . $string;
            // 处理参数重复
            if (strpos($info['fragment'], $new_string) !== false) {
                $info['fragment'] = str_replace($new_string, '', $info['fragment']);
            }

            $info['fragment'] = $info['fragment'] . $new_string;
        }

        $url = $this->unparse_url($info);

        return strtolower($url);
    }

    /**
     * 处理url
     * @param $parsed_url
     * @return string
     */
    private function unparse_url($parsed_url)
    {
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * 保存 wechat_ru_id
     * @param $wechat_ru_id
     */
    public function set_wechat_ru_id($wechat_ru_id)
    {
        if (file_exists(MOBILE_WECHAT)) {
            $cookiekey = 'wechat_ru_id';
            if ($wechat_ru_id > 0) {
                // 过期时间一天
                cookie()->queue($cookiekey, $wechat_ru_id, 24 * 60);
            } else {
                $this->sessionRepository->deleteCookie($cookiekey);
            }
            return;
        }
    }

    /**
     * 获取 wechat_ru_id
     * @return int|string
     */
    public function get_wechat_ru_id()
    {
        if (file_exists(MOBILE_WECHAT)) {
            $cookiekey = 'wechat_ru_id';
            $wechat_ru_id = $this->sessionRepository->getCookie($cookiekey, 0);
            $wechat_ru_id = is_null($wechat_ru_id) ? 0 : decrypt($wechat_ru_id);

            $count = AdminUser::where('ru_id', $wechat_ru_id)->count();

            if ($count > 0) {
                return $wechat_ru_id;
            } else {
                $this->sessionRepository->deleteCookie($cookiekey);
                return 0;
            }
        }
        return 0;
    }

    /**
     * 微信粉丝生成用户名规则
     * 长度最大15个字符 兼容UCenter用户名
     * @return
     */
    public function get_wechat_username($unionid, $type = '')
    {
        switch ($type) {
            case 'wechat':
                $prefix = 'wx';
                break;
            case 'qq':
                $prefix = 'qq';
                break;
            case 'weibo':
                $prefix = 'wb';
                break;
            case 'facebook':
                $prefix = 'fb';
                break;
            default:
                $prefix = 'sc';
                break;
        }
        return $prefix . substr(md5($unionid), -5) . substr(time(), 0, 4) . mt_rand(1000, 9999);
    }

    /**
     * 微信分享类型
     * @return
     */
    public function get_share_type($val = '')
    {
        $share_type = '';
        switch ($val) {
            case '1':
                $share_type = lang('wechat.share_type.1');
                break;
            case '2':
                $share_type = lang('wechat.share_type.2');
                break;
            case '3':
                $share_type = lang('wechat.share_type.3');
                break;
            case '4':
                $share_type = lang('wechat.share_type.4');
                break;
            default:
                break;
        }
        return $share_type;
    }

    /**
     * 返回微信粉丝来源说明
     * @return
     */
    public function get_wechat_user_from($from = 0)
    {
        $from_type = '';
        switch ($from) {
            case 0:
                $from_type = lang('wechat.from_type.0');
                break;
            case 1:
                $from_type = lang('wechat.from_type.1');
                break;
            case 2:
                $from_type = lang('wechat.from_type.2');
                break;
            case 3:
                $from_type = lang('wechat.from_type.3');
                break;
            default:
                break;
        }
        return $from_type;
    }

    /**
     * 返回系统关键词和自定义关键词
     * @param $wechat_id
     * @return
     */
    public function get_keywords_list($wechat_id)
    {
        $sys_keywords = WechatExtend::select('command')->where(['wechat_id' => $wechat_id, 'enable' => 1]);
        $sys_keywords = $this->baseRepository->getToArrayGet($sys_keywords);

        $rule_keywords = WechatRuleKeywords::select('rule_keywords')->where(['wechat_id' => $wechat_id]);
        $rule_keywords = $this->baseRepository->getToArrayGet($rule_keywords);

        $new_sys_keywords = [];
        if ($sys_keywords) {
            foreach ($sys_keywords as $key => $value) {
                if ($value['command'] == 'bonus' || $value['command'] == 'sign') {
                    unset($sys_keywords[$key]);
                }
            }
            $new_sys_keywords = array_column($sys_keywords, 'command');
        }

        $new_rule_keywords = $rule_keywords ? array_column($rule_keywords, 'rule_keywords') : [];

        $total_num = count($sys_keywords) + count($rule_keywords);
        $key_name = md5('wechat_keywords' . $wechat_id . $total_num);
        $keywords_list = cache($key_name);
        if (is_null($keywords_list)) {
            $keywords_list = array_merge($new_sys_keywords, $new_rule_keywords);
            cache([$key_name => $keywords_list], 60);
        }

        return $keywords_list;
    }

    /**
     * 图片库分类列表
     * @param integer $tree_id
     * @param integer $ru_id
     * @return
     */
    public function get_gallery_album_tree($tree_id = 0, $ru_id = 0)
    {
        $three_arr = [];
        $res = GalleryAlbum::where(['parent_album_id' => $tree_id, 'ru_id' => $ru_id]);
        $count = $res->count();
        if ($count > 0 || $tree_id == 0) {
            $album = $res->orderBy('album_id', 'DESC');
            $album = $this->baseRepository->getToArrayGet($album);

            if ($album) {
                foreach ($album as $k => $row) {
                    $three_arr[$k]['id'] = $row['album_id'];
                    $three_arr[$k]['name'] = $row['album_mame'];
                    $three_arr[$k]['haschild'] = 0;
                    if (isset($row['album_id'])) {
                        $child_tree = $this->get_gallery_album_tree($row['album_id'], $ru_id);
                        if ($child_tree) {
                            $three_arr[$k]['album_id'] = $child_tree;
                            $three_arr[$k]['haschild'] = 1;
                        }
                    }
                }
            }
        }

        return $three_arr;
    }

    /**
     * 二维码状态
     * @return
     */
    public function return_qrcode_status($id, $user_id = 0)
    {
        $status = 1;
        $users = Users::select('user_id', 'user_name')->where(['user_id' => $user_id]);
        $users = $this->baseRepository->getToArrayFirst($users);

        if (empty($users)) {
            $rs = WechatQrcode::where('id', $id)->update(['status' => 0]);
            $status = 0;
        }
        return $status;
    }


    /**
     * 商家信息
     * @return array
     */
    public function get_admin_seller()
    {
        $seller_id = request()->session()->get('seller_id', 0);
        $res = AdminUser::select('ru_id', 'user_name', 'admin_user_img', 'action_list')->where(['user_id' => $seller_id]);
        $res = $this->baseRepository->getToArrayFirst($res);

        return $res;
    }

    /**
     * 获得当前选中的菜单
     * @return array
     */
    public function get_select_menu()
    {
        // 商家后台当前模块左侧选择菜单（子菜单）
        $child_menu = [
            '22_wechat' => [
                '01_wechat_admin' => 'seller/wechat/modify',
                '02_mass_message' => 'seller/wechat/mass_message',
                '02_mass_message_01' => 'seller/wechat/mass_list',
                '03_auto_reply' => 'seller/wechat/reply_subscribe',
                '03_auto_reply_01' => 'seller/wechat/reply_msg',
                '03_auto_reply_02' => 'seller/wechat/reply_keywords',
                '04_menu' => 'seller/wechat/menu_list',
                '04_menu_01' => 'seller/wechat/menu_edit',
                '05_fans' => 'seller/wechat/subscribe_list',
                '05_fans_01' => 'seller/wechat/custom_message_list',
                '05_fans_02' => 'seller/wechat/subscribe_search',
                '05_fans_03' => 'seller/wechat/sys_tags',
                '06_media' => 'seller/wechat/article',
                '06_media_01' => 'seller/wechat/article_edit',
                '06_media_02' => 'seller/wechat/article_edit_news',
                '06_media_03' => 'seller/wechat/picture',
                '06_media_04' => 'seller/wechat/voice',
                '06_media_05' => 'seller/wechat/video',
                '06_media_06' => 'seller/wechat/video_edit',
                '07_qrcode' => 'seller/wechat/qrcode_list',
                '07_qrcode_01' => 'seller/wechat/qrcode_edit',
                '09_extend' => 'seller/wechat/extend_index',
                '09_extend_01' => 'seller/wechat/extend_edit',
                '09_extend_02' => 'seller/wechat/winner_list',
                '10_market' => 'seller/wechat/market_index',
                '10_market_01' => 'seller/wechat/market_list',
                '10_market_02' => 'seller/wechat/market_edit',
                '10_market_03' => 'seller/wechat/data_list',
                '10_market_04' => 'seller/wechat/market_qrcode',
            ]
        ];

        // 商家后台子菜单语言包 用于当前位置显示
        $lang = lang('admin/wechat');
        $child_menu_lang = [
            '02_mass_message_01' => $lang['mass_list'],
            '03_auto_reply_01' => $lang['msg_autoreply'],
            '03_auto_reply_02' => $lang['keywords_autoreply'],
            '04_menu_01' => $lang['menu_edit'],
            '05_fans_01' => $lang['custom_message_list'],
            '05_fans_02' => $lang['sub_list'],
            '05_fans_03' => $lang['tag_update'],
            '06_media_01' => $lang['article_edit'],
            '06_media_02' => $lang['article_edit_news'],
            '06_media_03' => $lang['picture'],
            '06_media_04' => $lang['voice'],
            '06_media_05' => $lang['video'],
            '06_media_06' => $lang['upload_video'],
            '07_qrcode_01' => $lang['qrcode_edit'],
            '09_extend_01' => $lang['extend_edit'],
            '09_extend_02' => $lang['winner_list'],
            '10_market_01' => $lang['market_list'],
            '10_market_02' => $lang['market_edit'],
            '10_market_03' => $lang['data_list'],
            '10_market_04' => $lang['market_qrcode'],
        ];
        // 合并菜单语言包
        $GLOBALS['_LANG'] = array_merge($GLOBALS['_LANG'], $child_menu_lang);

        // 合并左侧菜单
        $left_menu = array_merge($GLOBALS['modules'], $child_menu);

        $uri = request()->getRequestUri();

        // 匹配选择的菜单列表
        $uri = ltrim($uri, '/');
        $menu_arr = $this->get_menu_arr($uri, $left_menu);

        return $menu_arr;
    }

    /**
     * 匹配选择的菜单
     * @param string $url
     * @param array $list
     * @return array
     */
    private function get_menu_arr($url = '', $list = [])
    {
        static $menu_arr = [];
        static $menu_key = null;
        foreach ($list as $key => $val) {
            if (is_array($val)) {
                $menu_key = $key;
                $this->get_menu_arr($url, $val);
            } else {
                if ($val == $url || strpos($url, $val) !== false) {
                    $menu_arr['action'] = $menu_key;
                    $menu_arr['current'] = $key;

                    // 当前模块主菜单语言包
                    $menu_arr['action_label'] = $GLOBALS['_LANG'][$menu_key] ?? '';
                    // 当前选择菜单语言包(包含子菜单)
                    $menu_arr['label'] = $GLOBALS['_LANG'][$key] ?? $menu_arr['action_label'];

                    // 其他子菜单匹配
                    $key_2 = substr($key, 0, -3);
                    $menu_arr['current_2'] = $key_2;
                }
            }
        }
        return $menu_arr;
    }

}
