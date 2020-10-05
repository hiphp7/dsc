<?php

namespace App\Plugins\Integrates;

use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\SessionRepository;

/**
 * 用户整合插件基类
 * Class Integrate
 * @package App\Plugins\Integrates
 */
class Integrate
{
    /* 整合对象使用的数据库主机 */
    public $db_host = '';

    /* 整合对象使用的数据库名 */
    public $db_name = '';

    /* 整合对象使用的数据库用户名 */
    public $db_user = '';

    /* 整合对象使用的数据库密码 */
    public $db_pass = '';

    /* 整合对象数据表前缀 */
    public $prefix = '';

    /* 数据库所使用编码 */
    public $charset = '';

    /* 整合对象使用的cookie的domain */
    public $cookie_domain = '';

    /* 整合对象使用的cookie的path */
    public $cookie_path = '/';

    /* 整合对象会员表名 */
    public $user_table = '';

    /* 会员ID的字段名 */
    public $field_id = '';

    /* 会员名称的字段名 */
    public $field_name = '';

    /* 会员密码的字段名 */
    public $field_pass = '';

    /* 会员邮箱的字段名 */
    public $field_email = '';

    /* 会员手机的字段名 ecmoban模板堂 --zhuo */
    public $field_phone = '';

    /* 会员性别 */
    public $field_gender = '';

    /* 会员生日 */
    public $field_bday = '';

    /* 注册日期的字段名 */
    public $field_reg_date = '';

    /* 是否需要同步数据到商城 */
    public $need_sync = true;

    public $error = 0;

    protected $db;

    /**
     * Integrate constructor.
     * @param array $cfg
     */
    public function __construct($cfg = [])
    {
        $this->charset = isset($cfg['db_charset']) ? $cfg['db_charset'] : 'UTF8';
        $this->prefix = isset($cfg['prefix']) ? $cfg['prefix'] : '';
        $this->db_name = isset($cfg['db_name']) ? $cfg['db_name'] : '';
        $this->cookie_domain = isset($cfg['cookie_domain']) ? $cfg['cookie_domain'] : '';
        $this->cookie_path = isset($cfg['cookie_path']) ? $cfg['cookie_path'] : '/';
        $this->need_sync = true;

        /* 初始化数据库 */
        if (empty($cfg['db_host'])) {
            $this->db_name = $GLOBALS['dsc']->db_name;
            $this->prefix = $GLOBALS['dsc']->prefix;
            $this->db = &$GLOBALS['db'];
        }
    }

