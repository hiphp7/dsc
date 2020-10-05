<?php

namespace App\Plugins\Dscapi\app\controller;

use App\Plugins\Dscapi\app\func\common;
use App\Plugins\Dscapi\app\func\base;
use App\Plugins\Dscapi\app\model\goodsModel;
use App\Plugins\Dscapi\languages\goodsLang;

class goods extends goodsModel
{
    private $method = '';                    //接口地址

    private $table;                          //表名称
    private $alias = '';                     //表别名
    private $goods_select = array();         //查询字段数组
    private $select;                         //查询字段字符串组
    private $seller_id = 0;                  //商家ID
    private $brand_id = 0;                   //品牌ID
    private $cat_id = 0;                     //商品分类ID
    private $user_cat = 0;                   //商品商品分类ID
    private $goods_id = 0;                   //商品ID
    private $goods_sn = '';                  //商品货号
    private $is_delete = 0;                  //回收站商品 0：否 1：是
    private $bar_code = '';                  //商品条形码
    private $w_id = 0;                       //商品仓库ID
    private $a_id = 0;                       //商品地区ID
    private $region_id = 0;                  //仓库地区ID
    private $province_name = '';             //仓库地区省级名称
    private $city_name = '';                 //仓库地区市级名称
    private $region_sn = '';                 //商品仓库\地区货号
    private $img_id = 0;                     //商品相册ID
    private $attr_id = 0;                    //属性类型
    private $goods_attr_id = 0;              //商品属性ID
    private $tid = '';                       //商品运费模板ID
    private $seller_type = 0;                //数据库商家ID查询字段类型（0 - user_id, 1 - ru_id）
    private $format = 'json';                //返回格式（json, xml, array）
    private $page_size = 10;                 //每页条数
    private $page = 1;                       //当前页
    private $where_val;                      //查询条件
    private $goodsLangList;                  //语言包
    private $sort_by;                        //排序字段
    private $sort_order;                     //排序升降

    public function __construct($where = array())
    {
        $this->goods($where);

        $this->where_val = array(
            'seller_id' => $this->seller_id,
            'brand_id' => $this->brand_id,
            'cat_id' => $this->cat_id,
            'user_cat' => $this->user_cat,
            'goods_id' => $this->goods_id,
            'goods_sn' => $this->goods_sn,
            'is_delete' => $this->is_delete,
            'bar_code' => $this->bar_code,
            'w_id' => $this->w_id,
            'a_id' => $this->a_id,
            'region_id' => $this->region_id,
            'province_name' => $this->province_name,
            'city_name' => $this->city_name,
            'region_sn' => $this->region_sn,
            'img_id' => $this->img_id,
            'attr_id' => $this->attr_id,
            'goods_attr_id' => $this->goods_attr_id,
            'tid' => $this->tid,
            'seller_type' => $this->seller_type
        );

        if ($this->method == 'dsc.goods.area.info.get') {
            $this->alias = 'wag.';
        }

        $this->where = goodsModel::get_where($this->where_val, $this->alias);
        if ($this->method != 'dsc.goods.batchinsert.post') {
            $this->select = base::get_select_field($this->goods_select);
        }
    }

    public function goods($where = array())
    {

        /* 初始查询条件值 */
        $this->seller_type = $where['seller_type'];
        $this->seller_id = $where['seller_id'];
        $this->brand_id = $where['brand_id'];
        $this->cat_id = $where['cat_id'];
        $this->user_cat = $where['user_cat'];
        $this->goods_id = $where['goods_id'];
        $this->goods_sn = $where['goods_sn'];
        $this->is_delete = $where['is_delete'];
        $this->bar_code = $where['bar_code'];
        $this->w_id = $where['w_id'];
        $this->a_id = $where['a_id'];
        $this->region_id = $where['region_id'];
        $this->province_name = $where['province_name'];
        $this->city_name = $where['city_name'];
        $this->region_sn = $where['region_sn'];
        $this->img_id = $where['img_id'];
        $this->attr_id = $where['attr_id'];
        $this->goods_attr_id = $where['goods_attr_id'];
        $this->tid = $where['tid'];
        $this->goods_select = $where['goods_select'];
        $this->method = $where['method'];
        $this->format = $where['format'];
        $this->page_size = $where['page_size'];
        $this->page = $where['page'];
        $this->sort_by = $where['sort_by'];
        $this->sort_order = $where['sort_order'];

        $this->goodsLangList = goodsLang::lang_goods_request();
    }

