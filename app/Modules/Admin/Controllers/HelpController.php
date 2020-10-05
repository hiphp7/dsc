<?php

namespace App\Modules\Admin\Controllers;

/**
 * 帮助信息接口
 */
class HelpController extends InitController
{
    public function index()
    {
        $get_keyword = trim($_GET['al']); // 获取关键字
    }
}
