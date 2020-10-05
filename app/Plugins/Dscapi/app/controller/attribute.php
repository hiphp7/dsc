<?php

namespace App\Plugins\Dscapi\app\controller;

use App\Plugins\Dscapi\app\func\common;
use App\Plugins\Dscapi\app\func\base;
use App\Plugins\Dscapi\app\model\attributeModel;
use App\Plugins\Dscapi\languages\attributeLang;

class attribute extends attributeModel
{
    private $table;                          //表名称
    private $attribute_select = array();     //查询字段数组
    private $select;                         //查询字段字符串组
    private $format = 'json';                //返回格式（json, xml, array）
    private $page_size = 10;                 //每页条数
    private $page = 1;                       //当前页
    private $where_val;                      //查询条件
    private $attributeLangList;              //语言包
    private $seller_type = 0;                //数据库商家ID查询字段类型（0 - user_id, 1 - ru_id）
    private $sort_by;                        //排序字段
    private $sort_order;                     //排序升降

    public function __construct($where = array())
    {
        $this->attribute($where);

        $this->where_val = array(
            'seller_type' => $this->seller_type,
            'seller_id' => $this->seller_id,
            'attr_id' => $this->attr_id,
            'cat_id' => $this->cat_id,
            'attr_name' => $this->attr_name,
            'attr_type' => $this->attr_type,
        );

        $this->where = attributeModel::get_where($this->where_val);
        $this->select = base::get_select_field($this->attribute_select);
    }

    public function attribute($where = array())
    {

        /* 初始查询条件值 */
        $this->seller_type = $where['seller_type'];
        $this->seller_id = $where['seller_id'];
        $this->attr_id = $where['attr_id'];
        $this->cat_id = $where['cat_id'];
        $this->attr_name = $where['attr_name'];
        $this->attr_type = $where['attr_type'];
        $this->attribute_select = $where['attribute_select'];
        $this->format = $where['format'];
        $this->page_size = $where['page_size'];
        $this->page = $where['page'];
        $this->sort_by = $where['sort_by'];
        $this->sort_order = $where['sort_order'];

        $this->attributeLangList = attributeLang::lang_attribute_request();
    }

    /**
     * 多条属性类型信息
     *
     * @access  public
     * @return  array
     */
    public function get_goodstype_list($table)
    {
        $this->table = $table['goodstype'];
        $result = attributeModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order);
        $result = attributeModel::get_list_common_data($result, $this->page_size, $this->page, $this->attributeLangList, $this->format);

