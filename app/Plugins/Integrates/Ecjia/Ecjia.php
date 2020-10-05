<?php

namespace App\Plugins\Integrates\Ecjia;

use App\Plugins\Integrates\Ucenter\Ucenter;
use App\Repositories\Common\DscRepository;
use Binaryoung\Ucenter\Facades\Ucenter as Api;
use Illuminate\Support\Facades\DB;

class Ecjia extends Ucenter
{

    /**
     * 用户登录函数
     * @param string $username
     * @param string $password
     * @param null $remember
     * @param int $is_oath
     * @return bool|void
     */
    public function login($username, $password, $remember = null, $is_oath = 1)
    {
        /**
         * 一、用户账号存在情况
         * 1、dscmall存在，ecjia不存在
         * 2、dscmall存在，ecjia存在
         * 3、dscmall不存在，ecjia不存在
         * 4、dscmall不存在，ecjia存在
         * 二、账号处理方案
         * 1、
         */


        $username = addslashes($username);

        // 本地会员，通过手机号码换取用户名
        if (is_mobile($username)) {
            $condition['mobile_phone'] = $username;
        } elseif (is_email($username)) {
            $condition['email'] = $username;
        } else {
            $condition['user_name'] = $username;
        }

        $local_user = DB::table('users')
            ->select('user_id', 'user_name', 'password', 'email', 'mobile_phone', 'ec_salt')
            ->where(key($condition), current($condition))
            ->first();

        $mobile = collect($local_user)->get('mobile_phone', $username);
        $isuid = 6; // 增加 ecjia ucenter 手机号登录

        // uc远程登录失败后同步本地用户到uc
        list($uid, $uname, $pwd, $email, $repeat) = Api::uc_user_login($mobile, $password, $isuid);

        // $uid = -1 uc远程用户不存在,或者被删除
        if ($uid == -1 && !is_null($local_user)) {
            // 校验本地用户密码
            $ec_salt = collect($local_user)->get('ec_salt');
            $other = collect($local_user)->all();
            $local_password = ['password' => $password, 'ec_salt' => $ec_salt];

            // 密码匹配之后同步到uc
            if (collect($local_user)->get('password') == $this->compile_password($local_password)) {
                if ($this->add_user($mobile, $password, $other)) {
                    list($uid, $uname, $pwd, $email, $repeat) = Api::uc_user_login($mobile, $password, $isuid);
                }
            }
        }

        if (!empty($uid) && is_string($uid) && strlen($uid) == 32) {
            // 优先兼容 connect_user 表
            $connect_user = DB::table('connect_user')
                ->where('connect_code', $this->connectType())
                ->where('open_id', $uid)
                ->leftJoin('users', 'users.user_id', 'connect_user.user_id')
                ->first();

            // 生成（更新）密码
            $ec_salt = rand(1000, 9999);
            $password = $this->compile_password(['password' => $password, 'ec_salt' => $ec_salt]);

            // 首次登录或其他应用用户
            if (is_null($connect_user)) {
                if (is_null($local_user)) {

                    $last_ip = app(DscRepository::class)->dscIp();

                    $user_id = DB::table('users')->insertGetId(
                        [
                            'user_name' => $uname,
                            'password' => $password,
                            'ec_salt' => $ec_salt,
                            'email' => $email,
                            'mobile_phone' => $mobile,
                            'reg_time' => gmtime(),
                            'last_login' => gmtime(),
                            'last_ip' => $last_ip
                        ]
                    );
                } else {
                    $user_id = collect($local_user)->get('user_id');
                }

                DB::table('connect_user')->insert(
                    ['connect_code' => $this->connectType(), 'user_id' => $user_id, 'open_id' => $uid, 'create_at' => gmtime()]
                );
            } else {
                DB::table('users')
                    ->where('user_id', collect($local_user)->get('user_id'))
                    ->update(['password' => $password, 'ec_salt' => $ec_salt]);

                $uname = collect($local_user)->get('user_name');
            }

            if ($is_oath == 1) {
                $this->set_session($uname);
                $this->set_cookie($uname);
            }

            $this->ucdata = Api::uc_user_synlogin($uid);
            return true;
        } elseif ($uid == -1) {
            $this->error = ERR_INVALID_USERNAME;
            return false;
        } elseif ($uid == -2) {
            $this->error = ERR_INVALID_PASSWORD;
            return false;
        } else {
            return false;
        }
    }

