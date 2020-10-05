<?php

namespace App\Plugins\Integrates\Ucenter;

use App\Models\ConnectUser;
use App\Models\Users;
use App\Plugins\Integrates\Integrate;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use Binaryoung\Ucenter\Facades\Ucenter as Api;

class Ucenter extends Integrate
{
    /**
     * Ucenter constructor.
     * @param $cfg
     */
    public function __construct($cfg)
    {
        parent::__construct([]);
        $this->user_table = 'users';
        $this->field_id = 'user_id';
        $this->field_name = 'user_name';
        $this->field_pass = 'password';
        $this->field_email = 'email';
        $this->field_gender = 'sex';
        $this->field_bday = 'birthday';
        $this->field_reg_date = 'reg_time';
        $this->need_sync = false;
        $this->is_dscmall = 1;
    }

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
        $username = addslashes($username);

        // 本地会员，通过手机号码换取用户名
        if (is_mobile($username)) {
            $condition['mobile_phone'] = $username;
        } elseif (is_email($username)) {
            $condition['email'] = $username;
        } else {
            $condition['user_name'] = $username;
        }

        $local_user = Users::where(key($condition), current($condition))
            ->first();

        // 用户登录方式 isuid（0：用户名，1：ID，2：电子邮箱）
        $isuid = 0;
        if (is_mobile($username)) {
            if (!is_null($local_user)) {
                $username = collect($local_user)->get('user_name');
            }
            $isuid = $this->connectType() == 'ecjiauc' ? 6 : $isuid; // 增加 ecjia ucenter 手机号登录
        } elseif (is_email($username)) {
            $isuid = 2;
        }

        list($uid, $uname, $pwd, $email, $repeat) = Api::uc_user_login($username, $password, $isuid);

        // uc远程登录失败后同步本地用户到uc
        if ($uid < 0 && !is_null($local_user)) {
            // 校验本地用户密码
            $ec_salt = collect($local_user)->get('ec_salt');
            $other = collect($local_user)->all();
            $local_password = ['password' => $password, 'ec_salt' => $ec_salt];

            // 密码匹配之后同步到uc
            if (collect($local_user)->get('password') == $this->compile_password($local_password)) {
                if ($this->add_user($username, $password, $other)) {
                    list($uid, $uname, $pwd, $email, $repeat) = Api::uc_user_login($username, $password, $isuid);
                }
            }
        }

