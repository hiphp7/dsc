<?php

namespace App\Plugins\Dscapi\app\controller;

use App\Plugins\Dscapi\app\func\common;
use App\Plugins\Dscapi\app\func\base;
use App\Plugins\Dscapi\app\model\userModel;
use App\Plugins\Dscapi\languages\userLang;

class user extends userModel
{
    private $table;                          //表名称
    private $user_select = array();          //查询字段数组
    private $select;                         //查询字段字符串组
    private $user_id = 0;                    //会员ID
    private $user_name = 0;                  //会员名称
    private $mobile = '';                    //手机号
    private $rank_id = '';                   //等级ID
    private $rank_name = '';                 //等级名称
    private $address_id = '';                //收货地址ID
    private $format = 'json';                //返回格式（json, xml, array）
    private $page_size = 10;                 //每页条数
    private $page = 1;                       //当前页
    private $where_val;                      //查询条件
    private $userLangList;                   //语言包
    private $sort_by;                        //排序字段
    private $sort_order;                     //排序升降

    public function __construct($where = array())
    {
        $this->user($where);

        $this->where_val = array(
            'user_id' => $this->user_id,
            'user_name' => $this->user_name,
            'mobile' => $this->mobile,
            'rank_id' => $this->rank_id,
            'rank_name' => $this->rank_name,
            'address_id' => $this->address_id,
        );

        $this->where = userModel::get_where($this->where_val);
        $this->select = base::get_select_field($this->user_select);
    }

    public function user($where = array())
    {

        /* 初始查询条件值 */
        $this->user_id = $where['user_id'];
        $this->user_name = $where['user_name'];
        $this->mobile = $where['mobile'];
        $this->rank_id = $where['rank_id'];
        $this->rank_name = $where['rank_name'];
        $this->address_id = $where['address_id'];
        $this->user_select = $where['user_select'];
        $this->format = $where['format'];
        $this->page_size = $where['page_size'];
        $this->page = $where['page'];
        $this->sort_by = $where['sort_by'];
        $this->sort_order = $where['sort_order'];

        $this->userLangList = userLang::lang_user_request();
    }

    /**
     * 多条会员信息
     *
     * @access  public
     * @param   integer $user_id 会员ID
     * @return  array
     */
    public function get_user_list($table)
    {
        $this->table = $table['users'];
        $result = userModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order);
        $result = userModel::get_list_common_data($result, $this->page_size, $this->page, $this->userLangList, $this->format);