    /**
     * 添加用户
     * @param string $username
     * @param string $password
     * @param array $other
     * @param int $gender
     * @param int $bday
     * @param int $reg_date
     * @param string $md5password
     * @return bool|int
     */
    public function add_user($username, $password, $other, $gender = -1, $bday = 0, $reg_date = 0, $md5password = '')
    {
        $username = $other['mobile_phone'];

        /* 检测用户名 */
        if ($this->check_user($username) == false) {
            $this->error = ERR_USERNAME_EXISTS;
            return false;
        }

        $uid = Api::uc_user_register($username, $password, $other['email']);
        if ($uid <= 0) {
            if ($uid == -1) {
                $this->error = ERR_INVALID_USERNAME;
                return false;
            } elseif ($uid == -2) {
                $this->error = ERR_USERNAME_NOT_ALLOW;
                return false;
            } elseif ($uid == -3) {
                $this->error = ERR_USERNAME_EXISTS;
                return false;
            } elseif ($uid == -4) {
                $this->error = ERR_INVALID_EMAIL;
                return false;
            } elseif ($uid == -5) {
                $this->error = ERR_EMAIL_NOT_ALLOW;
                return false;
            } elseif ($uid == -6) {
                $this->error = ERR_EMAIL_EXISTS;
                return false;
            } else {
                return false;
            }
        } else {

            // 本地会员，通过手机号码换取用户名
            if (is_mobile($username)) {
                $condition['mobile_phone'] = $username;
            } elseif (is_email($username)) {
                $condition['email'] = $username;
            } else {
                $condition['user_name'] = $username;
            }

            $local_user = DB::table('users')
                ->select('user_id', 'user_name', 'password', 'email', 'mobile_phone', 'ec_salt')
                ->where(key($condition), current($condition))
                ->first();

            if (!empty($uid) && is_string($uid) && strlen($uid) == 32) {
                // 优先兼容 connect_user 表
                $connect_user = DB::table('connect_user')
                    ->where('connect_code', $this->connectType())
                    ->where('open_id', $uid)
                    ->leftJoin('users', 'users.user_id', 'connect_user.user_id')
                    ->first();

                // 生成（更新）密码
                $ec_salt = rand(1000, 9999);
                $password = $this->compile_password(['password' => $password, 'ec_salt' => $ec_salt]);

                // 首次注册或其他应用用户
                if (is_null($connect_user)) {
                    if (is_null($local_user)) {

                        $last_ip = app(DscRepository::class)->dscIp();

                        $user_id = DB::table('users')->insertGetId(
                            [
                                'user_name' => $username,
                                'password' => $password,
                                'ec_salt' => $ec_salt,
                                'mobile_phone' => $other['mobile_phone'],
                                'email' => $other['email'],
                                'reg_time' => gmtime(),
                                'last_login' => gmtime(),
                                'last_ip' => $last_ip
                            ]
                        );
                    } else {
                        $user_id = collect($local_user)->get('user_id');
                    }

                    DB::table('connect_user')->insert(
                        ['connect_code' => $this->connectType(), 'user_id' => $user_id, 'open_id' => $uid, 'create_at' => gmtime()]
                    );
                } else {
                    DB::table('users')->where('user_id', collect($local_user)->get('user_id'))
                        ->update(['password' => $password, 'ec_salt' => $ec_salt]);
                }

                return true;
            }

            return false;
        }
    }

    /**
     * 检查指定用户是否存在及密码是否正确
     * @param string $username
     * @param null $password
     * @return bool|int
     */
    public function check_user($username, $password = null)
    {
        $ucresult = Api::uc_user_checkname($username);
        if ($ucresult > 0) {
            // 用户名可用
            return true;
        } elseif ($ucresult == -1) {
            //echo '用户名不合法';
            $this->error = ERR_INVALID_USERNAME;
            return false;
        } elseif ($ucresult == -2) {
            //echo '包含要允许注册的词语';
            $this->error = ERR_INVALID_USERNAME;
            return false;
        } elseif ($ucresult == -3) {
            //echo '用户名已经存在';
            $this->error = ERR_USERNAME_EXISTS;
            return false;
        }
    }

    /**
     * 编辑用户信息
     * @param array $cfg
     * @param string $forget_pwd
     * @return bool|void
     */
    public function edit_user($cfg, $forget_pwd = '0')
    {
        if (isset($cfg['mobile_phone']) && $cfg['mobile_phone']) {
            $cfg['username'] = $cfg['mobile_phone'];
        }

        return parent::edit_user($cfg, $forget_pwd);
    }

    /**
     * 获取指定用户的信息
     * @param $username
     * @return array|\Illuminate\Database\Eloquent\Model|null|object|static
     */
    public function get_profile_by_name($username)
    {
        // 本地会员，通过手机号码换取用户名
        if (is_mobile($username)) {
            $condition['mobile_phone'] = $username;
        } elseif (is_email($username)) {
            $condition['email'] = $username;
        } else {
            $condition['user_name'] = $username;
        }

        $local_user = DB::table('users')
            ->select('user_id', 'user_name', 'password', 'email', 'mobile_phone', 'sex', 'reg_time', 'ec_salt')
            ->where(key($condition), current($condition))
            ->first();

        $local_user = collect($local_user)->all();

        return $local_user;
    }

    public function get_user_info($username)
    {
        return $this->get_profile_by_name($username);
    }

    /**
     * 注销登录
     */
    public function logout()
    {
        return parent::logout();
    }

    /**
     * 插件类型
     * @return string
     */
    protected function connectType()
    {
        return 'ecjiauc';
    }
}
