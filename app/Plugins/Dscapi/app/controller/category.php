<?php

namespace App\Plugins\Dscapi\app\controller;

use App\Plugins\Dscapi\app\func\common;
use App\Plugins\Dscapi\app\func\base;
use App\Plugins\Dscapi\app\model\categoryModel;
use App\Plugins\Dscapi\languages\categoryLang;

class category extends categoryModel
{
    private $table;                          //表名称
    private $alias;                          //表别名
    private $category_select = array();      //查询字段数组
    private $select;                         //查询字段字符串组
    private $seller_id = 0;                  //地区ID
    private $cat_id = 0;                     //地区ID
    private $cat_name = '';                  //父级ID
    private $parent_id = 0;                  //地区名称ID
    private $region_type = 0;                //地区层级val
    private $format = 'json';                //返回格式（json, xml, array）
    private $page_size = 10;                 //每页条数
    private $page = 1;                       //当前页
    private $where_val;                      //查询条件
    private $categoryLangList;                 //语言包
    private $sort_by;                        //排序字段
    private $sort_order;                     //排序升降

    public function __construct($where = array())
    {
        $this->category($where);

        $this->where_val = array(
            'seller_id' => $this->seller_id,
            'cat_id' => $this->cat_id,
            'parent_id' => $this->parent_id,
            'cat_name' => $this->cat_name,
        );

        $this->where = categoryModel::get_where($this->where_val);
        $this->select = base::get_select_field($this->category_select);
    }

    public function category($where = array())
    {

        /* 初始查询条件值 */
        $this->seller_id = $where['seller_id'];
        $this->cat_id = $where['cat_id'];
        $this->parent_id = $where['parent_id'];
        $this->cat_name = $where['cat_name'];
        $this->category_select = $where['category_select'];
        $this->format = $where['format'];
        $this->page_size = $where['page_size'];
        $this->page = $where['page'];
        $this->sort_by = $where['sort_by'];
        $this->sort_order = $where['sort_order'];

        $this->categoryLangList = categoryLang::lang_category_request();
    }

    /**
     * 多条分类信息
     *
     * @access  public
     * @return  array
     */
    public function get_category_list($table)
    {
        $this->table = $table['category'];
        $result = categoryModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order);
        $result = categoryModel::get_list_common_data($result, $this->page_size, $this->page, $this->categoryLangList, $this->format);