        return $result;
    }

    /**
     * 单条会员信息
     *
     * @access  public
     * @param   integer $user_id 会员ID
     * @return  array
     */
    public function get_user_info($table)
    {
        $this->table = $table['users'];
        $result = userModel::get_select_info($this->table, $this->select, $this->where);

        if (strlen($this->where) != 1) {
            $result = userModel::get_info_common_data_fs($result, $this->userLangList, $this->format);
        } else {
            $result = userModel::get_info_common_data_f($this->userLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入会员信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $user_select 字段信息
     * @return  array
     */
    public function get_user_insert($table)
    {
        $this->table = $table['users'];

        $userLang = userLang::lang_user_insert();
        
        $select = $this->user_select;

        $info = [];
        if ($select) {
            if (!isset($select['user_id'])) {
                if (isset($select['mobile_phone']) && !empty($select['mobile_phone'])) {
                    $where = "mobile_phone = '" . $select['mobile_phone'] . "'";
                    $info = $this->get_select_info($this->table, "*", $where);

                    if (!$info) {
                        if (isset($select['user_name']) && !empty($select['user_name'])) {
                            $where = "user_name = '" . $select['user_name'] . "'";
                            $info = $this->get_select_info($this->table, "*", $where);

                            if (!$info) {
                                return userModel::get_insert($this->table, $this->user_select, $this->format);
                            } else {
                                $error = userLang::INSERT_SAME_NAME_FAILURE;
                                $info = $select;
                            }
                        } else {
                            $error = userLang::INSERT_NULL_NAME_FAILURE;
                            $info = $select;
                        }
                    } else {
                        $error = userLang::INSERT_DATA_EXIST_FAILURE;
                        $info = $select;
                    }
                } else {
                    $error = userLang::INSERT_KEY_PARAM_FAILURE;
                    $string = 'mobile_phone';
                }
            } else {
                $info = $select;
                $error = userLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'user_id';
            }
        } else {
            $error = userLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [userLang::INSERT_CANNOT_PARAM_FAILURE, userLang::INSERT_KEY_PARAM_FAILURE])) {
            $userLang['msg_failure'][$error]['failure'] = sprintf($userLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $userLang['msg_failure'][$error]['failure'],
            'error' => $userLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新会员信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $user_select 商品字段信息
     * @return  array
     */
    public function get_user_update($table)
    {
        $this->table = $table['users'];

        $userLang = userLang::lang_user_update();

        $select = $this->user_select;

        $info = [];
        if ($select) {
            if (!isset($select['user_id'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = userLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        $mobile_phone = 0;
                        if (isset($select['mobile_phone']) && !empty($select['mobile_phone'])) {
                            $where = "mobile_phone = '" . $select['mobile_phone'] . "' AND user_id <> '" . $info['user_id'] . "'";
                            $mobile_phone = $this->get_select_info($this->table, "*", $where);
                        }

                        if ($mobile_phone) {
                            $error = userLang::UPDATE_DATA_EXIST_FAILURE;
                            $info = $select;
                        } else {
                            $user_name = '';
                            if (isset($select['user_name']) && !empty($select['user_name'])) {
                                $where = "user_name = '" . $select['user_name'] . "' AND user_id <> '" . $info['user_id'] . "'";
                                $user_name = $this->get_select_info($this->table, "*", $where);
                            }

                            if ($user_name) {
                                $error = userLang::UPDATE_SAME_NAME_FAILURE;
                            } else {
                                return userModel::get_update($this->table, $this->user_select, $this->where, $this->format, $info);
                            }
                        }
                    }
                } else {
                    $error = userLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = userLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'user_id';
            }
        } else {
            $error = userLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [userLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $userLang['msg_failure'][$error]['failure'] = sprintf($userLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $userLang['msg_failure'][$error]['failure'],
            'error' => $userLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除会员信息
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_user_delete($table)
    {
        $this->table = $table['users'];
        return userModel::get_delete($this->table, $this->where, $this->format);
    }

    /**
     * 多条会员等级信息
     *
     * @access  public
     * @param   integer $rank_id 等级ID
     * @return  array
     */
    public function get_user_rank_list($table)
    {
        $this->table = $table['rank'];
        $result = userModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order);
        $result = userModel::get_list_common_data($result, $this->page_size, $this->page, $this->userLangList, $this->format);

        return $result;
    }

    /**
     * 单条会员等级信息
     *
     * @access  public
     * @param   integer $rank_id 等级ID
     * @return  array
     */
    public function get_user_rank_info($table)
    {
        $this->table = $table['rank'];
        $result = userModel::get_select_info($this->table, $this->select, $this->where);

        if (strlen($this->where) != 1) {
            $result = userModel::get_info_common_data_fs($result, $this->userLangList, $this->format);
        } else {
            $result = userModel::get_info_common_data_f($this->userLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入会员等级信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $user_select 字段信息
     * @return  array
     */
    public function get_user_rank_insert($table)
    {
        $this->table = $table['rank'];

        $userLang = userLang::lang_user_insert();

        $select = $this->user_select;

        $info = [];
        if ($select) {
            if (!isset($select['rank_id'])) {
                if (isset($select['rank_name']) && !empty($select['rank_name'])) {
                    $where = "rank_name = '" . $select['rank_name'] . "'";
                    $info = $this->get_select_info($this->table, "*", $where);

                    if (!$info) {
                        return userModel::get_insert($this->table, $this->user_select, $this->format);
                    } else {
                        $error = userLang::INSERT_SAME_NAME_FAILURE;
                        $info = $select;
                    }
                } else {
                    $error = userLang::INSERT_NULL_NAME_FAILURE;
                    $info = $select;
                }
            } else {
                $info = $select;
                $error = userLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'rank_id';
            }
        } else {
            $error = userLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [userLang::INSERT_CANNOT_PARAM_FAILURE])) {
            $userLang['msg_failure'][$error]['failure'] = sprintf($userLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $userLang['msg_failure'][$error]['failure'],
            'error' => $userLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新会员等级信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $user_select 字段信息
     * @return  array
     */
    public function get_user_rank_update($table)
    {
        $this->table = $table['rank'];

        $userLang = userLang::lang_user_update();

        $select = $this->user_select;

        $info = [];
        if ($select) {
            if (!isset($select['rank_id'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = userLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        $rank_name = '';
                        if (isset($select['rank_name']) && !empty($select['rank_name'])) {
                            $where = "rank_name = '" . $select['rank_name'] . "' AND rank_id <> '" . $info['rank_id'] . "'";
                            $rank_name = $this->get_select_info($this->table, "*", $where);
                        }

                        if ($rank_name) {
                            $error = userLang::UPDATE_SAME_NAME_FAILURE;
                        } else {
                            return userModel::get_update($this->table, $this->user_select, $this->where, $this->format, $info);
                        }
                    }
                } else {
                    $error = userLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = userLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'rank_id';
            }
        } else {
            $error = userLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [userLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $userLang['msg_failure'][$error]['failure'] = sprintf($userLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $userLang['msg_failure'][$error]['failure'],
            'error' => $userLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除会员等级信息
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_user_rank_delete($table)
    {
        $this->table = $table['rank'];
        return userModel::get_delete($this->table, $this->where, $this->format);
    }

    /**
     * 多条会员收货地址信息
     *
     * @access  public
     * @param   integer $address_id 收货地址ID
     * @return  array
     */
    public function get_user_address_list($table)
    {
        $this->table = $table['address'];
        $result = userModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order);
        $result = userModel::get_list_common_data($result, $this->page_size, $this->page, $this->userLangList, $this->format);

        return $result;
    }

    /**
     * 单条会员收货地址信息
     *
     * @access  public
     * @param   integer $address_id 收货地址ID
     * @return  array
     */
    public function get_user_address_info($table)
    {
        $this->table = $table['address'];
        $result = userModel::get_select_info($this->table, $this->select, $this->where);

        if (strlen($this->where) != 1) {
            $result = userModel::get_info_common_data_fs($result, $this->userLangList, $this->format);
        } else {
            $result = userModel::get_info_common_data_f($this->userLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入会员收货地址信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $user_select 字段信息
     * @return  array
     */
    public function get_user_address_insert($table)
    {
        $this->table = $table['address'];
        $userLang = userLang::lang_user_insert();

        $select = $this->user_select;

        $info = [];
        if ($select) {
            if (!isset($select['address_id'])) {
                if ((isset($select['consignee']) && !empty($select['consignee'])) && (isset($select['user_id']) && !empty($select['user_id']))) {
                    return userModel::get_insert($this->table, $this->user_select, $this->format);
                } else {
                    $error = userLang::INSERT_NULL_NAME_FAILURE;
                    $info = $select;
                }
            } else {
                $info = $select;
                $error = userLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'address_id';
            }
        } else {
            $error = userLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [userLang::INSERT_CANNOT_PARAM_FAILURE])) {
            $userLang['msg_failure'][$error]['failure'] = sprintf($userLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $userLang['msg_failure'][$error]['failure'],
            'error' => $userLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新会员收货地址信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $user_select 字段信息
     * @return  array
     */
    public function get_user_address_update($table)
    {
        $this->table = $table['address'];

        $userLang = userLang::lang_user_update();

        $select = $this->user_select;

        $info = [];
        if ($select) {
            if (!isset($select['address_id'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = userLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        return userModel::get_update($this->table, $this->user_select, $this->where, $this->format, $info);
                    }
                } else {
                    $error = userLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = userLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'address_id';
            }
        } else {
            $error = userLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [userLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $userLang['msg_failure'][$error]['failure'] = sprintf($userLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $userLang['msg_failure'][$error]['failure'],
            'error' => $userLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除会员收货地址信息
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_user_address_delete($table)
    {
        $this->table = $table['address'];
        return userModel::get_delete($this->table, $this->where, $this->format);
    }
}
