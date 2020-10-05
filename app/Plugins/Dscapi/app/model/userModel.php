<?php

namespace App\Plugins\Dscapi\app\model;

use App\Plugins\Dscapi\app\func\common;
use App\Plugins\Dscapi\app\func\base;
use App\Plugins\Dscapi\languages\userLang;

abstract class userModel extends common
{
    private $alias_config;

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function __construct()
    {
        $this->userModel();
    }

    /**
     * 构造函数
     *
     * @access  public
     * @return  bool
     */
    public function userModel($table = '')
    {
        $this->alias_config = array(
            'users' => 'u',                             //会员表
            'user_rank' => 'ur',                        //会员等级表
            'user_address' => 'ua',                     //会员收货地址表
        );

        if ($table) {
            return $this->alias_config[$table];
        } else {
            return $this->alias_config;
        }
    }

    /**
     * 查询条件
     *
     * @access  public
     * @param   string where    查询条件
     * @return  string
     */
    public function get_where($val = array(), $alias = '')
    {
        $where = 1;

        /* 会员ID */
        $where .= base::get_where($val['user_id'], $alias . 'user_id');

        /* 会员名称 */
        $where .= base::get_where($val['user_name'], $alias . 'user_name');

        /* 会员手机号 */
        $where .= base::get_where($val['mobile'], $alias . 'mobile_phone');

        /* 等级ID */
        $where .= base::get_where($val['rank_id'], $alias . 'rank_id');

        /* 等级名称 */
        $where .= base::get_where($val['rank_name'], $alias . 'rank_name');

        /* 收货地址ID */
        $where .= base::get_where($val['address_id'], $alias . 'address_id');

        return $where;
    }

    /**
     * 查询获取列表数据
     *
     * @access  public
     * @param   string $table 表名称
     * @param   string $select 查询字段
     * @param   string where    查询条件
     * @param   string $page_size 页码
     * @param   string $page 当前页
     * @return  string
     */
    public function get_select_list($table, $select, $where, $page_size, $page, $sort_by, $sort_order)
    {
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table($table) . " WHERE " . $where;
        $result['record_count'] = $GLOBALS['db']->getOne($sql);

        if ($sort_by) {
            $where .= " ORDER BY $sort_by $sort_order ";
        }

        $where .= " LIMIT " . ($page - 1) * $page_size . ",$page_size";

        $sql = "SELECT " . $select . " FROM " . $GLOBALS['dsc']->table($table) . " WHERE " . $where;
        $result['list'] = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    /**
     * 查询获取单条数据
     *
     * @access  public
     * @param   string $table 表名称
     * @param   string $select 查询字段
     * @param   string where    查询条件
     * @return  string
     */
    public function get_select_info($table, $select, $where)
    {
        $sql = "SELECT " . $select . " FROM " . $GLOBALS['dsc']->table($table) . " WHERE " . $where . " LIMIT 1";
        $result = $GLOBALS['db']->getRow($sql);
        return $result;
    }

    /**
     * 插入数据
     *
     * @access  public
     * @param   string where    查询条件
     * @return  string
     */
    public function get_insert($table, $select, $format)
    {
        $config = array_flip($this->userModel());

        $userLang = userLang::lang_user_insert();

        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table), $select, "INSERT");
        $id = $GLOBALS['db']->insert_id();

        $info = $select;

        if ($id) {
            if ($table == $config['u']) {
                $info['user_id'] = $id;
            } elseif ($table == $config['ur']) {
                $info['rank_id'] = $id;
            } elseif ($table == $config['ua']) {
                $info['address_id'] = $id;
            }
        }

        $common_data = array(
            'result' => "success",
            'msg' => $userLang['msg_success']['success'],
            'error' => $userLang['msg_success']['error'],
            'format' => $format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新数据
     *
     * @access  public
     * @param   string where    查询条件
     * @return  string
     */
    public function get_update($table, $select, $where, $format, $info = [])
    {
        $userLang = userLang::lang_user_update();

        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table), $select, "UPDATE", $where);

        if ($info) {
            foreach ($info as $key => $row) {
                if (isset($select[$key])) {
                    $info[$key] = $select[$key];
                }
            }
        } else {
            $info = $select;
        }

        $common_data = array(
            'result' => "success",
            'msg' => $userLang['msg_success']['success'],
            'error' => $userLang['msg_success']['error'],
            'format' => $format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 数据删除
     *
     * @access  public
     * @param   string where    查询条件
     * @return  string
     */
    public function get_delete($table, $where, $format)
    {
        $userLang = userLang::lang_user_delete();

        $return = false;
        if (strlen($where) != 1) {
            $sql = "DELETE FROM " . $GLOBALS['dsc']->table($table) . " WHERE " . $where;
            $GLOBALS['db']->query($sql);

            $return = true;
        } else {
            $error = userLang::DEL_NULL_PARAM_FAILURE;
        }

        $common_data = array(
            'result' => $return ? "success" : "failure",
            'msg' => $return ? $userLang['msg_success']['success'] : $userLang['msg_failure'][$error]['failure'],
            'error' => $return ? $userLang['msg_success']['error'] : $userLang['msg_failure'][$error]['error'],
            'format' => $format
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 格式化返回值
     *
     * @access  public
     * @return  string
     */
    public function get_list_common_data($result, $page_size, $page, $userLang, $format)
    {
        $common_data = array(
            'page_size' => $page_size,
            'page' => $page,
            'result' => empty($result) ? "failure" : 'success',
            'msg' => empty($result) ? $userLang['msg_failure']['failure'] : $userLang['msg_success']['success'],
            'error' => empty($result) ? $userLang['msg_failure']['error'] : $userLang['msg_success']['error'],
            'format' => $format
        );

        common::common($common_data);
        $result = common::data_back($result, 1);

        return $result;
    }

    /**
     * 格式化返回值
     *
     * @access  public
     * @return  string
     */
    public function get_info_common_data_fs($result, $userLang, $format)
    {
        $common_data = array(
            'result' => empty($result) ? "failure" : 'success',
            'msg' => empty($result) ? $userLang['msg_failure']['failure'] : $userLang['msg_success']['success'],
            'error' => empty($result) ? $userLang['msg_failure']['error'] : $userLang['msg_success']['error'],
            'format' => $format
        );

        common::common($common_data);
        $result = common::data_back($result);

        return $result;
    }

    /**
     * 格式化返回值
     *
     * @access  public
     * @return  string
     */
    public function get_info_common_data_f($userLang, $format)
    {
        $result = array();

        $common_data = array(
            'result' => 'failure',
            'msg' => $userLang['where_failure']['failure'],
            'error' => $userLang['where_failure']['error'],
            'format' => $format
        );

        common::common($common_data);
        $result = common::data_back($result);

        return $result;
    }
}