        return $result;
    }

    /**
     * 单条分类信息
     *
     * @access  public
     * @return  array
     */
    public function get_category_info($table)
    {
        $this->table = $table['category'];
        $result = categoryModel::get_select_info($this->table, $this->select, $this->where);

        if (strlen($this->where) != 1) {
            $result = categoryModel::get_info_common_data_fs($result, $this->categoryLangList, $this->format);
        } else {
            $result = categoryModel::get_info_common_data_f($this->categoryLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入分类信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $category_select 字段信息
     * @return  array
     */
    public function get_category_insert($table)
    {
        $this->table = $table['category'];

        $categoryLang = categoryLang::lang_category_insert();

        $select = $this->category_select;

        $info = [];
        if ($select) {
            if (!isset($select['cat_id'])) {
                if (isset($select['cat_name']) && !empty($select['cat_name'])) {
                    if (isset($select['parent_id']) && !empty($select['parent_id'])) {
                        $parent_id = $select['parent_id'];
                    } else {
                        $parent_id = 0;
                    }

                    $where = "cat_name = '" . $select['cat_name'] . "' AND parent_id = '$parent_id'";
                    $info = $this->get_select_info($this->table, "*", $where);

                    if (!$info) {
                        return categoryModel::get_insert($this->table, $this->category_select, $this->format);
                    } else {
                        $error = categoryLang::INSERT_SAME_NAME_FAILURE;
                        $info = $select;
                    }
                } else {
                    $error = categoryLang::INSERT_NULL_NAME_FAILURE;
                    $info = $select;
                }
            } else {
                $info = $select;
                $error = categoryLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'cat_id';
            }
        } else {
            $error = categoryLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [categoryLang::INSERT_CANNOT_PARAM_FAILURE])) {
            $categoryLang['msg_failure'][$error]['failure'] = sprintf($categoryLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $categoryLang['msg_failure'][$error]['failure'],
            'error' => $categoryLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新分类信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $category_select 商品字段信息
     * @return  array
     */
    public function get_category_update($table)
    {
        $this->table = $table['category'];

        $categoryLang = categoryLang::lang_category_update();

        $select = $this->category_select;

        $info = [];
        if ($select) {
            if (!isset($select['cat_id'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = categoryLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        $cat_name = '';
                        if (isset($select['cat_name']) && !empty($select['cat_name'])) {
                            $where = "1";
                            if (isset($select['parent_id']) && !empty($select['parent_id'])) {
                                $where .= " AND parent_id = '" . $select['parent_id'] . "'";
                            }

                            $where .= " AND cat_name = '" . $select['cat_name'] . "' AND cat_id <> '" . $info['cat_id'] . "'";
                            $cat_name = $this->get_select_info($this->table, "*", $where);
                        }

                        if ($cat_name) {
                            $error = categoryLang::UPDATE_SAME_NAME_FAILURE;
                        } else {
                            return categoryModel::get_update($this->table, $this->category_select, $this->where, $this->format, $info);
                        }
                    }
                } else {
                    $error = categoryLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = categoryLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'cat_id';
            }
        } else {
            $error = categoryLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [categoryLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $categoryLang['msg_failure'][$error]['failure'] = sprintf($categoryLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $categoryLang['msg_failure'][$error]['failure'],
            'error' => $categoryLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除分类信息
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_category_delete($table)
    {
        $this->table = $table['category'];
        return categoryModel::get_delete($this->table, $this->where, $this->format);
    }

    /**
     * 多条商家分类信息
     *
     * @access  public
     * @return  array
     */
    public function get_category_seller_list($table)
    {
        $this->table = $table['seller'];
        $result = categoryModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order);
        $result = categoryModel::get_list_common_data($result, $this->page_size, $this->page, $this->categoryLangList, $this->format);

        return $result;
    }

    /**
     * 单条商家分类信息
     *
     * @access  public
     * @return  array
     */
    public function get_category_seller_info($table)
    {
        $this->table = $table['seller'];
        $result = categoryModel::get_select_info($this->table, $this->select, $this->where);

        if (strlen($this->where) != 1) {
            $result = categoryModel::get_info_common_data_fs($result, $this->categoryLangList, $this->format);
        } else {
            $result = categoryModel::get_info_common_data_f($this->categoryLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入商家分类信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $category_select 字段信息
     * @return  array
     */
    public function get_category_seller_insert($table)
    {
        $this->table = $table['seller'];

        $categoryLang = categoryLang::lang_category_insert();

        $select = $this->category_select;

        $info = [];
        if ($select) {
            if (!isset($select['cat_id'])) {
                if (isset($select['cat_name']) && !empty($select['cat_name'])) {
                    if (isset($select['parent_id']) && !empty($select['parent_id'])) {
                        $parent_id = $select['parent_id'];
                    } else {
                        $parent_id = 0;
                    }

                    $where = "cat_name = '" . $select['cat_name'] . "' AND parent_id = '$parent_id'";
                    $info = $this->get_select_info($this->table, "*", $where);

                    if (!$info) {
                        return categoryModel::get_insert($this->table, $this->category_select, $this->format);
                    } else {
                        $error = categoryLang::INSERT_SAME_NAME_FAILURE;
                        $info = $select;
                    }
                } else {
                    $error = categoryLang::INSERT_NULL_NAME_FAILURE;
                    $info = $select;
                }
            } else {
                $info = $select;
                $error = categoryLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'cat_id';
            }
        } else {
            $error = categoryLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [categoryLang::INSERT_CANNOT_PARAM_FAILURE])) {
            $categoryLang['msg_failure'][$error]['failure'] = sprintf($categoryLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $categoryLang['msg_failure'][$error]['failure'],
            'error' => $categoryLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新商家分类信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $category_select 商品字段信息
     * @return  array
     */
    public function get_category_seller_update($table)
    {
        $this->table = $table['seller'];

        $categoryLang = categoryLang::lang_category_update();

        $select = $this->category_select;

        $info = [];
        if ($select) {
            if (!isset($select['cat_id'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = categoryLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        $cat_name = '';
                        if (isset($select['cat_name']) && !empty($select['cat_name'])) {
                            $where = "1";
                            if (isset($select['parent_id']) && !empty($select['parent_id'])) {
                                $where .= " AND parent_id = '" . $select['parent_id'] . "'";
                            }

                            $where .= " AND cat_name = '" . $select['cat_name'] . "' AND cat_id <> '" . $info['cat_id'] . "' AND user_id = '" . $info['user_id'] . "'";
                            $cat_name = $this->get_select_info($this->table, "*", $where);
                        }

                        if ($cat_name) {
                            $error = categoryLang::UPDATE_SAME_NAME_FAILURE;
                        } else {
                            return categoryModel::get_update($this->table, $this->category_select, $this->where, $this->format, $info);
                        }
                    }
                } else {
                    $error = categoryLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = categoryLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'cat_id';
            }
        } else {
            $error = categoryLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [categoryLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $categoryLang['msg_failure'][$error]['failure'] = sprintf($categoryLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $categoryLang['msg_failure'][$error]['failure'],
            'error' => $categoryLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除商家分类信息
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_category_seller_delete($table)
    {
        $this->table = $table['seller'];
        return categoryModel::get_delete($this->table, $this->where, $this->format);
    }
}