        return $result;
    }

    /**
     * 单条属性类型信息
     *
     * @access  public
     * @return  array
     */
    public function get_goodstype_info($table)
    {
        $this->table = $table['goodstype'];
        $result = attributeModel::get_select_info($this->table, $this->select, $this->where);

        if (strlen($this->where) != 1) {
            $result = attributeModel::get_info_common_data_fs($result, $this->attributeLangList, $this->format);
        } else {
            $result = attributeModel::get_info_common_data_f($this->attributeLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入属性类型信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $attribute_select 字段信息
     * @return  array
     */
    public function get_goodstype_insert($table)
    {
        $this->table = $table['goodstype'];

        $attributeLang = attributeLang::lang_attribute_insert();

        $select = $this->attribute_select;

        if ($select) {
            if (!isset($select['cat_id'])) {
                if (isset($select['cat_name']) && !empty($select['cat_name'])) {
                    if (isset($select['user_id']) && !empty($select['user_id'])) {
                        $user_id = $select['user_id'];
                    } else {
                        $user_id = 0;
                    }

                    $where = " cat_name = '" . $select['cat_name'] . "' AND user_id = '$user_id'";
                    $info = $this->get_select_info($this->table, "*", $where);

                    if (!$info) {
                        return attributeModel::get_insert($this->table, $this->attribute_select, $this->format);
                    } else {
                        $error = attributeLang::INSERT_DATA_EXIST_FAILURE;
                        $info = $select;
                    }
                } else {
                    $error = attributeLang::INSERT_KEY_PARAM_FAILURE;
                    $string = 'cat_name';
                }
            } else {
                $info = $select;
                $error = attributeLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'cat_id';
            }
        } else {
            $error = attributeLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [attributeLang::INSERT_CANNOT_PARAM_FAILURE, attributeLang::INSERT_KEY_PARAM_FAILURE])) {
            $attributeLang['msg_failure'][$error]['failure'] = sprintf($attributeLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $attributeLang['msg_failure'][$error]['failure'],
            'error' => $attributeLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新属性类型信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $attribute_select 商品字段信息
     * @return  array
     */
    public function get_goodstype_update($table)
    {
        $this->table = $table['goodstype'];

        $attributeLang = attributeLang::lang_attribute_update();

        $select = $this->attribute_select;

        $info = [];
        if ($select) {
            if (!isset($select['cat_id'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = attributeLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        $cat_name = 0;
                        if (isset($select['cat_name']) && !empty($select['cat_name'])) {
                            $where = "cat_name = '" . $select['cat_name'] . "' AND cat_id <> '" . $info['cat_id'] . "' AND user_id = '" . $info['user_id'] . "'";
                            $cat_name = $this->get_select_info($this->table, "*", $where);
                        }

                        if ($cat_name) {
                            $error = attributeLang::UPDATE_DATA_EXIST_FAILURE;
                            $info = $select;
                        } else {
                            return attributeModel::get_update($this->table, $this->attribute_select, $this->where, $this->format, $info);
                        }
                    }
                } else {
                    $error = attributeLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = attributeLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'cat_id';
            }
        } else {
            $error = attributeLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [attributeLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $attributeLang['msg_failure'][$error]['failure'] = sprintf($attributeLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $attributeLang['msg_failure'][$error]['failure'],
            'error' => $attributeLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除属性类型信息
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_goodstype_delete($table)
    {
        $this->table = $table['attribute'];
        return attributeModel::get_delete($this->table, $this->where, $this->format);
    }

    /**
     * 多条属性信息
     *
     * @access  public
     * @return  array
     */
    public function get_attribute_list($table)
    {
        $this->table = $table['attribute'];
        $result = attributeModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order);
        $result = attributeModel::get_list_common_data($result, $this->page_size, $this->page, $this->attributeLangList, $this->format);

        return $result;
    }

    /**
     * 单条属性信息
     *
     * @access  public
     * @return  array
     */
    public function get_attribute_info($table)
    {
        $this->table = $table['attribute'];
        $result = attributeModel::get_select_info($this->table, $this->select, $this->where);

        if (strlen($this->where) != 1) {
            $result = attributeModel::get_info_common_data_fs($result, $this->attributeLangList, $this->format);
        } else {
            $result = attributeModel::get_info_common_data_f($this->attributeLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入属性信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $attribute_select 字段信息
     * @return  array
     */
    public function get_attribute_insert($table)
    {
        $this->table = $table['attribute'];

        $attributeLang = attributeLang::lang_attribute_insert();

        $select = $this->attribute_select;

        $info = [];
        if ($select) {
            if (!isset($select['attr_id'])) {
                if (isset($select['attr_name']) && !empty($select['attr_name'])) {
                    $where = " attr_name = '" . $select['attr_name'] . "'";
                    $info = $this->get_select_info($this->table, "*", $where);

                    if (!$info) {
                        return attributeModel::get_insert($this->table, $this->attribute_select, $this->format);
                    } else {
                        $error = attributeLang::INSERT_DATA_EXIST_FAILURE;
                        $info = $select;
                    }
                } else {
                    $error = attributeLang::INSERT_KEY_PARAM_FAILURE;
                    $string = 'attr_name';
                }
            } else {
                $info = $select;
                $error = attributeLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'attr_id';
            }
        } else {
            $error = attributeLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [attributeLang::INSERT_CANNOT_PARAM_FAILURE, attributeLang::INSERT_KEY_PARAM_FAILURE])) {
            $attributeLang['msg_failure'][$error]['failure'] = sprintf($attributeLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $attributeLang['msg_failure'][$error]['failure'],
            'error' => $attributeLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新属性信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $attribute_select 商品字段信息
     * @return  array
     */
    public function get_attribute_update($table)
    {
        $this->table = $table['attribute'];

        $attributeLang = attributeLang::lang_attribute_update();

        $select = $this->attribute_select;

        $info = [];
        if ($select) {
            if (!isset($select['attr_id'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = attributeLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        $goods_sn = 0;
                        if (isset($select['attr_name']) && !empty($select['attr_name'])) {
                            $where = "attr_name = '" . $select['attr_name'] . "' AND attr_id <> '" . $info['attr_id'] . "'";
                            $goods_sn = $this->get_select_info($this->table, "*", $where);
                        }

                        if ($goods_sn) {
                            $error = attributeLang::UPDATE_DATA_EXIST_FAILURE;
                            $info = $select;
                        } else {
                            return attributeModel::get_update($this->table, $this->attribute_select, $this->where, $this->format, $info);
                        }
                    }
                } else {
                    $error = attributeLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = attributeLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'attr_id';
            }
        } else {
            $error = attributeLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [attributeLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $attributeLang['msg_failure'][$error]['failure'] = sprintf($attributeLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $attributeLang['msg_failure'][$error]['failure'],
            'error' => $attributeLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除属性信息
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_attribute_delete($table)
    {
        $this->table = $table['attribute'];
        return attributeModel::get_delete($this->table, $this->where, $this->format);
    }
}