        if ($uid > 0) {
            // 优先兼容 connect_user 表
            $connect_user = ConnectUser::where('connect_code', $this->connectType())
                ->where('open_id', $uid)
                ->leftJoin('users', 'users.user_id', 'connect_user.user_id')
                ->first();

            // 生成（更新）密码
            $ec_salt = rand(1000, 9999);
            $password = $this->compile_password(['password' => $password, 'ec_salt' => $ec_salt]);

            // 首次登录或其他应用用户
            if (is_null($connect_user)) {
                if (is_null($local_user)) {
                    $user_id = Users::insertGetId(
                        [
                            'user_name' => $uname,
                            'password' => $password,
                            'ec_salt' => $ec_salt,
                            'email' => $email,
                            'reg_time' => gmtime(),
                            'last_login' => gmtime(),
                            'last_ip' => app(DscRepository::class)->dscIp()
                        ]
                    );
                } else {
                    $user_id = collect($local_user)->get('user_id');
                }

                ConnectUser::insert(
                    ['connect_code' => $this->connectType(), 'user_id' => $user_id, 'open_id' => $uid, 'create_at' => gmtime()]
                );
            } else {
                if (!is_null($local_user)) {
                    Users::where('user_id', collect($local_user)->get('user_id'))
                        ->update(['password' => $password, 'ec_salt' => $ec_salt]);

                    $uname = collect($local_user)->get('user_name');
                } else {
                    $uname = '';
                }
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
     * 用户退出
     * @return bool|void
     */
    public function logout()
    {
        $this->set_cookie();  //清除cookie
        $this->set_session(); //清除session
        $this->ucdata = Api::uc_user_synlogout();   //同步退出
        return true;
    }

    /**
     * 添加用户
     * @param $username
     * @param $password
     * @param $other
     * @param int $gender
     * @param int $bday
     * @param int $reg_date
     * @param string $md5password
     * @return bool|int
     */
    public function add_user($username, $password, $other, $gender = -1, $bday = 0, $reg_date = 0, $md5password = '')
    {
        /* 检测用户名 */
        if ($this->check_user($username)) {
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
            //注册成功，插入用户表
            $ec_salt = rand(1000, 9999);
            $password = $this->compile_password(['password' => $password, 'ec_salt' => $ec_salt]);

            $user_id = Users::insertGetId(
                [
                    'user_name' => $username,
                    'password' => $password,
                    'ec_salt' => $ec_salt,
                    'mobile_phone' => $other['mobile_phone'],
                    'email' => $other['email'],
                    'reg_time' => gmtime(),
                    'last_login' => gmtime(),
                    'last_ip' => app(DscRepository::class)->dscIp()
                ]
            );

            ConnectUser::insert(
                ['connect_code' => $this->connectType(), 'user_id' => $user_id, 'open_id' => $uid, 'create_at' => gmtime()]
            );

            return true;
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
        $userdata = Api::uc_user_checkname($username);
        if ($userdata == 1) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 检测Email是否合法
     * @param string $email
     * @return bool
     */
    public function check_email($email)
    {
        if (!empty($email)) {
            $email_exist = Api::uc_user_checkemail($email);
            if ($email_exist == 1) {
                return false;
            } else {
                $this->error = ERR_EMAIL_EXISTS;
                return true;
            }
        }
        return true;
    }

    /**
     * 检查指定手机是否存在
     * @param string $phone
     * @return bool
     */
    public function check_mobile_phone($phone)
    {
        if (!empty($phone)) {

            $count = Users::where('mobile_phone', $phone)->count();

            if ($count > 0) {
                $this->error = ERR_PHONE_EXISTS;
                return true;
            }
        }
        return false;
    }

    /**
     * 编辑用户信息
     * @param $cfg
     * @param string $forget_pwd
     * @return bool|void
     */
    public function edit_user($cfg, $forget_pwd = '0')
    {
        $real_username = $cfg['username'];
        $cfg['username'] = addslashes($cfg['username']);

        $valarr = ['email' => 'email', 'gender' => 'sex', 'bday' => 'birthday'];

        $set_str = [];
        foreach ($cfg as $key => $val) {
            if ($key == 'username' || $key == 'password' || $key == 'old_password' || $key == 'user_id') {
                continue;
            }

            $set_str[$valarr[$key]] = $val;
        }

        if (!empty($set_str)) {
            Users::where('user_name', $cfg['username'])->update($set_str);
            $flag = true;
        }

        if (!empty($cfg['email'])) {
            $ucresult = Api::uc_user_edit($cfg['username'], '', '', $cfg['email'], 1);
            if ($ucresult > 0) {
                $flag = true;
            } elseif ($ucresult == -4) {
                //echo 'Email 格式有误';
                $this->error = ERR_INVALID_EMAIL;

                return false;
            } elseif ($ucresult == -5) {
                //echo 'Email 不允许注册';
                $this->error = ERR_INVALID_EMAIL;

                return false;
            } elseif ($ucresult == -6) {
                //echo '该 Email 已经被注册';
                $this->error = ERR_EMAIL_EXISTS;

                return false;
            } elseif ($ucresult < 0) {
                return false;
            }
        }
        if (!empty($cfg['old_password']) && !empty($cfg['password']) && $forget_pwd == 0) {
            $ucresult = Api::uc_user_edit($real_username, $cfg['old_password'], $cfg['password'], '');
            if ($ucresult > 0) {
                return true;
            } else {
                $this->error = ERR_INVALID_PASSWORD;
                return false;
            }
        } elseif (!empty($cfg['password']) && $forget_pwd == 1) {
            $ucresult = Api::uc_user_edit($real_username, '', $cfg['password'], '', '1');
            if ($ucresult > 0) {
                $flag = true;
            }
        }

        return true;
    }

    /**
     * 获取指定用户的信息
     *
     * @param $username
     * @return array|mixed
     */
    public function get_profile_by_name($username)
    {
        $row = Users::where('user_name', $username)->first();
        $row = $row ? $row->toArray() : [];

        return $row;
    }

    /**
     * 检查cookie是正确，返回用户名
     * @return string|void
     */
    public function check_cookie()
    {
        return '';
    }

    /**
     * 根据登录状态设置cookie
     * @return bool|void
     */
    public function get_cookie()
    {
        $id = $this->check_cookie();
        if ($id) {
            if ($this->need_sync) {
                $this->sync($id);
            }
            $this->set_session($id);

            return true;
        } else {
            return false;
        }
    }

    /**
     * 设置cookie
     *
     * @param string $username
     * @param int $email_phone
     * @param int $is_oath
     * @param string $unionid
     * @return array|null|string
     */
    public function set_cookie($username = '', $email_phone = 0, $is_oath = 1, $unionid = '')
    {
        $time = gmtime();
        if (empty($username)) {
            /* 摧毁cookie */
            $cookieList = [
                'ECS[user_id]',
                'ECS[username]',
                'ECS[nick_name]',
                'ECS[password]'
            ];
            app(SessionRepository::class)->destroy_cookie($cookieList);
        } else {
            if ($is_oath == 1) {
                /* 设置cookie */
                $time = 60 * 24 * 30;

                /* 是否邮箱 */
                $is_email = app(CommonRepository::class)->getMatchEmail($username);

                /* 是否手机 */
                $is_phone = app(CommonRepository::class)->getMatchPhone($username);

                $row = Users::whereRaw(1);

                $is_name = 0;
                if ($is_email) {
                    $row = $row->where('email', $username);

                    $field = [
                        'email'
                    ];

                    $fieldVal = [
                        $username
                    ];

                } elseif ($is_phone) {
                    $row = $row->where('mobile_phone', $username);

                    $field = [
                        'email'
                    ];

                    $fieldVal = [
                        $username
                    ];

                } else {
                    $is_name = 1;

                    $row = $row->where('user_name', $username);

                    $field = [
                        'email'
                    ];

                    $fieldVal = [
                        $username
                    ];
                }

                $fieldRow = $this->check_field_name($field, $fieldVal);
                if (empty($fieldRow)) {
                    if ($is_name == 1) {

                        $field = [
                            'user_name'
                        ];

                        $fieldVal = [
                            $username
                        ];

                        $fieldRow = $this->check_field_name($field, $fieldVal);

                        if ($fieldRow) {
                            $row = $row->where('user_name', $username);
                        } else {
                            $row = $row->where('mobile_phone', $username);
                        }
                    }
                }

                $row = $row->first();

                $row = $row ? $row->toArray() : [];

            } else {

                $where = [
                    'user_name' => $username
                ];

                //防止user_name一样，造成串号
                if ($unionid) {
                    $where['identifier'] = $unionid;
                }

                $row = Users::whereHas('getUsersAuth', function ($query) use ($where) {
                    $query = $query->where('user_name', $where['user_name']);

                    if ($where['identifier']) {
                        $query->where('identifier', $where['identifier']);
                    }
                });

                $row = $row->with([
                    'getUsersAuth' => function ($query) {
                        $query->select('user_id', 'identifier');
                    }
                ]);

                $row = $row->first();

                $row = $row ? $row->toArray() : [];

                if ($row) {
                    $row = $row['get_users_auth'] ? array_merge($row, $row['get_users_auth']) : $row;
                    unset($row['get_users_auth']);
                    $row['identifier'] = $row['identifier'] ?? '';
                }
            }

            if ($row) {
                cookie()->queue("ECS[username]", stripslashes($row['user_name']), $time);
                cookie()->queue("ECS[nick_name]", stripslashes($row['nick_name']), $time);
                cookie()->queue("ECS[user_id]", $row['user_id'], $time);
                cookie()->queue("ECS[password]", $row['password'], $time);
                cookie()->queue("ECS[email_phone]", $email_phone, $time);

                return request()->cookie();
            }
        }
    }

    /**
     *  设置指定用户SESSION
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function set_session($username = '', $is_oath = 1, $unionid = '')
    {
        if (empty($username)) {
            $sessionList = [
                'user_id',
                'user_name',
                'email',
                'user_rank',
                'discount'
            ];
            app(SessionRepository::class)->destroy_session($sessionList);
        } else {
            if ($is_oath == 1) {
                /* 是否邮箱 */
                $is_email = app(CommonRepository::class)->getMatchEmail($username);

                /* 是否手机 */
                $is_phone = app(CommonRepository::class)->getMatchPhone($username);

                $row = Users::whereRaw(1);

                $is_name = 0;
                if ($is_email) {
                    $row = $row->where('email', $username);

                    $field = [
                        'email'
                    ];

                    $fieldVal = [
                        $username
                    ];

                } elseif ($is_phone) {

                    $is_name = 1;

                    $row = $row->where('mobile_phone', $username);

                    $field = [
                        'email'
                    ];

                    $fieldVal = [
                        $username
                    ];

                } else {

                    $row = $row->where('user_name', $username);

                    $field = [
                        'email'
                    ];

                    $fieldVal = [
                        $username
                    ];
                }

                $fieldRow = $this->check_field_name($field, $fieldVal);

                if (empty($fieldRow)) {
                    if ($is_name == 1) {
                        $field = [
                            'user_name'
                        ];

                        $fieldVal = [
                            $username
                        ];

                        $fieldRow = $this->check_field_name($field, $fieldVal);

                        if ($fieldRow) {
                            $row = $row->where('user_name', $username);
                        } else {
                            $row = $row->where('mobile_phone', $username);
                        }
                    }
                }

                $row = $row->first();

                $row = $row ? $row->toArray() : [];

            } else {

                $where = [
                    'user_name' => $username
                ];

                //防止user_name一样，造成串号
                if ($unionid) {
                    $where['identifier'] = $unionid;
                }

                $row = Users::whereHas('getUsersAuth', function ($query) use ($where) {
                    $query = $query->where('user_name', $where['user_name']);

                    if ($where['identifier']) {
                        $query->where('identifier', $where['identifier']);
                    }
                });

                $row = $row->with([
                    'getUsersAuth' => function ($query) {
                        $query->select('user_id', 'identifier');
                    }
                ]);

                $row = $row->first();

                $row = $row ? $row->toArray() : [];

                if ($row) {
                    $row = $row['get_users_auth'] ? array_merge($row, $row['get_users_auth']) : $row;
                    unset($row['get_users_auth']);
                    $row['identifier'] = $row['identifier'] ?? '';
                }
            }

            if ($row) {
                session([
                    'user_id' => $row['user_id'],
                    'user_name' => stripslashes($row['user_name']),
                    'nick_name' => stripslashes($row['nick_name']),
                    'email' => $row['email']
                ]);
            }
        }
    }

    /**
     * 获取指定用户的信息
     *
     * @param $id
     * @return array|mixed
     */
    public function get_profile_by_id($id)
    {
        $row = Users::where('user_id', $id)->first();
        $row = $row ? $row->toArray() : [];

        return $row;
    }

    public function get_user_info($username)
    {
        return $this->get_profile_by_name($username);
    }

    /**
     * 删除用户
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function remove_user($username)
    {
        $connect_user = ConnectUser::where('connect_code', $this->connectType());

        $connect_user = $connect_user->whereHas('getUsers', function ($query) use ($username) {
            $query->where('user_name', $username);
        });

        $connect_user = $connect_user->first();

        // 移除 Ucenter 用户
        if (!is_null($connect_user)) {
            $open_id = collect($connect_user)->get('open_id');
            Api::uc_user_delete($open_id);
        }

        // 移除本地用户
        parent::remove_user($username);
    }

    /**
     *  获取论坛有效积分及单位
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function get_points_name()
    {
        return 'ucenter';
    }

    /**
     * 插件类型
     * @return string
     */
    protected function connectType()
    {
        return 'ucenter';
    }

    /**
     * 检查指定用户是否存在及密码是否正确(重载基类check_user函数，支持zc加密方法)
     *
     * @param array $field
     * @param array $val
     * @return mixed
     */
    private function check_field_name($field = [], $val = [])
    {

        $row = Users::whereRaw(1);

        foreach ($field as $k => $v) {
            $row = $row->where($v, $val[$k]);
        }

        $row = $row->first();

        $row = $row ? $row->toArray() : [];

        return $row;
    }
}
