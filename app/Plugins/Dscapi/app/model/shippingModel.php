<?php

namespace App\Plugins\Dscapi\app\model;

use App\Plugins\Dscapi\app\func\common;
use App\Plugins\Dscapi\app\func\base;
use App\Plugins\Dscapi\languages\shippingLang;

abstract class shippingModel extends common
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
        $this->shippingModel();
    }

    /**
     * 构造函数
     *
     * @access  public
     * @return  bool
     */
    public function shippingModel($table = '')
    {
        $this->alias_config = array(
            'shipping' => 'r',                         //快递方式表
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

        /* 快递方式ID */
        $where .= base::get_where($val['shipping_id'], $alias . 'shipping_id');
        
        /* 快递编码 */
        $where .= base::get_where($val['shipping_code'], $alias . 'shipping_code');
        
        /* 快递方式名称 */
        $where .= base::get_where($val['shipping_name'], $alias . 'shipping_name');
    
        
        return $where;
    }
    
    /**
     * 查询获取列表数据
     *
     * @access  public
     * @param   string $table    表名称
     * @param   string $select    查询字段
     * @param   string where    查询条件
     * @param   string $page_size    页码
     * @param   string $page    当前页
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
     * @param   string $table    表名称
     * @param   string $select    查询字段
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
        $shippingLang = shippingLang::lang_shipping_insert();

        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table), $select, "INSERT");
        $id = $GLOBALS['db']->insert_id();
        
        $info = $select;
        
        if ($id) {
            $info['shipping_id'] = $id;
        }

        $common_data = array(
            'result' => "success",
            'msg' => $shippingLang['msg_success']['success'],
            'error' => $shippingLang['msg_success']['error'],
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
        $shippingLang = shippingLang::lang_shipping_update();
        
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
            'msg' => $shippingLang['msg_success']['success'],
            'error' => $shippingLang['msg_success']['error'],
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
        $shippingLang = shippingLang::lang_shipping_delete();

        $return = false;
        if (strlen($where) != 1) {
            $sql = "DELETE FROM " . $GLOBALS['dsc']->table($table) . " WHERE " . $where;
            $GLOBALS['db']->query($sql);
            
            $return = true;
        } else {
            $error = shippingLang::DEL_NULL_PARAM_FAILURE;
        }
        
        $common_data = array(
            'result' => $return ? "success" : "failure",
            'msg' => $return ? $shippingLang['msg_success']['success'] : $shippingLang['msg_failure'][$error]['failure'],
            'error' => $return ? $shippingLang['msg_success']['error'] : $shippingLang['msg_failure'][$error]['error'],
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
    public function get_list_common_data($result, $page_size, $page, $shippingLang, $format)
    {
        $common_data = array(
            'page_size' => $page_size,
            'page' => $page,
            'result' => empty($result) ? "failure" : 'success',
            'msg' => empty($result) ? $shippingLang['msg_failure']['failure'] : $shippingLang['msg_success']['success'],
            'error' => empty($result) ? $shippingLang['msg_failure']['error'] : $shippingLang['msg_success']['error'],
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
    public function get_info_common_data_fs($result, $shippingLang, $format)
    {
        $common_data = array(
            'result' => empty($result) ? "failure" : 'success',
            'msg' => empty($result) ? $shippingLang['msg_failure']['failure'] : $shippingLang['msg_success']['success'],
            'error' => empty($result) ? $shippingLang['msg_failure']['error'] : $shippingLang['msg_success']['error'],
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
    public function get_info_common_data_f($shippingLang, $format)
    {
        $result = array();
        
        $common_data = array(
            'result' => 'failure',
            'msg' => $shippingLang['where_failure']['failure'],
            'error' => $shippingLang['where_failure']['error'],
            'format' => $format
        );
        
        common::common($common_data);
        $result = common::data_back($result);
        
        return $result;
    }
}
