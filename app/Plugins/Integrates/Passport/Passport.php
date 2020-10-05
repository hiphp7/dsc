<?php

namespace App\Plugins\Integrates\Passport;

use App\Plugins\Integrates\Integrate;
use App\Repositories\Common\CommonRepository;

/**
 * Class Passport
 * @package App\Plugins\Integrates\Passport
 */
class Passport extends Integrate
{
    public $is_dscmall = 1;

    /**
     * Passport constructor.
     * @param array $cfg
     */
    public function __construct($cfg = [])
    {
        parent::__construct();
        $this->user_table = 'users';
        $this->field_id = 'user_id';
        $this->ec_salt = 'ec_salt';
        $this->field_name = 'user_name';
        $this->field_pass = 'password';
        $this->field_email = 'email';
        $this->field_phone = 'mobile_phone';
        $this->field_gender = 'sex';
        $this->field_bday = 'birthday';
        $this->field_reg_date = 'reg_time';
        $this->need_sync = false;
        $this->is_dscmall = 1;
    }

    /**
     * 检查指定用户是否存在及密码是否正确(重载基类check_user函数，支持zc加密方法)
     * @param $username
     * @param string $password
     * @return int|mixed
     */
    public function check_user($username, $password = '')
    {
        if ($this->charset != 'UTF8') {
            $username = dsc_iconv('UTF8', $this->charset, $username);
        }

        /* 是否邮箱 */
        $is_email = app(CommonRepository::class)->getMatchEmail($username);

        /* 是否手机 */
        $is_phone = app(CommonRepository::class)->getMatchPhone($username);

        $is_name = 0;
        if ($is_email) {
            $field_name = "email = '$username'";
        } elseif ($is_phone) {
            $is_name = 1;
            $field_name = "mobile_phone = '$username'";
        } else {
            $field_name = "user_name = '$username'";
        }

        $row = $this->check_field_name($field_name);
        if (empty($row)) {
            if ($is_name == 1) {
                $field = "user_name = '$username'";
                $row = $this->check_field_name($field);

                if (empty($row)) {
                    return 0;
                }
            } else {
                return 0;
            }
        }

        if (empty($row['salt'])) {
            if (!empty($password) && ($row['password'] != $this->compile_password(['password' => $password, 'ec_salt' => $row['ec_salt']]))) {
                return 0;
            } else {
                if (empty($row['ec_salt'])) {
                    $ec_salt = rand(1, 9999);
                    $new_password = md5(md5($password) . $ec_salt);
                    $sql = "UPDATE " . $this->table($this->user_table) . "SET password = '" . $new_password . "',ec_salt = '" . $ec_salt . "'" .
                        " WHERE user_id = '" . $row['user_id'] . "'";

                    $this->db->query($sql);
                }
                return $row['user_id'];
            }
        } else {
            /* 如果salt存在，使用salt方式加密验证，验证通过洗白用户密码 */
            $encrypt_type = substr($row['salt'], 0, 1);
            $encrypt_salt = substr($row['salt'], 1);

            /* 计算加密后密码 */
            switch ($encrypt_type) {
                case ENCRYPT_ZC:
                    $encrypt_password = md5($encrypt_salt . $password);
                    break;

                case ENCRYPT_UC:
                    $encrypt_password = md5(md5($password) . $encrypt_salt);
                    break;

                default:
                    $encrypt_password = '';
            }

            if (!empty($password) && ($row['password'] != $encrypt_password)) {
                return 0;
            }

            $sql = "UPDATE " . $this->table($this->user_table) .
                " SET password = '" . $this->compile_password(['password' => $password]) . "', salt=''" .
                " WHERE user_id = '$row[user_id]'";
            $this->db->query($sql);

            return $row['user_id'];
        }
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

        return $this->db->getRow($sql);
    }
}
