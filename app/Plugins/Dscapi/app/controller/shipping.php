<?php

namespace App\Plugins\Dscapi\app\controller;

use App\Plugins\Dscapi\app\func\common;
use App\Plugins\Dscapi\app\func\base;
use App\Plugins\Dscapi\app\model\shippingModel;
use App\Plugins\Dscapi\languages\shippingLang;

class shipping extends shippingModel
{
    private $table;                                 //表名称
    private $shipping_select = array();             //查询字段数组
    private $select;                                //查询字段字符串组
    private $shipping_id = 0;                       //快递方式ID
    private $shipping_name = '';                    //快递方式名称
    private $shipping_code = '';                    //快递编码
    private $format = 'json';                       //返回格式（json, xml, array）
    private $page_size = 10;                        //每页条数
    private $page = 1;                              //当前页
    private $where_val;                             //查询条件
    private $shippingLangList;                      //语言包
    private $sort_by;                               //排序字段
    private $sort_order;                            //排序升降

    public function __construct($where = array())
    {
        $this->shipping($where);

        $this->where_val = array(
            'shipping_id' => $this->shipping_id,
            'shipping_code' => $this->shipping_code,
            'shipping_name' => $this->shipping_name,
        );

        $this->where = shippingModel::get_where($this->where_val);
        $this->select = base::get_select_field($this->shipping_select);
    }

    public function shipping($where = array())
    {

        /* 初始查询条件值 */
        $this->shipping_id = $where['shipping_id'];
        $this->shipping_code = $where['shipping_code'];
        $this->shipping_name = $where['shipping_name'];
        $this->shipping_select = $where['shipping_select'];
        $this->format = $where['format'];
        $this->page_size = $where['page_size'];
        $this->page = $where['page'];
        $this->sort_by = $where['sort_by'];
        $this->sort_order = $where['sort_order'];

        $this->shippingLangList = shippingLang::lang_shipping_request();
    }

    /**
     * 多条快递方式信息
     *
     * @access  public
     * @return  array
     */
    public function get_shipping_list($table)
    {
        $this->table = $table['shipping'];
        $result = shippingModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order);
        $result = shippingModel::get_list_common_data($result, $this->page_size, $this->page, $this->shippingLangList, $this->format);

        return $result;
    }

    /**
     * 单条快递方式信息
     *
     * @access  public
     * @return  array
     */
    public function get_shipping_details($table)
    {
        $this->table = $table['shipping'];
        $result = shippingModel::get_select_info($this->table, $this->select, $this->where);

        if (strlen($this->where) != 1) {
            $result = shippingModel::get_info_common_data_fs($result, $this->shippingLangList, $this->format);
        } else {
            $result = shippingModel::get_info_common_data_f($this->shippingLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入快递方式信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $shipping_select 字段信息
     * @return  array
     */
    public function get_shipping_insert($table)
    {
        $this->table = $table['shipping'];

        $shippingLang = shippingLang::lang_shipping_insert();

        $select = $this->shipping_select;

        $info = [];
        if ($select) {
            if (!isset($select['shipping_id'])) {
                if (isset($select['shipping_code']) && !empty($select['shipping_code'])) {
                    $where = "shipping_code = '" . $select['shipping_code'] . "'";
                    $info = $this->get_select_info($this->table, "*", $where);

                    if (!$info) {
                        if (isset($select['shipping_name']) && !empty($select['shipping_name'])) {
                            $where = "shipping_name = '" . $select['shipping_name'] . "'";
                            $info = $this->get_select_info($this->table, "*", $where);

                            if (!$info) {
                                return shippingModel::get_insert($this->table, $this->shipping_select, $this->format);
                            } else {
                                $error = shippingLang::INSERT_SAME_NAME_FAILURE;
                                $info = $select;
                            }
                        } else {
                            $error = shippingLang::INSERT_NULL_NAME_FAILURE;
                            $info = $select;
                        }
                    } else {
                        $error = shippingLang::INSERT_DATA_EXIST_FAILURE;
                        $info = $select;
                    }
                } else {
                    $error = shippingLang::INSERT_KEY_PARAM_FAILURE;
                    $string = 'shipping_code';
                }
            } else {
                $info = $select;
                $error = shippingLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'shipping_id';
            }
        } else {
            $error = shippingLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [shippingLang::INSERT_CANNOT_PARAM_FAILURE, shippingLang::INSERT_KEY_PARAM_FAILURE])) {
            $shippingLang['msg_failure'][$error]['failure'] = sprintf($shippingLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $shippingLang['msg_failure'][$error]['failure'],
            'error' => $shippingLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新快递方式信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $shipping_select 商品字段信息
     * @return  array
     */
    public function get_shipping_update($table)
    {
        $this->table = $table['shipping'];

        $shippingLang = shippingLang::lang_shipping_update();

        $select = $this->shipping_select;

        $info = [];
        if ($select) {
            if (!isset($select['shipping_id'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = shippingLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        $shipping_code = '';
                        if (isset($select['shipping_code'])) {
                            $where = "shipping_code = '" . $select['shipping_code'] . "' AND shipping_id <> '" . $info['shipping_id'] . "'";
                            $shipping_code = $this->get_select_info($this->table, "*", $where);
                        }

                        if ($shipping_code) {
                            $error = shippingLang::UPDATE_DATA_EXIST_FAILURE;
                            $info = $select;
                        } else {
                            $shipping_name = '';
                            if (isset($select['shipping_name']) && !empty($select['shipping_name'])) {
                                $where = "shipping_name = '" . $select['shipping_name'] . "' AND shipping_id <> '" . $info['shipping_id'] . "'";
                                $shipping_name = $this->get_select_info($this->table, "*", $where);
                            }

                            if ($shipping_name) {
                                $error = shippingModel::UPDATE_SAME_NAME_FAILURE;
                            } else {
                                return shippingModel::get_update($this->table, $this->shipping_select, $this->where, $this->format, $info);
                            }
                        }
                    }
                } else {
                    $error = shippingLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = shippingLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'shipping_id';
            }
        } else {
            $error = shippingLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [shippingLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $shippingLang['msg_failure'][$error]['failure'] = sprintf($shippingLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $shippingLang['msg_failure'][$error]['failure'],
            'error' => $shippingLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除快递方式信息
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_shipping_delete($table)
    {
        $this->table = $table['shipping'];
        return shippingModel::get_delete($this->table, $this->where, $this->format);
    }
}
