<?php

namespace App\Repositories\Common;

use App\Kernel\Repositories\Common\SessionRepository as Base;

/**
 * Class SessionRepository
 * @method realCartMacIp() 获得用户的真实IP地址和MAC地址
 * @method sessionRepy() 初始化session
 * @method getSessionId() sessionId
 * @method destroy_session($list = []) 消除 session
 * @method destroy_cookie($list = []) 消除 cookie
 * @method deleteCookie($list = []) 删除cookie
 * @method getCookie($list = [], $default = 0) 获取cookie
 * @method setCookie($list = [], $default = 0) 存储cookie
 * @method delete_spec_admin_session($adminid) 删除session
 * @method sessionPutList($list = []) 存储多条session
 * @method sessionPut($key = '', $value = '') 存储一条session
 * @method sessionGet($key = '', $value = '') 获取一条session
 * @method sessionPullList($list = [])  删除多条数据
 * @method sessionPull($key = '', $value = '') 删除一条数据
 * @method sessionPush($key = '', $value = '') 在 Session 数组中保存数据 示例：request()->session()->push('user.teams', 'developers');
 * @package App\Repositories\Common
 */
class SessionRepository extends Base
{

}