    /**
     * 多条商品信息
     *
     * @access  public
     * @param   integer $goods_id 商品ID
     * @return  array
     */
    public function get_goods_list($table)
    {
        $this->table = $table['goods'];
        $result = goodsModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order);
        $result = goodsModel::get_list_common_data($result, $this->page_size, $this->page, $this->goodsLangList, $this->format);

        return $result;
    }

    /**
     * 单条商品信息
     *
     * @access  public
     * @param   integer $goods_id 商品ID
     * @return  array
     */
    public function get_goods_info($table)
    {
        $this->table = $table['goods'];
        $result = goodsModel::get_select_info($this->table, $this->select, $this->where);

        if (strlen($this->where) != 1) {
            $result = goodsModel::get_info_common_data_fs($result, $this->goodsLangList, $this->format);
        } else {
            $result = goodsModel::get_info_common_data_f($this->goodsLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入商品信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $goods_select 商品字段信息
     * @return  array
     */
    public function get_goods_insert($table)
    {
        $this->table = $table['goods'];

        $goodsLang = goodsLang::lang_goods_insert();

        $select = $this->goods_select;

        $info = [];
        if ($select) {
            if (!isset($select['goods_id'])) {
                if (isset($select['goods_sn']) && !empty($select['goods_sn'])) {
                    if (isset($select['user_id']) && !empty($select['user_id'])) {
                        $user_id = $select['user_id'];
                    } else {
                        $user_id = 0;
                    }

                    $where = " goods_sn = '" . $select['goods_sn'] . "' AND user_id = '$user_id'";
                    $info = $this->get_select_info($this->table, "*", $where);

                    if (!$info) {
                        if (isset($select['goods_name']) && !empty($select['goods_name'])) {
                            $where = "goods_name = '" . $select['goods_name'] . "'";
                            $info = $this->get_select_info($this->table, "*", $where);

                            if (!$info) {
                                return goodsModel::get_insert($this->table, $this->goods_select, $this->format);
                            } else {
                                $error = goodsLang::INSERT_SAME_NAME_FAILURE;
                                $info = $select;
                            }
                        } else {
                            $error = goodsLang::INSERT_NULL_NAME_FAILURE;
                            $info = $select;
                        }
                    } else {
                        $error = goodsLang::INSERT_DATA_EXIST_FAILURE;
                        $info = $select;
                    }
                } else {
                    $error = goodsLang::INSERT_KEY_PARAM_FAILURE;
                    $string = 'goods_sn';
                }
            } else {
                $info = $select;
                $error = goodsLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'goods_id';
            }
        } else {
            $error = goodsLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [goodsLang::INSERT_CANNOT_PARAM_FAILURE, goodsLang::INSERT_KEY_PARAM_FAILURE])) {
            $goodsLang['msg_failure'][$error]['failure'] = sprintf($goodsLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $goodsLang['msg_failure'][$error]['failure'],
            'error' => $goodsLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新商品信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $goods_select 商品字段信息
     * @return  array
     */
    public function get_goods_update($table)
    {
        $this->table = $table['goods'];
        $goodsLang = goodsLang::lang_goods_update();

        $select = $this->goods_select;

        $info = [];
        if ($select) {
            if (!isset($select['goods_id'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = goodsLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        $goods_sn = 0;
                        if (isset($select['goods_sn']) && !empty($select['goods_sn'])) {
                            $where = "goods_sn = '" . $select['goods_sn'] . "' AND goods_id <> '" . $info['goods_id'] . "' AND user_id = '" . $info['user_id'] . "'";
                            $goods_sn = $this->get_select_info($this->table, "*", $where);
                        }

                        if ($goods_sn) {
                            $error = goodsLang::UPDATE_DATA_EXIST_FAILURE;
                            $info = $select;
                        } else {
                            $goods_name = '';
                            if (isset($select['goods_name']) && !empty($select['goods_name'])) {
                                $where = "goods_name = '" . $select['goods_name'] . "' AND goods_id <> '" . $info['goods_id'] . "'";
                                $goods_name = $this->get_select_info($this->table, "*", $where);
                            }

                            if ($goods_name) {
                                $error = goodsLang::UPDATE_SAME_NAME_FAILURE;
                            } else {
                                return goodsModel::get_update($this->table, $this->goods_select, $this->where, $this->format, $info);
                            }
                        }
                    }
                } else {
                    $error = goodsLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = goodsLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'goods_id';
            }
        } else {
            $error = goodsLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [goodsLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $goodsLang['msg_failure'][$error]['failure'] = sprintf($goodsLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $goodsLang['msg_failure'][$error]['failure'],
            'error' => $goodsLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除商品信息
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_goods_delete($table)
    {
        $this->table = $table['goods'];
        return goodsModel::get_delete($this->table, $this->where, $this->format);
    }

    /**
     * 获取商品仓库列表
     * 仓库模式
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_warehouse_list($table)
    {
        $this->table = $table['warehouse'];
        $result = goodsModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order);
        $result = goodsModel::get_list_common_data($result, $this->page_size, $this->page, $this->goodsLangList, $this->format);

        return $result;
    }

    /**
     * 获取单条商品仓库信息
     * 仓库模式
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_warehouse_info($table)
    {
        $this->table = $table['warehouse'];
        $result = goodsModel::get_select_info($this->table, $this->select, $this->where);

        if (strlen($this->where) != 1) {
            $result = goodsModel::get_info_common_data_fs($result, $this->goodsLangList, $this->format);
        } else {
            $result = goodsModel::get_info_common_data_f($this->goodsLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入商品仓库信息
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_warehouse_insert($table)
    {
        $this->table = $table['warehouse'];

        $goodsLang = goodsLang::lang_goods_insert();

        $select = $this->goods_select;

        $info = [];
        if ($select) {
            if (!isset($select['w_id'])) {
                if (isset($select['region_sn']) && !empty($select['region_sn'])) {
                    if (isset($select['user_id']) && !empty($select['user_id'])) {
                        $user_id = $select['user_id'];
                    } else {
                        $user_id = 0;
                    }

                    $where = "region_sn = '" . $select['region_sn'] . "' AND user_id = '$user_id'";
                    $info = $this->get_select_info($this->table, "*", $where);

                    if (!$info) {
                        return goodsModel::get_insert($this->table, $this->goods_select, $this->format);
                    } else {
                        $error = goodsLang::INSERT_SAME_NAME_FAILURE;
                        $info = $select;
                    }
                } else {
                    $error = goodsLang::INSERT_KEY_PARAM_FAILURE;
                    $string = 'region_sn';
                }
            } else {
                $info = $select;
                $error = goodsLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'w_id';
            }
        } else {
            $error = goodsLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [goodsLang::INSERT_CANNOT_PARAM_FAILURE, goodsLang::INSERT_KEY_PARAM_FAILURE])) {
            $goodsLang['msg_failure'][$error]['failure'] = sprintf($goodsLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $goodsLang['msg_failure'][$error]['failure'],
            'error' => $goodsLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新商品仓库信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $goods_select 商品字段信息
     * @return  array
     */
    public function get_goods_warehouse_update($table)
    {
        $this->table = $table['warehouse'];

        $goodsLang = goodsLang::lang_goods_update();

        $select = $this->goods_select;

        $info = [];
        if ($select) {
            if (!isset($select['w_id'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = goodsLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        $region_sn = 0;
                        if (isset($select['region_sn']) && !empty($select['region_sn'])) {
                            $where = "region_sn = '" . $select['region_sn'] . "' AND w_id <> '" . $info['w_id'] . "' AND user_id = '" . $info['user_id'] . "'";
                            $region_sn = $this->get_select_info($this->table, "*", $where);
                        }

                        if ($region_sn) {
                            $error = goodsLang::UPDATE_DATA_EXIST_FAILURE;
                            $info = $select;
                        } else {
                            return goodsModel::get_update($this->table, $this->goods_select, $this->where, $this->format, $info);
                        }
                    }
                } else {
                    $error = goodsLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = goodsLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'w_id';
            }
        } else {
            $error = goodsLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [goodsLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $goodsLang['msg_failure'][$error]['failure'] = sprintf($goodsLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $goodsLang['msg_failure'][$error]['failure'],
            'error' => $goodsLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除商品仓库信息
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_goods_warehouse_delete($table)
    {
        $this->table = $table['warehouse'];
        return goodsModel::get_delete($this->table, $this->where, $this->format);
    }

    /**
     * 获取商品仓库地区列表
     * 地区模式
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_area_list($table)
    {
        $this->table = $table['area'];
        $result = goodsModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order);
        $result = goodsModel::get_list_common_data($result, $this->page_size, $this->page, $this->goodsLangList, $this->format);

        return $result;
    }

    /**
     * 获取单条商品仓库地区信息
     * 地区模式
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_area_info($table)
    {
        $this->table = $table['area'];
        $result = goodsModel::get_select_info($this->table, $this->select, $this->where);

        if (strlen($this->where) != 1) {
            $result = goodsModel::get_info_common_data_fs($result, $this->goodsLangList, $this->format);
        } else {
            $result = goodsModel::get_info_common_data_f($this->goodsLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入商品仓库地区信息
     * 地区模式
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_area_insert($table)
    {
        $this->table = $table['area'];

        $goodsLang = goodsLang::lang_goods_insert();

        $select = $this->goods_select;

        $info = [];
        if ($select) {
            if (!isset($select['a_id'])) {
                if (isset($select['region_sn']) && !empty($select['region_sn'])) {
                    if (isset($select['user_id']) && !empty($select['user_id'])) {
                        $user_id = $select['user_id'];
                    } else {
                        $user_id = 0;
                    }

                    $where = "region_sn = '" . $select['region_sn'] . "' AND user_id = '$user_id'";

                    $is_region = 1;
                    if (isset($select['province_name']) && $select['province_name']) {
                        $sql = "SELECT region_id FROM " . $GLOBALS['dsc']->table('region_warehouse') . " WHERE region_type = 1 AND region_name = '" . $select['province_name'] . "'";
                        $region_id = $GLOBALS['db']->getOne($sql);

                        if ($region_id) {
                            $is_region = 1;
                            $select['region_id'] = $region_id;
                        } else {
                            $is_region = 0;
                        }
                    } elseif (isset($select['city_name']) && $select['city_name']) {
                        $sql = "SELECT region_id FROM " . $GLOBALS['dsc']->table('region_warehouse') . " WHERE region_type = 2 AND region_name = '" . $select['city_name'] . "'";
                        $region_id = $GLOBALS['db']->getOne($sql);

                        if ($region_id) {
                            $is_region = 1;
                            $select['region_id'] = $region_id;
                        } else {
                            $is_region = 0;
                        }
                    }

                    if ($is_region == 1) {
                        $info = $this->get_select_info($this->table, "*", $where);

                        if (!$info) {
                            return goodsModel::get_insert($this->table, $select, $this->format);
                        } else {
                            $error = goodsLang::INSERT_SAME_NAME_FAILURE;
                            $info = $select;
                        }
                    } else {
                        $error = goodsLang::INSERT_DATA_FAILURE;
                        $info = $select;
                    }
                } else {
                    $error = goodsLang::INSERT_KEY_PARAM_FAILURE;
                    $string = 'region_sn';
                }
            } else {
                $info = $select;
                $error = goodsLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'a_id';
            }
        } else {
            $error = goodsLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [goodsLang::INSERT_CANNOT_PARAM_FAILURE, goodsLang::INSERT_KEY_PARAM_FAILURE])) {
            $goodsLang['msg_failure'][$error]['failure'] = sprintf($goodsLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $goodsLang['msg_failure'][$error]['failure'],
            'error' => $goodsLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新商品仓库地区信息
     * 地区模式
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_area_update($table)
    {
        $this->table = $table['area'];

        $goodsLang = goodsLang::lang_goods_update();

        $select = $this->goods_select;

        $info = [];
        if ($select) {
            if (!isset($select['a_id'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = goodsLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        $region_sn = 0;
                        if (isset($select['region_sn']) && !empty($select['region_sn'])) {
                            $where = "region_sn = '" . $select['region_sn'] . "' AND a_id <> '" . $info['a_id'] . "' AND user_id = '" . $info['user_id'] . "'";
                            $region_sn = $this->get_select_info($this->table, "*", $where);
                        }

                        $is_region = 1;
                        if (isset($select['province_name']) && $select['province_name']) {
                            $sql = "SELECT region_id FROM " . $GLOBALS['dsc']->table('region_warehouse') . " WHERE region_type = 1 AND province_name = '" . $select['province_name'] . "'";
                            $region_id = $GLOBALS['db']->getOne($sql);

                            if ($region_id) {
                                $is_region = 1;
                                $select['region_id'] = $region_id;
                                $info['province_name'] = $select['province_name'];
                            } else {
                                $is_region = 0;
                            }
                        } elseif (isset($select['city_name']) && $select['city_name']) {
                            $sql = "SELECT region_id FROM " . $GLOBALS['dsc']->table('region_warehouse') . " WHERE region_type = 2 AND region_name = '" . $select['city_name'] . "'";
                            $region_id = $GLOBALS['db']->getOne($sql);

                            if ($region_id) {
                                $is_region = 1;
                                $select['region_id'] = $region_id;
                                $info['city_name'] = $select['city_name'];
                            } else {
                                $is_region = 0;
                            }
                        }

                        if ($is_region == 1) {
                            if ($region_sn) {
                                $error = goodsLang::UPDATE_DATA_EXIST_FAILURE;
                                $info = $select;
                            } else {
                                return goodsModel::get_update($this->table, $select, $this->where, $this->format, $info);
                            }
                        } else {
                            $error = goodsLang::UPDATE_DATA_FAILURE;
                            $info = $select;
                        }
                    }
                } else {
                    $error = goodsLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = goodsLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'a_id';
            }
        } else {
            $error = goodsLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [goodsLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $goodsLang['msg_failure'][$error]['failure'] = sprintf($goodsLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $goodsLang['msg_failure'][$error]['failure'],
            'error' => $goodsLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除商品仓库地区信息
     * 地区模式
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_goods_area_delete($table)
    {
        $this->table = $table['area'];
        return goodsModel::get_delete($this->table, $this->where, $this->format);
    }

    /**
     * 获取商品相册列表
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_gallery_list($table)
    {
        $this->table = $table['gallery'];
        $result = goodsModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order);
        $result = goodsModel::get_list_common_data($result, $this->page_size, $this->page, $this->goodsLangList, $this->format);

        return $result;
    }

    /**
     * 获取单条商品相册
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_gallery_info($table)
    {
        $this->table = $table['gallery'];
        $result = goodsModel::get_select_info($this->table, $this->select, $this->where);

        if (strlen($this->where) != 1) {
            $result = goodsModel::get_info_common_data_fs($result, $this->goodsLangList, $this->format);
        } else {
            $result = goodsModel::get_info_common_data_f($this->goodsLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入商品相册
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_gallery_insert($table)
    {
        $this->table = $table['gallery'];

        $goodsLang = goodsLang::lang_goods_insert();

        $select = $this->goods_select;

        $info = [];
        if ($select) {
            if (!isset($select['img_id'])) {
                if (isset($select['goods_id']) && !empty($select['goods_id'])) {
                    return goodsModel::get_insert($this->table, $this->goods_select, $this->format);
                } else {
                    $error = goodsLang::INSERT_NULL_NAME_FAILURE;
                    $info = $select;
                }
            } else {
                $info = $select;
                $error = goodsLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'img_id';
            }
        } else {
            $error = goodsLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [goodsLang::INSERT_CANNOT_PARAM_FAILURE])) {
            $goodsLang['msg_failure'][$error]['failure'] = sprintf($goodsLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $goodsLang['msg_failure'][$error]['failure'],
            'error' => $goodsLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新商品相册
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_gallery_update($table)
    {
        $this->table = $table['gallery'];

        $goodsLang = goodsLang::lang_goods_update();

        $select = $this->goods_select;

        $info = [];
        if ($select) {
            if (!isset($select['img_id'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = goodsLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        return goodsModel::get_update($this->table, $this->goods_select, $this->where, $this->format, $info);
                    }
                } else {
                    $error = goodsLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = goodsLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'img_id';
            }
        } else {
            $error = goodsLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [goodsLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $goodsLang['msg_failure'][$error]['failure'] = sprintf($goodsLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $goodsLang['msg_failure'][$error]['failure'],
            'error' => $goodsLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除商品相册
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_goods_gallery_delete($table)
    {
        $this->table = $table['gallery'];
        return goodsModel::get_delete($this->table, $this->where, $this->format);
    }

    /**
     * 获取商品属性列表
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_attr_list($table)
    {
        $this->table = $table['attr'];
        $result = goodsModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order);
        $result = goodsModel::get_list_common_data($result, $this->page_size, $this->page, $this->goodsLangList, $this->format);

        return $result;
    }

    /**
     * 获取单条商品属性
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_attr_info($table)
    {
        $this->table = $table['attr'];
        $result = goodsModel::get_select_info($this->table, $this->select, $this->where);

        if (strlen($this->where) != 1) {
            $result = goodsModel::get_info_common_data_fs($result, $this->goodsLangList, $this->format);
        } else {
            $result = goodsModel::get_info_common_data_f($this->goodsLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入商品属性
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_attr_insert($table)
    {
        $this->table = $table['attr'];

        $goodsLang = goodsLang::lang_goods_insert();

        $select = $this->goods_select;

        $info = [];
        if ($select) {
            if (!isset($select['goods_attr_id'])) {
                if (isset($select['goods_id']) && !empty($select['goods_id']) && isset($select['attr_id']) && !empty($select['attr_id'])) {
                    return goodsModel::get_insert($this->table, $this->goods_select, $this->format);
                } else {
                    $error = goodsLang::INSERT_NULL_NAME_FAILURE;
                    $info = $select;
                }
            } else {
                $info = $select;
                $error = goodsLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'goods_attr_id';
            }
        } else {
            $error = goodsLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [goodsLang::INSERT_CANNOT_PARAM_FAILURE])) {
            $goodsLang['msg_failure'][$error]['failure'] = sprintf($goodsLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $goodsLang['msg_failure'][$error]['failure'],
            'error' => $goodsLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新商品属性
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_attr_update($table)
    {
        $this->table = $table['attr'];

        $goodsLang = goodsLang::lang_goods_update();

        $select = $this->goods_select;

        $info = [];
        if ($select) {
            if (!isset($select['goods_attr_id'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = goodsLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        return goodsModel::get_update($this->table, $this->goods_select, $this->where, $this->format, $info);
                    }
                } else {
                    $error = goodsLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = goodsLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'goods_attr_id';
            }
        } else {
            $error = goodsLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [goodsLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $goodsLang['msg_failure'][$error]['failure'] = sprintf($goodsLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $goodsLang['msg_failure'][$error]['failure'],
            'error' => $goodsLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除商品属性
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_goods_attr_delete($table)
    {
        $this->table = $table['attr'];
        return goodsModel::get_delete($this->table, $this->where, $this->format);
    }

    /**
     * 获取商品运费模板列表
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_freight_list($table)
    {
        if ($this->seller_id != -1) {
            $this->where = "gt.ru_id = " . $this->seller_id . " GROUP BY gt.tid";
        }

        $join_on = array(
            '',
            "tid|tid",
            "tid|tid"
        );

        $this->table = $table;
        $result = goodsModel::get_join_select_list($this->table, $this->select, $this->where, $join_on);
        $result = goodsModel::get_list_common_data($result, $this->page_size, $this->page, $this->goodsLangList, $this->format);

        return $result;
    }

    /**
     * 获取单条商品运费模板
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_freight_info($table)
    {
        if ($this->tid != -1) {
            $this->where = "gt.tid = " . $this->tid . " GROUP BY gt.tid";
        }

        $join_on = array(
            '',
            "tid|tid",
            "tid|tid"
        );

        $this->table = $table;
        $result = goodsModel::get_join_select_info($this->table, $this->select, $this->where, $join_on);

        if (strlen($this->where) != 1) {
            $result = goodsModel::get_info_common_data_fs($result, $this->goodsLangList, $this->format);
        } else {
            $result = goodsModel::get_info_common_data_f($this->goodsLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入商品运费模板
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_freight_insert($table)
    {
        $this->table = $table;

        $goodsLang = goodsLang::lang_goods_insert();

        $select = $this->goods_select;

        $info = [];
        if ($select) {
            if (!isset($select['tid'])) {
                return goodsModel::get_more_insert($this->table, $this->goods_select, $this->format);
            } else {
                $info = $select;
                $error = goodsLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'tid';
            }
        } else {
            $error = goodsLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [goodsLang::INSERT_CANNOT_PARAM_FAILURE])) {
            $goodsLang['msg_failure'][$error]['failure'] = sprintf($goodsLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $goodsLang['msg_failure'][$error]['failure'],
            'error' => $goodsLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新商品运费模板
     *
     * @access  public
     * @param   integer $data 商品字段信息
     * @return  array
     */
    public function get_goods_freight_update($table)
    {
        $this->table = $table;

        $goodsLang = goodsLang::lang_goods_update();

        $select = $this->goods_select;

        $info = [];
        if ($select) {
            if (!isset($select['tid'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = goodsLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        return goodsModel::get_more_update($this->table, $this->goods_select, $this->where, $this->format, $info);
                    }
                } else {
                    $error = goodsLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = goodsLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'tid';
            }
        } else {
            $error = goodsLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [goodsLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $goodsLang['msg_failure'][$error]['failure'] = sprintf($goodsLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $goodsLang['msg_failure'][$error]['failure'],
            'error' => $goodsLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除商品运费模板
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_goods_freight_delete($table)
    {
        $this->table = $table;
        return goodsModel::get_more_delete($this->table, $this->where, $this->format);
    }

    /**
     * 批量插入商品信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $goods_select 商品字段信息
     * @return  array
     */
    public function get_goods_batchinsert($table)
    {
        $this->table = $table['goods'];
        return goodsModel::get_goods_batch_insert($this->table, $this->goods_select, $this->format);
    }

    /**
     * 信息更新通知
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $goods_select 商品字段信息
     * @return  array
     */
    public function get_goods_notification_update($table)
    {
        $this->table = $table;
        return goodsModel::get_goodsnotification_update($this->table, $this->goods_select, $this->format);
    }
}
