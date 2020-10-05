<?php

namespace App\Http\Controllers;

use App\Kernel\Plugins\PluginAbstract;
use Illuminate\Support\Str;

abstract class PluginController extends PluginAbstract
{
    /**
     * 模板变量
     * @var array
     */
    protected $_data = [];


    /**
     * 插件目录 名称
     * @var mixed|string
     */
    public $addon_path = '';
    public $plugin_name = '';
    public $code = '';

    /**
     * PluginController constructor.
     */
    public function __construct()
    {
        $this->plugin_name = $this->getPluginName();
        $this->code = Str::snake($this->plugin_name);
        $this->addon_path = $this->getPluginDirectory();
    }

    final public function getClass()
    {
        $class = get_class($this);
        $str = explode('\\', $class);
        return $str;
    }

    /**
     * 插件目录
     * @return mixed
     */
    final public function getPluginDirectory()
    {
        $str = $this->getClass();

        return $str['2'];
    }

    /**
     * 插件名
     * @return mixed
     */
    final public function getPluginName()
    {
        $str = $this->getClass();

        return $str['3'];
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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function plugin_display($filename = '')
    {
        $tpl = plugin_path($this->addon_path . '/' . $this->plugin_name . '/Views/' . $filename . '.blade.php');

        $lang_path = plugin_path($this->addon_path . '/' . $this->plugin_name . '/Languages/' . config('shop.lang') . '.php');

        if (file_exists($lang_path)) {
            // 加载插件单独语言包
            L(require_once($lang_path));
        }

        $lang = L();
        $this->assign('lang', $lang); // 用于主模板

        $this->plugin_assign('lang', $lang); // 用于内嵌模板

        $content = view()->file($tpl, $this->_data);
        $this->assign('content', $content);

        $this->assign('type', 'admin'); // 模板类型： 空 前台 admin 后台

        $view = 'admin.base.plugin';
        return $this->display($view);
    }

    /**
     * 显示插件模板（前台）
     * @param string $filename
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function show_display($filename = '')
    {
        $tpl = plugin_path($this->addon_path . '/' . $this->plugin_name . '/Views/' . $filename . '.blade.php');

        $lang_path = plugin_path($this->addon_path . '/' . $this->plugin_name . '/Languages/' . config('shop.lang') . '.php');

        if (file_exists($lang_path)) {
            // 加载插件单独语言包
            L(require_once($lang_path));
        }

        $lang = L();
        $this->assign('lang', $lang); // 用于主模板

        $this->plugin_assign('lang', $lang); // 用于内嵌模板

        $content = view()->file($tpl, $this->_data);
        $this->assign('content', $content);

        $this->assign('type', ''); // 模板类型： 空 前台 admin 后台

        $view = 'admin.base.plugin';
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
}
