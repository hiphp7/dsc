<?php

namespace App\Custom;

use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Throwable;

trait CustomView
{
    /**
     * 获取当前模块名
     *
     * @return string
     */
    protected function getCurrentModuleName()
    {
        return $this->getCurrentAction()['module'];
    }

    /**
     * 获取当前控制器名
     *
     * @return string
     */
    protected function getCurrentControllerName()
    {
        return $this->getCurrentAction()['controller'];
    }

    /**
     * 获取当前方法名
     *
     * @return string
     */
    protected function getCurrentMethodName()
    {
        return $this->getCurrentAction()['method'];
    }

    /**
     * 获取当前路由属于哪个后台
     *
     * @return string
     */
    protected function getCurrentManageName()
    {
        return $this->getCurrentAction()['manage'];
    }

    /**
     * 获取当前控制器 所在模块与方法
     *
     * @return array
     */
    protected function getCurrentAction()
    {
        $action = request()->route()->getAction();

        $namespace = explode('\\', $action['namespace']);
        $namespace = array_pad($namespace, 5, '');
        list($app, $module_path, $module, $controllers, $manage) = $namespace;

        $action = str_replace($action['namespace'] . '\\', '', $action['controller']);

        list($controller, $method) = explode('@', $action);

        return ['module' => $module, 'controller' => Str::studly($controller), 'method' => $method, 'manage' => $manage];
    }

    /**
     * 显示开发模块模板
     * @param string $tpl
     * @return Factory|View|string
     */
    protected function display($tpl = null)
    {
        if (!is_null($tpl)) {
            return view($tpl, $this->tVar);
        }

        $action = $this->getCurrentAction();

        // 当前主模块
        $module = Str::studly($action['module']);
        // 子模块
        $manage = Str::snake($action['manage']);
        if (!empty($manage)) {
            // 设置视图
            View::addNamespace('custom', app_path('Custom') . '/' . $module . '/Views/' . $manage);
        } else {
            // 设置视图
            View::addNamespace('custom', app_path('Custom') . '/' . $module . '/Views');
        }
        // 控制器
        $controller = str_replace('Controller', '', $action['controller']);
        // 方法名
        $method = str_replace('action', '', $action['method']);

        // 默认模板
        $tpl = Str::snake($controller . '.' . $method);

        return view('custom::' . $tpl, $this->tVar);
    }

    /**
     * 异步加载开发blade模板
     * @param null $tpl
     * @return array|string
     * @throws Throwable
     */
    protected function fetch($tpl = null)
    {
        if (!is_null($tpl)) {
            return view($tpl, $this->tVar)->render();
        }

        $action = $this->getCurrentAction();

        // 当前主模块
        $module = Str::studly($action['module']);
        // 子模块
        $manage = Str::snake($action['manage']);
        if (!empty($manage)) {
            // 设置视图
            View::addNamespace('custom', app_path('Custom') . '/' . $module . '/Views/' . $manage);
        } else {
            // 设置视图
            View::addNamespace('custom', app_path('Custom') . '/' . $module . '/Views');
        }
        // 控制器
        $controller = str_replace('Controller', '', $action['controller']);
        // 方法名
        $method = str_replace('action', '', $action['method']);

        // 默认模板
        $tpl = Str::snake($controller . '.' . $method);

        return view('custom::' . $tpl, $this->tVar)->render();
    }

    /**
     * 加载当前开发模块下语言包文件
     * @exp  User/Lang/zh-CN/user.php
     * @param array $files
     * @param null $module 指定模块名
     * @return array
     * @throws Exception
     */
    protected function load_lang($files = [], $module = null)
    {
        if (is_null($module)) {
            // 当前模块名
            $module = Str::studly($this->getCurrentModuleName());
        }

        if (!is_array($files)) {
            $files = [$files];
        }

        $config = cache('shop_config');

        if (empty($config['lang']) || $config['lang'] == 'zh_cn') {
            $locale = config('app.locale');
        } else {
            $locale = $config['lang'];
        }

        $lang_path = app_path('Custom/' . $module . '/Lang/' . $locale . '/');

        static $_LANG = [];
        static $lang = [];

        foreach ($files as $vo) {
            $hash = md5($vo);
            $lang[$hash] = null;
            $helper = $lang_path . $vo . '.php';
            if (file_exists($helper)) {
                $lang[$hash] = require_once($helper);
                if (!is_null($lang[$hash])) {
                    $_LANG = array_merge($_LANG, $lang[$hash]);
                }
            }
        }

        return array_change_key_case($_LANG);
    }

    /**
     * 加载当前模块下函数库
     * @param array $files
     * @param array $module
     */
    protected function load_helper($files = [], $module = null)
    {
        if (is_null($module)) {
            // 当前模块名
            $module = Str::studly($this->getCurrentModuleName());
        }

        if (!is_array($files)) {
            $files = [$files];
        }

        $base_path = app_path('Custom/' . $module . '/Support/');

        foreach ($files as $vo) {
            $helper = $base_path . $vo . '.php';
            if (file_exists($helper)) {
                require_once $helper;
            }
        }
    }
}
