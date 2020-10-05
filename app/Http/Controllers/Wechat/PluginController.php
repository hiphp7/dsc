<?php

namespace App\Http\Controllers\Wechat;

use App\Http\Controllers\Controller;
use App\Models\WechatExtend;
use App\Models\WechatMedia;
use App\Repositories\Common\TimeRepository;
use App\Services\Wechat\WechatHelperService;
use App\Services\Wechat\WechatPluginService;
use App\Services\Wechat\WechatPointService;
use App\Services\Wechat\WechatService;
use App\Services\Wechat\WechatUserService;
use Illuminate\Support\Str;

abstract class PluginController extends Controller
{
    /**
     * 模板变量
     * @var array
     */
    protected $_data = [];

    protected $plugin_name;

    protected $config;
    protected $wechatService;
    protected $wechatPointService;
    protected $timeRepository;
    protected $wechatUserService;
    protected $wechatHelperService;
    protected $wechatPluginService;

    /**
     * PluginController constructor.
     */
    public function __construct()
    {
        $shopConfig = cache('shop_config');
        if (is_null($shopConfig)) {
            $this->config = app(\App\Services\Common\ConfigService::class)->getConfig();
        } else {
            $this->config = $shopConfig;
        }

        $this->timeRepository = app(TimeRepository::class);
        $this->wechatService = app(WechatService::class);
        $this->wechatPointService = app(WechatPointService::class);
        $this->wechatUserService = app(WechatUserService::class);
        $this->wechatHelperService = app(WechatHelperService::class);
        $this->wechatPluginService = app(WechatPluginService::class);

        config(['shop' => $this->config]);
    }

