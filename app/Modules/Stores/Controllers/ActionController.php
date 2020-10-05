<?php

namespace App\Modules\Stores\Controllers;

use App\Repositories\Common\SessionRepository;

/**
 * 门店控制台首页
 */
class ActionController extends InitController
{
    public function index()
    {
        /* act操作项的初始化 */
        $act = request()->input('act', '');

        /* ------------------------------------------------------ */
        //-- 清理缓存
        /* ------------------------------------------------------ */
        if ($act == 'clear_cache') {
            clear_all_files('', STORES_PATH);
            return sys_msg($GLOBALS['_LANG']['caches_cleared']);
        }

        /*------------------------------------------------------ */
        //-- 退出登录
        /*------------------------------------------------------ */
        elseif ($act == 'logout') {
            /* 清除session */
            $sessionList = [
                'store_user_id',
                'stores_id',
                'stores_name'
            ];
            app(SessionRepository::class)->destroy_session($sessionList);

            $Loaction = "privilege.php?act=logout";
            return dsc_header("Location: $Loaction\n");
        }
    }
}
