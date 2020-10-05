<?php

namespace App\Plugins\Dscapi\app\controller;

use App\Plugins\Dscapi\app\func\common;
use App\Plugins\Dscapi\app\func\base;
use App\Plugins\Dscapi\app\model\regionModel;
use App\Plugins\Dscapi\languages\regionLang;

class region extends regionModel
{
    private $table;                          //表名称
    private $region_select = array();          //查询字段数组
    private $select;                         //查询字段字符串组
    private $region_id = 0;                  //地区ID
    private $parent_id = 0;                  //父级ID
    private $region_name = '';               //地区名称ID
    private $region_type = 0;                //地区层级val
    private $format = 'json';           //返回格式（json, xml, array）
    private $page_size = 10;                 //每页条数
    private $page = 1;                       //当前页
    private $where_val;                      //查询条件
    private $regionLangList;                 //语言包
    private $sort_by;                        //排序字段
    private $sort_order;                     //排序升降

    public function __construct($where = array())
    {
        $this->region($where);

        $this->where_val = array(
            'region_id' => $this->region_id,
            'parent_id' => $this->parent_id,
            'region_type' => $this->region_type,
            'region_name' => $this->region_name,
        );

        $this->where = regionModel::get_where($this->where_val);
        $this->select = base::get_select_field($this->region_select);
    }

    public function region($where = array())
    {

        /* 初始查询条件值 */
        $this->region_id = $where['region_id'];
        $this->parent_id = $where['parent_id'];
        $this->region_type = $where['region_type'];
        $this->region_name = $where['region_name'];
        $this->region_select = $where['region_select'];
        $this->format = $where['format'];
        $this->page_size = $where['page_size'];
        $this->page = $where['page'];
        $this->sort_by = $where['sort_by'];
        $this->sort_order = $where['sort_order'];

        $this->regionLangList = regionLang::lang_region_request();
    }

    /**
     * 多条地区信息
     *
     * @access  public
     * @return  array
     */
    public function get_region_list($table)
    {
        $this->table = $table['region'];
        $result = regionModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order);
        $result = regionModel::get_list_common_data($result, $this->page_size, $this->page, $this->regionLangList, $this->format);

        return $result;
    }

    /**
     * 单条地区信息
     *
     * @access  public
     * @return  array
     */
    public function get_region_info($table)
    {
        $this->table = $table['region'];
        $result = regionModel::get_select_info($this->table, $this->select, $this->where);

        if (strlen($this->where) != 1) {
            $result = regionModel::get_info_common_data_fs($result, $this->regionLangList, $this->format);
        } else {
            $result = regionModel::get_info_common_data_f($this->regionLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入地区信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $region_select 字段信息
     * @return  array
     */
    public function get_region_insert($table)
    {
        $this->table = $table['region'];

        $regionLang = regionLang::lang_region_insert();

        $select = $this->region_select;

        $info = [];
        if ($select) {
            if (!isset($select['region_id'])) {
                if (isset($select['region_name']) && !empty($select['region_name'])) {
                    if (isset($select['region_name']) && !empty($select['region_name'])) {
                        $where = "region_name = '" . $select['region_name'] . "'";
                        $info = $this->get_select_info($this->table, "*", $where);

                        if (!$info) {
                            return regionModel::get_insert($this->table, $this->region_select, $this->format);
                        } else {
                            $error = regionLang::INSERT_SAME_NAME_FAILURE;
                            $info = $select;
                        }
                    } else {
                        $error = regionLang::INSERT_NULL_NAME_FAILURE;
                        $info = $select;
                    }
                } else {
                    $error = regionLang::INSERT_KEY_PARAM_FAILURE;
                    $string = 'region_name';
                }
            } else {
                $info = $select;
                $error = regionLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'region_id';
            }
        } else {
            $error = regionLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [regionLang::INSERT_CANNOT_PARAM_FAILURE, regionLang::INSERT_KEY_PARAM_FAILURE])) {
            $regionLang['msg_failure'][$error]['failure'] = sprintf($regionLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $regionLang['msg_failure'][$error]['failure'],
            'error' => $regionLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新地区信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $region_select 商品字段信息
     * @return  array
     */
    public function get_region_update($table)
    {
        $this->table = $table['region'];

        $regionLang = regionLang::lang_region_update();

        $select = $this->region_select;

        $info = [];
        if ($select) {
            if (!isset($select['region_id'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = regionLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        $region_name = 0;
                        if (isset($select['region_name']) && !empty($select['region_name'])) {
                            $where = "region_name = '" . $select['region_name'] . "' AND region_id <> '" . $info['region_id'] . "'";
                            $region_name = $this->get_select_info($this->table, "*", $where);
                        }

                        if ($region_name) {
                            $error = regionLang::UPDATE_DATA_EXIST_FAILURE;
                            $info = $select;
                        } else {
                            return regionModel::get_update($this->table, $this->region_select, $this->where, $this->format, $info);
                        }
                    }
                } else {
                    $error = regionLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = regionLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'region_id';
            }
        } else {
            $error = regionLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [regionLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $regionLang['msg_failure'][$error]['failure'] = sprintf($regionLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $regionLang['msg_failure'][$error]['failure'],
            'error' => $regionLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除地区信息
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_region_delete($table)
    {
        $this->table = $table['region'];
        return regionModel::get_delete($this->table, $this->where, $this->format);
    }
}
