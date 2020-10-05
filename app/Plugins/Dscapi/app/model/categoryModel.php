<?php

namespace App\Plugins\Dscapi\app\model;

use App\Plugins\Dscapi\app\func\common;
use App\Plugins\Dscapi\app\func\base;
use App\Plugins\Dscapi\languages\categoryLang;

abstract class categoryModel extends common
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
        $this->categoryModel();
    }

    /**
     * 构造函数
     *
     * @access  public
     * @return  bool
     */
    public function categoryModel($table = '')
    {
        $this->alias_config = array(
            'category' => 'c',                         //分类表
            'merchants_category' => 'mc',              //店铺分类表
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

        /* 商家ID */
        $where .= base::get_where($val['seller_id'], $alias . 'user_id');

        /* 分类 */
        $where .= base::get_where($val['cat_id'], $alias . 'cat_id');

        /* 父级ID */
        $where .= base::get_where($val['parent_id'], $alias . 'parent_id');

        /* 分类名称 */
        $where .= base::get_where($val['cat_name'], $alias . 'cat_name');

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
        $categoryLang = categoryLang::lang_category_insert();

        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table), $select, "INSERT");
        $id = $GLOBALS['db']->insert_id();

        $info = $select;

        if ($id) {
            $info['cat_id'] = $id;
        }

        $common_data = array(
            'result' => "success",
            'msg' => $categoryLang['msg_success']['success'],
            'error' => $categoryLang['msg_success']['error'],
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
        $categoryLang = categoryLang::lang_category_update();

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
            'msg' => $categoryLang['msg_success']['success'],
            'error' => $categoryLang['msg_success']['error'],
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
        $categoryLang = categoryLang::lang_category_delete();

        $return = false;
        if (strlen($where) != 1) {
            $sql = "DELETE FROM " . $GLOBALS['dsc']->table($table) . " WHERE " . $where;
            $GLOBALS['db']->query($sql);

            $return = true;
        } else {
            $error = categoryLang::DEL_NULL_PARAM_FAILURE;
        }

        $common_data = array(
            'result' => $return ? "success" : "failure",
            'msg' => $return ? $categoryLang['msg_success']['success'] : $categoryLang['msg_failure'][$error]['failure'],
            'error' => $return ? $categoryLang['msg_success']['error'] : $categoryLang['msg_failure'][$error]['error'],
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
    public function get_list_common_data($result, $page_size, $page, $categoryLang, $format)
    {
        $common_data = array(
            'page_size' => $page_size,
            'page' => $page,
            'result' => empty($result) ? "failure" : 'success',
            'msg' => empty($result) ? $categoryLang['msg_failure']['failure'] : $categoryLang['msg_success']['success'],
            'error' => empty($result) ? $categoryLang['msg_failure']['error'] : $categoryLang['msg_success']['error'],
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
    public function get_info_common_data_fs($result, $categoryLang, $format)
    {
        $common_data = array(
            'result' => empty($result) ? "failure" : 'success',
            'msg' => empty($result) ? $categoryLang['msg_failure']['failure'] : $categoryLang['msg_success']['success'],
            'error' => empty($result) ? $categoryLang['msg_failure']['error'] : $categoryLang['msg_success']['error'],
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
    public function get_info_common_data_f($categoryLang, $format)
    {
        $result = array();

        $common_data = array(
            'result' => 'failure',
            'msg' => $categoryLang['where_failure']['failure'],
            'error' => $categoryLang['where_failure']['error'],
            'format' => $format
        );

        common::common($common_data);
        $result = common::data_back($result);

        return $result;
    }
}