    /**
     * 内嵌模板变量赋值
     *
     * @param $name
     * @param string $value
     */
    protected function plugin_assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->_data = array_merge($this->_data, $name);
        } else {
            $this->_data[$name] = $value;
        }
    }

    /**
     * 显示插件模板（后台）
     * @param string $filename
     * @param array $data
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function plugin_display($filename = '', $data = [])
    {
        // 加载公共部分语言包
        L(lang('admin/wechat'));

        $config = $data['config'] ?: [];
        $plugin_path = !empty($config['plugin_path']) ? $config['plugin_path'] : 'Plugins';

        $view_path = (isset($config['wechat_ru_id']) && $config['wechat_ru_id'] > 0) ? 'Views_seller' : 'Views';
        $plugin_name = Str::studly($this->plugin_name);
        if ($plugin_path == 'Market') {
            $tpl = plugin_path('Market/' . $plugin_name . '/' . $view_path . '/' . $filename . '.blade.php');

            $lang_path = plugin_path('Market/' . $plugin_name . '/Languages/' . config('shop.lang') . '.php');
        } else {
            $tpl = plugin_path('Wechat/' . $plugin_name . '/' . $view_path . '/' . $filename . '.blade.php');

            $lang_path = plugin_path('Wechat/' . $plugin_name . '/Languages/' . config('shop.lang') . '.php');
        }

        if (file_exists($lang_path)) {
            // 加载插件单独语言包
            L(require_once($lang_path));
        }

        $lang = L();
        $this->assign('lang', $lang); // 用于主模板

        $this->plugin_assign('lang', $lang); // 用于内嵌模板
        $content = view()->file($tpl, $this->_data);
        $this->assign('template_content', $content);
        $this->assign('type', 1); // 模板类型 0 前台 1 后台

        $view = (isset($config['wechat_ru_id']) && $config['wechat_ru_id'] > 0) ? 'seller.wechat.layout' : 'admin.wechat.layout';
        return $this->display($view);
    }

    /**
     * 显示插件模板（前台）
     * @param string $filename
     * @param array $data
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function show_display($filename = '', $data = [])
    {
        // 加载公共部分语言包
        L(lang('wechat'));

        $config = $data['config'] ?: [];
        $plugin_path = !empty($config['plugin_path']) ? $config['plugin_path'] : 'Plugins';

        $view_path = (isset($config['wechat_ru_id']) && $config['wechat_ru_id'] > 0) ? 'Views_seller' : 'Views';
        $plugin_name = Str::studly($this->plugin_name);
        if ($plugin_path == 'Market') {
            $tpl = plugin_path('Market/' . $plugin_name . '/' . $view_path . '/' . $filename . '.blade.php');

            $lang_path = plugin_path('Market/' . $plugin_name . '/Languages/' . config('shop.lang') . '.php');
        } else {
            $tpl = plugin_path('Wechat/' . $plugin_name . '/' . $view_path . '/' . $filename . '.blade.php');

            $lang_path = plugin_path('Wechat/' . $plugin_name . '/Languages/' . config('shop.lang') . '.php');
        }

        if (file_exists($lang_path)) {
            // 加载插件单独语言包
            L(require_once($lang_path));
        }

        $lang = L();
        $this->assign('lang', $lang); // 用于主模板

        $this->plugin_assign('lang', $lang); // 用于内嵌模板
        $content = view()->file($tpl, $this->_data);
        $this->assign('template_content', $content);
        $this->assign('type', 0); // 模板类型 0 前台 1 后台

        $view = (isset($config['wechat_ru_id']) && $config['wechat_ru_id'] > 0) ? 'seller.wechat.layout' : 'admin.wechat.layout';
        return $this->display($view);
    }

    /**
     * 消息提示跳转页
     * @param $msg
     * @param null $url
     * @param string $type
     * @param bool $seller
     * @param int $waitSecond
     * @return string
     */
    protected function message($msg, $url = null, $type = '1', $seller = false, $waitSecond = 2)
    {
        if (is_null($url)) {
            $url = 'javascript:history.back();';
        }
        if ($type == '2') {
            $title = 'Error';
        } else {
            $title = 'Warning';
        }

        $data = [
            'title' => $title,
            'message' => $msg,
            'type' => $type,
            'url' => $url,
            'second' => $waitSecond,
        ];
        $this->assign('data', $data);

        $tpl = ($seller == true) ? 'admin/base.seller_message' : 'admin/base.message';
        return $this->display($tpl);
    }

    /**
     * 前端消息提示跳转页
     * @param $msg
     * @param null $url
     * @param string $type
     * @param int $waitSecond
     * @return string
     */
    protected function show_message($msg, $url = null, $type = '1', $waitSecond = 2)
    {
        if (is_null($url)) {
            $url = 'javascript:history.back();';
        }
        if ($type == '2') {
            $title = 'Error';
        } else {
            $title = 'Warning';
        }

        $data = [
            'title' => $title,
            'message' => $msg,
            'type' => $type,
            'url' => $url,
            'second' => $waitSecond,
        ];
        $this->assign('data', $data);

        return $this->display('message');
    }

    /**
     * 获取插件配置信息
     *
     * @param int $wechat_id
     * @param string $code
     * @return array|mixed
     */
    protected function get_plugin_config($wechat_id = 0, $code = '')
    {
        $config = [];
        $wechatExtend = WechatExtend::where(['wechat_id' => $wechat_id, 'command' => $code, 'enable' => 1])->first();
        $wechatExtend = $wechatExtend ? $wechatExtend->toArray() : [];

        if (!empty($wechatExtend)) {

            $config = empty($wechatExtend['config']) ? [] : unserialize($wechatExtend['config']);
            // 素材
            if (!empty($config['media_id'])) {
                $media = WechatMedia::select('id', 'title', 'file', 'file_name', 'type', 'digest', 'content', 'add_time', 'article_id', 'link')
                    ->where(['id' => $config['media_id'], 'wechat_id' => $wechat_id])
                    ->first();
                $media = $media ? $media->toArray() : [];
                // 单图文
                if (empty($media['article_id'])) {
                    $media['content'] = strip_tags(html_out($media['content']));
                    $config['media'] = $media;
                }
            }
            // url处理
            if (!empty($config['plugin_url'])) {
                $config['plugin_url'] = html_out($config['plugin_url']);
            }
            // 奖品处理
            if (isset($config['prize_level']) && !empty($config['prize_level'])) {
                if (is_array($config['prize_level']) && is_array($config['prize_count']) && is_array($config['prize_prob']) && is_array($config['prize_name'])) {
                    foreach ($config['prize_level'] as $key => $val) {
                        $config['prize'][] = [
                            'prize_level' => $val,
                            'prize_name' => $config['prize_name'][$key],
                            'prize_count' => $config['prize_count'][$key],
                            'prize_prob' => $config['prize_prob'][$key]
                        ];
                    }
                }
            }

            $config['extend'] = $wechatExtend;
        }

        return $config;
    }

    /**
     * 获得openid
     * @param int $wechat_ru_id
     * @return \Illuminate\Session\SessionManager|\Illuminate\Session\Store|int|mixed
     */
    protected function get_openid($wechat_ru_id = 0)
    {
        if ($wechat_ru_id > 0) {
            $openid = session()->get('seller_openid', '');
        } else {
            $openid = session()->get('openid', '');
        }

        return $openid;
    }
}
