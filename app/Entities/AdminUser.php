<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AdminUser
 */
class AdminUser extends Model
{
    protected $table = 'admin_user';

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $fillable = [
        'user_name',
        'parent_id',
        'ru_id',
        'rs_id',
        'email',
        'password',
        'ec_salt',
        'add_time',
        'last_login',
        'last_ip',
        'action_list',
        'nav_list',
        'lang_type',
        'agency_id',
        'suppliers_id',
        'todolist',
        'role_id',
        'major_brand',
        'admin_user_img',
        'recently_cat',
        'login_status'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * @return mixed
     */
    public function getRuId()
    {
        return $this->ru_id;
    }

    /**
     * @return mixed
     */
    public function getRsId()
    {
        return $this->rs_id;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    public function getEcSalt()
    {
        return $this->ec_salt;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @return mixed
     */
    public function getLastLogin()
    {
        return $this->last_login;
    }

    /**
     * @return mixed
     */
    public function getLastIp()
    {
        return $this->last_ip;
    }

    /**
     * @return mixed
     */
    public function getActionList()
    {
        return $this->action_list;
    }

    /**
     * @return mixed
     */
    public function getNavList()
    {
        return $this->nav_list;
    }

    /**
     * @return mixed
     */
    public function getLangType()
    {
        return $this->lang_type;
    }

    /**
     * @return mixed
     */
    public function getAgencyId()
    {
        return $this->agency_id;
    }

    /**
     * @return mixed
     */
    public function getSuppliersId()
    {
        return $this->suppliers_id;
    }

    /**
     * @return mixed
     */
    public function getTodolist()
    {
        return $this->todolist;
    }

    /**
     * @return mixed
     */
    public function getRoleId()
    {
        return $this->role_id;
    }

    /**
     * @return mixed
     */
    public function getMajorBrand()
    {
        return $this->major_brand;
    }

    /**
     * @return mixed
     */
    public function getAdminUserImg()
    {
        return $this->admin_user_img;
    }

    /**
     * @return mixed
     */
    public function getRecentlyCat()
    {
        return $this->recently_cat;
    }

    /**
     * @return mixed
     */
    public function getLoginStatus()
    {
        return $this->login_status;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserName($value)
    {
        $this->user_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setParentId($value)
    {
        $this->parent_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRuId($value)
    {
        $this->ru_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRsId($value)
    {
        $this->rs_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEmail($value)
    {
        $this->email = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPassword($value)
    {
        $this->password = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEcSalt($value)
    {
        $this->ec_salt = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddTime($value)
    {
        $this->add_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLastLogin($value)
    {
        $this->last_login = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLastIp($value)
    {
        $this->last_ip = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActionList($value)
    {
        $this->action_list = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setNavList($value)
    {
        $this->nav_list = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLangType($value)
    {
        $this->lang_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAgencyId($value)
    {
        $this->agency_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSuppliersId($value)
    {
        $this->suppliers_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTodolist($value)
    {
        $this->todolist = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRoleId($value)
    {
        $this->role_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMajorBrand($value)
    {
        $this->major_brand = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdminUserImg($value)
    {
        $this->admin_user_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRecentlyCat($value)
    {
        $this->recently_cat = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLoginStatus($value)
    {
        $this->login_status = $value;
        return $this;
    }
}
