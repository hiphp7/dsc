<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Libraries\CaptchaVerify;
use App\Libraries\Mysql;
use App\Models\AdminUser;
use App\Models\ConnectUser;
use App\Models\ImService;

class LoginController extends Controller
{
    protected function initialize()
    {
        $config = cache('shop_config');
        $config = !is_null($config) ? $config : false;
        if ($config === false) {
            $config = app(\App\Services\Common\ConfigService::class)->getConfig();
        }

        $GLOBALS['_CFG'] = $config;
        $GLOBALS['db'] = app(Mysql::class);

        load_helper('time');
    }

    /**
     * 用户登录
     */
    public function index()
    {
        /**
         * 用户登录
         */
        if (request()->isMethod('post')) {
            //ecjia验证kefu登录
            $login_type = request()->get('login_type', '');

            if ($login_type == 'app_admin_login') {
                $user_id = request()->get('user_id', 0);
                // $is_admin = request()->get('is_admin', 0); 废弃参数
                $connect_code = request()->get('connect_code', '');

                $connect_user = ConnectUser::where('user_id', $user_id)
                    ->where('connect_code', $connect_code)->first();
                if (is_null($connect_user)) {
                    return ['code' => 1, 'msg' => '该账号没有客服权限'];
                }

                $service = ImService::where('user_id', $user_id)->where('status', 1)->first();
                if (is_null($service)) {
                    return ['code' => 1, 'msg' => '该账号没有客服权限'];
                }

                $row = AdminUser::where('user_id', $user_id)->first();//限制商家登录后台
            } else {
                $input = $this->checkSignInData();
                if (isset($input['code'])) {
                    return $input;
                }

                $username = $input['username'];
                $password = $input['password'];

                $row = AdminUser::where('user_name', $username)->first();
                $password = $row['ec_salt'] ? md5(md5($password) . $row['ec_salt']) : md5($password);
                if ($password != $row['password']) {
                    $row = false;
                }
            }

            //查询结果
            if ($row) {
                // 登录成功
                $service = ImService::where('user_id', $row['user_id'])->where('status', 1)->first();
                if (empty($service) || empty($service['id'])) {
                    return ['code' => 1, 'msg' => '该账号没有客服权限'];
                }

                $this->set_kefu_session($row['user_id'], $service['id'], $service['nick_name'], $service['login_time']);

                // 登录成功
                $result = ['code' => 0, 'msg' => '登录成功'];
                if (is_mobile_device()) {
                    // 成功则返回token
                    $result['token'] = $this->tokenEncode([
                        'id' => strtoupper(bin2hex(base64_encode($service['id']))),
                        'expire' => local_gettime() + 3600, // 有效期一小时
                        'hash' => md5(md5($service['id']) . config('app.key'))
                    ]);
                }

                return $result;
            } else {
                return ['code' => 1, 'msg' => '用户名或密码错误'];
            }
        }

        if (is_mobile_device()) {
            return redirect()->route('kefu.adminp.mobile');
        }

        return view('kefu.admin_login');
    }

    /**
     * 用户退出
     */
    public function logout()
    {
        $id = session('kefu_id', 0);   //客服ID

        $data['chat_status'] = 0;   // 改为退出状态
        ImService::where('id', $id)->where('status', 1)->update($data);

        session([
            'kefu_admin_id' => '',
            'kefu_id' => '',
            'kefu_name' => '',
            'last_check' => '', // 用于保存最后一次检查订单的时间
        ]);

        // 删除cookie
        cookie('ECSCP[kefu_id]', '');
        cookie('ECSCP[kefu_token]', '');

        return redirect()->route('kefu.admin.index');
    }

    /**
     * 登录数据校验
     */
    private function checkSignInData()
    {
        $username = request()->get('username', '');
        $password = request()->get('password', '');
        $catpcha = request()->get('catpcha', '');

        $result = ['code' => 0, 'msg' => ''];
        /** 用户名 */
        if (empty($username)) {
            $result['code'] = 1;
            $result['msg'] = '用户名为空';
            return $result;
        }

        /** 密码 */
        if (empty($password)) {
            $result['code'] = 1;
            $result['msg'] = '密码为空';
            return $result;
        }


        /** 手机登录不校验  验证码 */
        if (!is_mobile_device()) {
            /** 验证码 */
            if (empty($catpcha)) {
                $result['code'] = 1;
                $result['msg'] = '验证码为空';
                return $result;
            }

            load_helper('time');

            /** 校验验证码 */
            $verify = new CaptchaVerify();
            $res = $verify->check($catpcha);
            if (!$res) {
                $result['code'] = 1;
                $result['msg'] = '验证码错误';
                return $result;
            }
        }

        return [
            'username' => $username,
            'password' => $password,
        ];
    }

    /**
     * 记录客服session
     */
    private function set_kefu_session($admin_id, $user_id, $username, $last_time)
    {
        session([
            'kefu_admin_id' => $admin_id,
            'kefu_id' => $user_id,
            'kefu_name' => $username,
            'last_check' => $last_time, // 用于保存最后一次检查订单的时间
        ]);
    }

    /**
     * 加密登录信息
     * @param $data
     * @return string
     */
    private function tokenEncode($data)
    {
        return base64_encode(json_encode($data));
    }
}
