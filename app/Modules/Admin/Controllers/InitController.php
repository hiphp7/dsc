<?php

namespace App\Modules\Admin\Controllers;

use App\Kernel\Modules\Admin\Controllers\InitController as Base;

class InitController extends Base
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
     * 获取当前控制器与方法
     *
     * @return array
     */
    protected function getCurrentAction()
    {
        return parent::getCurrentAction();
    }

    /**
     * 模板变量赋值
     *
     * @param $name
     * @param string $value
     */
    protected function assign($name, $value = '')
    {
        return parent::assign($name, $value);
    }

    /**
     * 加载模板和页面输出 可以返回输出内容
     * @param string $filename
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function display($filename = '')
    {
        return parent::display($filename);
    }
}