    /**
     * 用户登录函数
     * @param $username
     * @param $password
     * @param null $remember
     * @param int $is_oath
     * @return bool
     */
    public function login($username, $password, $remember = null, $is_oath = 1)
    {
        if ($this->check_user($username, $password) > 0) {
            if ($this->need_sync) {
                $this->sync($username, $password);
            }

            if ($is_oath == 1) {
                $this->set_session($username);
                $this->set_cookie($username, $remember);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * 注销登录
     */
    public function logout()
    {
        $this->set_cookie(); //清除cookie
        $this->set_session(); //清除session
    }

    /**
     * 添加一个新用户
     * @param $username
     * @param $password
     * @param $registerMode_info
     * @param int $gender
     * @param int $bday
     * @param int $reg_date
     * @param string $md5password
     * @return bool
     */
    public function add_user($username, $password, $registerMode_info, $gender = -1, $bday = 0, $reg_date = 0, $md5password = '')
    {
        /* 将用户添加到整合方 */
        if ($this->check_user($username) > 0) {
            $this->error = ERR_USERNAME_EXISTS;

            return false;
        }

        /* 检查email是否重复 */
        if (empty($registerMode_info['register_mode']) && !empty($registerMode_info['email'])) {
            $sql = "SELECT " . $this->field_id .
                " FROM " . $this->table($this->user_table) .
                " WHERE " . $this->field_email . " = '$registerMode_info[email]'";

            if ($this->db->getOne($sql, true) > 0) {
                $this->error = ERR_EMAIL_EXISTS;

                return false;
            }
        } else {
            $sql = "SELECT " . $this->field_id .
                " FROM " . $this->table($this->user_table) .
                " WHERE " . $this->field_phone . " = '$registerMode_info[mobile_phone]'";

            if ($this->db->getOne($sql, true) > 0) {
                $this->error = ERR_PHONE_EXISTS;

                return false;
            }
        }

        $post_username = $username;

        if ($md5password) {
            $post_password = $this->compile_password(['md5password' => $md5password]);
        } else {
            $post_password = $this->compile_password(['password' => $password]);
        }

        $fields = [$this->field_name, $this->field_phone, $this->field_email, $this->field_pass];
        $values = [$post_username, $registerMode_info['mobile_phone'], $registerMode_info['email'], $post_password];

        if ($gender > -1) {
            $fields[] = $this->field_gender;
            $values[] = $gender;
        }
        if ($bday) {
            $fields[] = $this->field_bday;
            $values[] = $bday;
        }
        if ($reg_date) {
            $fields[] = $this->field_reg_date;
            $values[] = $reg_date;
        }

        $sql = "INSERT INTO " . $this->table($this->user_table) .
            " (" . implode(',', $fields) . ")" .
            " VALUES ('" . implode("', '", $values) . "')";

        $this->db->query($sql);

        if ($this->need_sync) {
            $this->sync($username, $password);
        }

        return true;
    }

    /**
     * 编辑用户信息($password, $email, $gender, $bday)
     * @param $cfg
     * @param string $forget_pwd
     * @return bool
     */
    public function edit_user($cfg, $forget_pwd = '0')
    {
        if (empty($cfg['username'])) {
            return false;
        } else {
            $cfg['post_username'] = $cfg['username'];
        }

        $values = [];
        if (!empty($cfg['password']) && empty($cfg['md5password'])) {
            $cfg['md5password'] = md5($cfg['password']);
        }
        if ((!empty($cfg['md5password'])) && $this->field_pass != 'NULL') {
            $values[] = $this->field_pass . "='" . $this->compile_password(['md5password' => $cfg['md5password']]) . "'";
            $values[] = '`ec_salt` = 0';
        }
        if ((!empty($cfg['email'])) && $this->field_email != 'NULL') {
            /* 检查email是否重复 */
            $sql = "SELECT " . $this->field_id .
                " FROM " . $this->table($this->user_table) .
                " WHERE " . $this->field_email . " = '$cfg[email]' " .
                " AND " . $this->field_id . " != '$cfg[user_id]'";
            if ($this->db->getOne($sql, true) > 0) {
                $this->error = ERR_EMAIL_EXISTS;

                return false;
            }
            // 检查是否为新E-mail
            $sql = "SELECT count(*)" .
                " FROM " . $this->table($this->user_table) .
                " WHERE " . $this->field_email . " = '$cfg[email]' ";
            if ($this->db->getOne($sql, true) == 0) {
                // 新的E-mail
                $sql = "UPDATE " . $GLOBALS['dsc']->table($this->user_table) . " SET is_validated = 0 WHERE user_name = '$cfg[post_username]'";
                $this->db->query($sql);
            }
            $values[] = $this->field_email . "='" . $cfg['email'] . "'";
        }
        if ((!empty($cfg['mobile_phone'])) && $this->field_phone != 'NULL') {
            /* 检查mobile_phone是否重复 */
            $sql = "SELECT " . $this->field_id .
                " FROM " . $this->table($this->user_table) .
                " WHERE " . $this->field_phone . " = '$cfg[mobile_phone]' " .
                " AND " . $this->field_id . " != '$cfg[user_id]'";
            if ($this->db->getOne($sql, true) > 0) {
                $this->error = ERR_PHONE_EXISTS;

                return false;
            }
            $values[] = $this->field_phone . "='" . $cfg['mobile_phone'] . "'";
        }

        if (isset($cfg['gender']) && $this->field_gender != 'NULL') {
            $values[] = $this->field_gender . "='" . $cfg['gender'] . "'";
        }

        if ((!empty($cfg['bday'])) && $this->field_bday != 'NULL') {
            $values[] = $this->field_bday . "='" . $cfg['bday'] . "'";
        }

        if ($values) {
            $sql = "UPDATE " . $this->table($this->user_table) .
                " SET " . implode(', ', $values) .
                " WHERE " . $this->field_id . "='" . $cfg['user_id'] . "' LIMIT 1";

            $this->db->query($sql);

            if ($this->need_sync) {
                if (empty($cfg['md5password'])) {
                    $this->sync($cfg['username']);
                } else {
                    $this->sync($cfg['username'], '', $cfg['md5password']);
                }
            }
        }

        /* 判断是否检验原始密码 */
        if (isset($cfg['old_password']) && !empty($cfg['old_password']) && !empty($cfg['post_username'])) {
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table($this->user_table) . " WHERE user_name = '" . $cfg['post_username'] . "' AND 'password' = '" . $cfg['old_password'] . "'";
            if ($this->db->getOne($sql, true) > 0) {
                return true;
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * 删除用户
     * @param $post_id
     */
    public function remove_user($post_id)
    {
        if (!is_array($post_id)) {
            $post_id = [$post_id];
        }

        if ($this->need_sync || (isset($this->is_dscmall) && $this->is_dscmall)) {
            /* 如果需要同步或是dscmall插件执行这部分代码 */
            $sql = "SELECT user_id FROM " . $GLOBALS['dsc']->table($this->user_table) . " WHERE ";
            $sql .= (is_array($post_id)) ? db_create_in($post_id, 'user_name') : "user_name='" . $post_id . "' LIMIT 1";
            $col = $GLOBALS['db']->getCol($sql);

            if ($col) {
                $sql = "UPDATE " . $GLOBALS['dsc']->table($this->user_table) . " SET parent_id = 0 WHERE " . db_create_in($col, 'parent_id'); //将删除用户的下级的parent_id 改为0
                $GLOBALS['db']->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['dsc']->table($this->user_table) . " WHERE " . db_create_in($col, 'user_id'); //删除用户
                $GLOBALS['db']->query($sql);
                /* 删除用户订单 */
                $sql = "SELECT order_id FROM " . $GLOBALS['dsc']->table('order_info') . " WHERE " . db_create_in($col, 'user_id');
                $GLOBALS['db']->query($sql);
                $col_order_id = $GLOBALS['db']->getCol($sql);
                if ($col_order_id) {
                    $sql = "DELETE FROM " . $GLOBALS['dsc']->table('order_info') . " WHERE " . db_create_in($col_order_id, 'order_id');
                    $GLOBALS['db']->query($sql);
                    $sql = "DELETE FROM " . $GLOBALS['dsc']->table('order_goods') . " WHERE " . db_create_in($col_order_id, 'order_id');
                    $GLOBALS['db']->query($sql);
                }

                /* 删除用户白条 */
                $sql = "SELECT baitiao_id FROM " . $GLOBALS['dsc']->table('baitiao') . " WHERE " . db_create_in($col, 'user_id');
                $GLOBALS['db']->query($sql);
                $col_baitiao_id = $GLOBALS['db']->getCol($sql);
                if ($col_baitiao_id) {
                    $sql = "DELETE FROM " . $GLOBALS['dsc']->table('baitiao_log') . " WHERE " . db_create_in($col_baitiao_id, 'baitiao_id'); //删除白条记录
                    $GLOBALS['db']->query($sql);
                    $sql = "DELETE FROM " . $GLOBALS['dsc']->table('baitiao_pay_log') . " WHERE " . db_create_in($col_baitiao_id, 'baitiao_id'); //删除白条支付记录
                    $GLOBALS['db']->query($sql);
                }

                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('booking_goods') . " WHERE " . db_create_in($col, 'user_id'); //删除用户
                $GLOBALS['db']->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('collect_goods') . " WHERE " . db_create_in($col, 'user_id'); //删除会员收藏商品
                $GLOBALS['db']->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('collect_store') . " WHERE " . db_create_in($col, 'user_id'); //删除会员收藏店铺
                $GLOBALS['db']->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('collect_brand') . " WHERE " . db_create_in($col, 'user_id'); //删除会员收藏品牌
                $GLOBALS['db']->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('feedback') . " WHERE " . db_create_in($col, 'user_id'); //删除用户留言
                $GLOBALS['db']->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('user_address') . " WHERE " . db_create_in($col, 'user_id'); //删除用户地址
                $GLOBALS['db']->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('user_bonus') . " WHERE " . db_create_in($col, 'user_id'); //删除用户红包
                $GLOBALS['db']->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('user_account') . " WHERE " . db_create_in($col, 'user_id'); //删除用户帐号金额
                $GLOBALS['db']->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('tag') . " WHERE " . db_create_in($col, 'user_id'); //删除用户标记
                $GLOBALS['db']->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('account_log') . " WHERE " . db_create_in($col, 'user_id'); //删除用户日志
                $GLOBALS['db']->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('value_card') . " WHERE " . db_create_in($col, 'user_id'); //删除用户储值卡
                $GLOBALS['db']->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('coupons_user') . " WHERE " . db_create_in($col, 'user_id'); //删除用户优惠券
                $GLOBALS['db']->query($sql);

                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('users_vat_invoices_info') . " WHERE " . db_create_in($col, 'user_id'); //删除用户发票信息
                $GLOBALS['db']->query($sql);

                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('user_bank') . " WHERE " . db_create_in($col, 'user_id'); //删除用户银行卡
                $GLOBALS['db']->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('users_paypwd') . " WHERE " . db_create_in($col, 'user_id'); //删除用户支付密码
                $GLOBALS['db']->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('users_real') . " WHERE " . db_create_in($col, 'user_id'); //删除用户实名认证信息
                $GLOBALS['db']->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('users_auth') . " WHERE " . db_create_in($col, 'user_id'); //删除第三方会员绑定表
                $GLOBALS['db']->query($sql);

                $sql = "DELETE FROM " . $GLOBALS['dsc']->table('connect_user') . " WHERE " . db_create_in($col, 'user_id'); //删除第三方会员绑定表
                $GLOBALS['db']->query($sql);

                if (file_exists(MOBILE_WECHAT)) {
                    // 删除微信通关联表
                    $sql = "SELECT uid FROM " . $GLOBALS['dsc']->table('wechat_user') . " WHERE " . db_create_in($col, 'ect_uid');
                    $GLOBALS['db']->query($sql);
                    $col_uid = $GLOBALS['db']->getCol($sql);
                    if ($col_uid) {
                        // 微信粉丝普通消息记录
                        $sql = "DELETE FROM " . $GLOBALS['dsc']->table('wechat_custom_message') . " WHERE " . db_create_in($col_uid, 'uid');
                        $GLOBALS['db']->query($sql);
                    }

                    $sql = "SELECT openid FROM " . $GLOBALS['dsc']->table('wechat_user') . " WHERE " . db_create_in($col, 'ect_uid');
                    $GLOBALS['db']->query($sql);
                    $col_openid = $GLOBALS['db']->getCol($sql);
                    if ($col_openid) {
                        // 微信粉丝标签
                        $sql = "DELETE FROM " . $GLOBALS['dsc']->table('wechat_user_tag') . " WHERE " . db_create_in($col_openid, 'openid');
                        $GLOBALS['db']->query($sql);
                        // 微信粉丝中奖记录
                        $sql = "DELETE FROM " . $GLOBALS['dsc']->table('wechat_prize') . " WHERE " . db_create_in($col_openid, 'openid');
                        $GLOBALS['db']->query($sql);
                        // 微信粉丝签到记录
                        $sql = "DELETE FROM " . $GLOBALS['dsc']->table('wechat_point') . " WHERE " . db_create_in($col_openid, 'openid');
                        $GLOBALS['db']->query($sql);
                        // 微信粉丝模板消息记录
                        $sql = "DELETE FROM " . $GLOBALS['dsc']->table('wechat_template_log') . " WHERE " . db_create_in($col_openid, 'openid');
                        $GLOBALS['db']->query($sql);
                    }

                    $sql = "DELETE FROM " . $GLOBALS['dsc']->table('wechat_user') . " WHERE " . db_create_in($col, 'ect_uid'); //删除微信通会员
                    $GLOBALS['db']->query($sql);
                }

                if (file_exists(MOBILE_DRP)) {
                    $sql = "DELETE FROM " . $GLOBALS['dsc']->table('drp_shop') . " WHERE " . db_create_in($col, 'user_id'); //删除分销会员
                    $GLOBALS['db']->query($sql);

                    $sql = "DELETE FROM " . $GLOBALS['dsc']->table('drp_affiliate_log') . " WHERE " . db_create_in($col, 'user_id'); //删除分销会员分成记录 已丢弃
                    $GLOBALS['db']->query($sql);

                    $sql = "DELETE FROM " . $GLOBALS['dsc']->table('drp_log') . " WHERE " . db_create_in($col, 'user_id'); //删除分销会员分成记录
                    $GLOBALS['db']->query($sql);

                    $sql = "DELETE FROM " . $GLOBALS['dsc']->table('drp_type') . " WHERE " . db_create_in($col, 'user_id'); //删除分销会员商品分类记录
                    $GLOBALS['db']->query($sql);

                    $sql = "DELETE FROM " . $GLOBALS['dsc']->table('drp_transfer_log') . " WHERE " . db_create_in($col, 'user_id'); //删除分销会员提现记录
                    $GLOBALS['db']->query($sql);
                }
            }
        }

        if (isset($this->dscmall) && $this->dscmall) {
            /* 如果是dscmall插件直接退出 */
            return;
        }

        $sql = "DELETE FROM " . $this->table($this->user_table) . " WHERE ";
        if (is_array($post_id)) {
            $sql .= db_create_in($post_id, $this->field_name);
        } else {
            $sql .= $this->field_name . "='" . $post_id . "' LIMIT 1";
        }

        $this->db->query($sql);
    }

    /**
     * 获取指定用户的信息
     * @param $username
     * @return mixed
     */
    public function get_profile_by_name($username)
    {
        $post_username = $username;

        $sql = "SELECT " . $this->field_id . " AS user_id," . $this->field_name . " AS user_name," .
            $this->field_email . " AS email," . $this->field_gender . " AS sex," .
            $this->field_bday . " AS birthday," . $this->field_reg_date . " AS reg_time, " .
            $this->field_pass . " AS password " .
            " FROM " . $this->table($this->user_table) .
            " WHERE " . $this->field_name . "='$post_username'";
        $row = $this->db->getRow($sql);

        return $row;
    }

    /**
     * 获取指定用户的信息
     * @param $id
     * @return mixed
     */
    public function get_profile_by_id($id)
    {
        $sql = "SELECT " . $this->field_id . " AS user_id," . $this->field_name . " AS user_name," .
            $this->field_email . " AS email," . $this->field_gender . " AS sex," .
            $this->field_bday . " AS birthday," . $this->field_reg_date . " AS reg_time, " .
            $this->field_pass . " AS password " .
            " FROM " . $this->table($this->user_table) .
            " WHERE " . $this->field_id . "='$id'";
        $row = $this->db->getRow($sql);

        return $row;
    }

    /**
     * 根据登录状态设置cookie
     * @return bool
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
     * 检查指定用户是否存在及密码是否正确
     * @param $username
     * @param string $password
     * @return mixed
     */
    public function check_user($username, $password = '')
    {
        /* 是否邮箱 */
        $is_email = app(CommonRepository::class)->getMatchEmail($username);

        /* 是否手机 */
        $is_phone = app(CommonRepository::class)->getMatchPhone($username);

        $is_name = 0;
        if ($is_email) {
            $field_name = $this->field_email . " = '$username'";
        } elseif ($is_phone) {
            $is_name = 1;
            $field_name = $this->field_phone . " = '$username'";
        } else {
            $field_name = $this->field_name . " = '$username'";
        }

        $row = $this->check_field_name($field_name);
        if (empty($row)) {
            if ($is_name == 1) {
                $field = $this->field_name . " = '$username'";
                $row = $this->check_field_name($field);

                if ($row) {
                    $field_name = $this->field_name . " = '$username'";
                } else {
                    $field_name = $this->field_phone . " = '$username'";
                }
            }
        }

        $sql = "SELECT " . $this->field_id .
            " FROM " . $this->table($this->user_table) .
            " WHERE " . $field_name . " AND " . $this->field_pass . " ='" . $this->compile_password(['password' => $password]) . "'";

        return $this->db->getOne($sql);
    }

    /**
     * 检查指定邮箱是否存在
     * @param $email
     * @return bool
     */
    public function check_email($email)
    {
        if (!empty($email)) {
            /* 检查email是否重复 */
            $sql = "SELECT " . $this->field_id .
                " FROM " . $this->table($this->user_table) .
                " WHERE " . $this->field_email . " = '$email' ";
            if ($this->db->getOne($sql, true) > 0) {
                $this->error = ERR_EMAIL_EXISTS;
                return true;
            }
            return false;
        }
    }

    /**
     * 检查指定手机是否存在
     * @param $phone
     * @return bool
     */
    public function check_mobile_phone($phone)
    {
        if (!empty($phone)) {
            /* 检查mobile_phone是否重复 */
            $sql = "SELECT " . $this->field_id .
                " FROM " . $this->table($this->user_table) .
                " WHERE " . $this->field_phone . " = '$phone' ";

            if ($this->db->getOne($sql, true) > 0) {
                $this->error = ERR_PHONE_EXISTS;
                return true;
            }
            return false;
        }
    }

    /**
     * 检查cookie是正确，返回用户名
     * @return string
     */
    public function check_cookie()
    {
        return '';
    }

    /**
     * 设置cookie
     *
     * @param string $username
     * @param null $remember
     * @param int $is_oath
     * @param string $unionid
     * @return array|null|string
     */
    public function set_cookie($username = '', $remember = null, $is_oath = 1, $unionid = '')
    {
        //防止user_name一样，造成串号
        $where = '';
        $select = "";
        $leftJoin = "";
        if ($unionid) {
            $where = " conu.open_id = '$unionid'";
            $select = ", conu.open_id";
            $leftJoin = " LEFT JOIN " . $GLOBALS['dsc']->table('connect_user') . " AS conu ON u.user_id = conu.user_id";
        }

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
                /* 是否邮箱 */
                $is_email = app(CommonRepository::class)->getMatchEmail($username);

                /* 是否手机 */
                $is_phone = app(CommonRepository::class)->getMatchPhone($username);

                $is_name = 0;
                if ($is_email) {
                    $field_name = "u.email = '$username'";
                } elseif ($is_phone) {
                    $is_name = 1;
                    $field_name = "u.mobile_phone = '$username'";
                } else {
                    $field_name = "u.user_name = '$username'";
                }

                $row = $this->check_field_name($field_name, 'u');
                if (empty($row)) {
                    if ($is_name == 1) {
                        $field = "u.user_name = '$username'";
                        $row = $this->check_field_name($field, 'u');

                        if ($row) {
                            $field_name = "u.user_name = '$username'";
                        } else {
                            $field_name = "u.mobile_phone = '$username'";
                        }
                    }
                }

                if (empty($unionid)) {
                    $where = $field_name;
                }

                $sql = "SELECT u.user_id, u.user_name, u.nick_name, u.password, u.email " . $select . " FROM " . $GLOBALS['dsc']->table($this->user_table) . " AS u " .
                    $leftJoin .
                    " WHERE $where LIMIT 1";
                $row = $GLOBALS['db']->getRow($sql);
            } else {
                if (empty($unionid)) {
                    $where = " u.user_name = '$username'";
                }

                $sql = "SELECT u.user_id, u.user_name, u.nick_name, u.password, u.email " . $select . " FROM " . $GLOBALS['dsc']->table($this->user_table) . " AS u " .
                    $leftJoin .
                    " WHERE $where LIMIT 1";
                $row = $GLOBALS['db']->getRow($sql);
            }

            if ($row) {

//                if ($remember) {
//                    /* 设置cookie */
//                    $time = gmtime() + 3600 * 24 * 365;
//                } else {
//                    /* 设置cookie */
//                    $time = gmtime() + 3600 * 24 * 1;
//                }
                //取消记住密码 保持会话有效
                /* 设置cookie */

//                cookie()->queue('ECS[username]', stripslashes($row['user_name']));
//                cookie()->queue('ECS[nick_name]', stripslashes($row['nick_name']));
//                cookie()->queue('ECS[user_id]', $row['user_id']);
//                cookie()->queue('ECS[password]', $row['password'], $time);//密码敏感 不存cookie

                return request()->cookie();
            }
        }
    }

    /**
     * 设置指定用户SESSION
     * @param string $username
     * @param int $is_oath
     * @param string $unionid
     */
    public function set_session($username = '', $is_oath = 1, $unionid = '')
    {
        //防止user_name一样，造成串号
        $where = '';
        $select = "";
        $leftJoin = "";
        if ($unionid) {
            $where = " conu.open_id = '$unionid'";
            $select = ", conu.open_id";
            $leftJoin = " LEFT JOIN " . $GLOBALS['dsc']->table('connect_user') . " AS conu ON u.user_id = conu.user_id";
        }

        if (empty($username)) {
            $sessionList = [
                'user_id',
                'user_name',
                'nick_name',
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

                $is_name = 0;
                if ($is_email) {
                    $field_name = "u.email = '$username'";
                } elseif ($is_phone) {
                    $is_name = 1;
                    $field_name = "u.mobile_phone = '$username'";
                } else {
                    $field_name = "u.user_name = '$username'";
                }

                $row = $this->check_field_name($field_name, 'u');
                if (empty($row)) {
                    if ($is_name == 1) {
                        $field = "u.user_name = '$username'";
                        $row = $this->check_field_name($field, 'u');

                        if ($row) {
                            $field_name = "u.user_name = '$username'";
                        } else {
                            $field_name = "u.mobile_phone = '$username'";
                        }
                    }
                }

                if (empty($unionid)) {
                    $where = $field_name;
                }

                $sql = "SELECT u.user_id, u.user_name, u.nick_name, u.password, u.email " . $select . " FROM " . $GLOBALS['dsc']->table($this->user_table) . " AS u " .
                    $leftJoin .
                    " WHERE $where LIMIT 1";
                $row = $GLOBALS['db']->getRow($sql);
            } else {
                if (empty($unionid)) {
                    $where = " u.user_name = '$username'";
                }

                $sql = "SELECT u.user_id, u.user_name, u.nick_name, u.password, u.email " . $select . " FROM " . $GLOBALS['dsc']->table($this->user_table) . " AS u " .
                    $leftJoin .
                    " WHERE $where LIMIT 1";
                $row = $GLOBALS['db']->getRow($sql);
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
     * 在给定的表名前加上数据库名以及前缀
     * @param $str
     * @return string
     */
    public function table($str)
    {
        return '`' . $this->db_name . '`.`' . $this->prefix . $str . '`';
    }

    /**
     * 编译密码函数
     * @param $cfg 包含参数为 $password, $md5password, $salt, $type
     * @return string
     */
    public function compile_password($cfg)
    {
        if (isset($cfg['password'])) {
            $cfg['md5password'] = md5($cfg['password']);
        }
        if (empty($cfg['type'])) {
            $cfg['type'] = PWD_MD5;
        }

        switch ($cfg['type']) {
            case PWD_MD5:
                if (!empty($cfg['ec_salt'])) {
                    return md5($cfg['md5password'] . $cfg['ec_salt']);
                } else {
                    return $cfg['md5password'];
                }

            // no break
            case PWD_PRE_SALT:
                if (empty($cfg['salt'])) {
                    $cfg['salt'] = '';
                }

                return md5($cfg['salt'] . $cfg['md5password']);

            case PWD_SUF_SALT:
                if (empty($cfg['salt'])) {
                    $cfg['salt'] = '';
                }

                return md5($cfg['md5password'] . $cfg['salt']);

            default:
                return '';
        }
    }

    /**
     * 会员同步
     * @param $username
     * @param string $password
     * @param string $md5password
     * @return bool
     */
    public function sync($username, $password = '', $md5password = '')
    {
        if ((!empty($password)) && empty($md5password)) {
            $md5password = md5($password);
        }

        $main_profile = $this->get_profile_by_name($username);

        if (empty($main_profile)) {
            return false;
        }

        $sql = "SELECT user_name, email, password, sex, birthday" .
            " FROM " . $GLOBALS['dsc']->table($this->user_table) .
            " WHERE user_name = '$username'";

        $profile = $GLOBALS['db']->getRow($sql);
        if (empty($profile)) {
            /* 向商城表插入一条新记录 */
            if (empty($md5password)) {
                $sql = "INSERT INTO " . $GLOBALS['dsc']->table($this->user_table) .
                    "(user_name, email, sex, birthday, reg_time)" .
                    " VALUES('$username', '" . $main_profile['email'] . "','" .
                    $main_profile['sex'] . "','" . $main_profile['birthday'] . "','" . $main_profile['reg_time'] . "')";
            } else {
                $sql = "INSERT INTO " . $GLOBALS['dsc']->table($this->user_table) .
                    "(user_name, email, sex, birthday, reg_time, password)" .
                    " VALUES('$username', '" . $main_profile['email'] . "','" .
                    $main_profile['sex'] . "','" . $main_profile['birthday'] . "','" .
                    $main_profile['reg_time'] . "', '$md5password')";
            }

            $GLOBALS['db']->query($sql);

            return true;
        } else {
            $values = [];
            if ($main_profile['email'] != $profile['email']) {
                $values[] = "email='" . $main_profile['email'] . "'";
            }
            if ($main_profile['sex'] != $profile['sex']) {
                $values[] = "sex='" . $main_profile['sex'] . "'";
            }
            if ($main_profile['birthday'] != $profile['birthday']) {
                $values[] = "birthday='" . $main_profile['birthday'] . "'";
            }
            if ((!empty($md5password)) && ($md5password != $profile['password'])) {
                $values[] = "password='" . $md5password . "'";
            }

            if (empty($values)) {
                return true;
            } else {
                $sql = "UPDATE " . $GLOBALS['dsc']->table($this->user_table) .
                    " SET " . implode(", ", $values) .
                    " WHERE user_name='$username'";

                $GLOBALS['db']->query($sql);

                return true;
            }
        }
    }

    /**
     * 获取论坛有效积分及单位
     * @return array
     */
    public function get_points_name()
    {
        return [];
    }

    /**
     * 获取用户积分
     * @param $username
     * @return bool
     */
    public function get_points($username)
    {
        $credits = $this->get_points_name();
        $fileds = array_keys($credits);
        if ($fileds) {
            $sql = "SELECT " . $this->field_id . ', ' . implode(', ', $fileds) .
                " FROM " . $this->table($this->user_table) .
                " WHERE " . $this->field_name . "='$username'";
            $row = $this->db->getRow($sql);
            return $row;
        } else {
            return false;
        }
    }

    /**
     * 设置用户积分
     * @param $username
     * @param $credits
     * @return bool
     */
    public function set_points($username, $credits)
    {
        $user_set = array_keys($credits);
        $points_set = array_keys($this->get_points_name());

        $set = array_intersect($user_set, $points_set);

        if ($set) {
            $tmp = [];
            foreach ($set as $credit) {
                $tmp[] = $credit . '=' . $credit . '+' . $credits[$credit];
            }
            $sql = "UPDATE " . $this->table($this->user_table) .
                " SET " . implode(', ', $tmp) .
                " WHERE " . $this->field_name . " = '$username'";
            $this->db->query($sql);
        }

        return true;
    }

    /**
     * 获取用户信息
     * @param $username
     * @return mixed
     */
    public function get_user_info($username)
    {
        return $this->get_profile_by_name($username);
    }

    /**
     * 检查有无重名用户，有则返回重名用户
     * @param $user_list
     * @return array
     */
    public function test_conflict($user_list)
    {
        if (empty($user_list)) {
            return [];
        }

        $sql = "SELECT " . $this->field_name . " FROM " . $this->table($this->user_table) . " WHERE " . db_create_in($user_list, $this->field_name);
        $user_list = $this->db->getCol($sql);

        return $user_list;
    }

    /**
     * 检查指定用户是否存在及密码是否正确(重载基类check_user函数，支持zc加密方法)
     * @param $field_name
     * @param string $alias
     * @return mixed
     */
    private function check_field_name($field_name, $alias = '')
    {
        $as = '';
        if (!empty($alias)) {
            $as = " AS " . $alias;
            $alias = $alias . ".";
        }

        $sql = "SELECT {$alias}user_id, {$alias}password, {$alias}salt, {$alias}ec_salt " .
            " FROM " . $this->table($this->user_table) . $as .
            " WHERE " . $field_name;

        $row = $this->db->getRow($sql);

        return $row;
    }
}
