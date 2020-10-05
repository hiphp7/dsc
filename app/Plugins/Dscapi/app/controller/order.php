<?php

namespace App\Plugins\Dscapi\app\controller;

use App\Plugins\Dscapi\app\func\common;
use App\Plugins\Dscapi\app\func\base;
use App\Plugins\Dscapi\app\model\orderModel;
use App\Plugins\Dscapi\languages\orderLang;

class order extends orderModel
{
    private $method = '';
    private $table;                          //表名称
    private $alias = '';                     //表别名
    private $order_select = array();         //查询字段数组
    private $select;                         //查询字段字符串组
    private $seller_id = 0;                  //商家ID
    private $order_id = 0;                   //订单ID
    private $order_sn = 0;                   //订单编号

    private $start_add_time = 0;                //添加开始时间
    private $end_add_time = 0;                  //添加结束时间
    private $start_confirm_time = 0;            //订单确认开始时间
    private $end_confirm_time = 0;              //订单确认结束时间
    private $start_pay_time = 0;                //支付开始时间
    private $end_pay_time = 0;                  //支付结束时间
    private $start_shipping_time = 0;           //配送开始时间
    private $end_shipping_time = 0;             //配送结束时间
    private $start_take_time = 0;               //确认收货开始时间
    private $end_take_time = 0;                 //确认收货结束时间

    private $order_status = 0;                   //订单状态
    private $shipping_status = 0;                //配送状态
    private $pay_status = 0;                     //订单编号

    private $mobile = 0;                     //订单联系手机号码
    private $goods_sn = '';                  //商品货号
    private $goods_id = 0;                   //商品ID
    private $rec_id = 0;                     //订单商品ID
    private $format = 'json';                //返回格式（json, xml, array）
    private $page_size = 10;                 //每页条数
    private $page = 1;                       //当前页
    private $where_val;                      //查询条件
    private $orderLangList;                  //语言包
    private $sort_by = 'order_id';           //排序字段
    private $sort_order;                     //排序升降

    public function __construct($where = array())
    {
        $this->order($where);

        $this->where_val = array(
            'method' => $this->method,
            'seller_id' => $this->seller_id,
            'order_id' => $this->order_id,
            'order_sn' => $this->order_sn,
            'mobile' => $this->mobile,
            'rec_id' => $this->rec_id,
            'goods_sn' => $this->goods_sn,
            'goods_id' => $this->goods_id,
            'start_add_time' => $this->start_add_time,
            'end_add_time' => $this->end_add_time,
            'start_confirm_time' => $this->start_confirm_time,
            'end_confirm_time' => $this->end_confirm_time,
            'start_pay_time' => $this->start_pay_time,
            'end_pay_time' => $this->end_pay_time,
            'start_shipping_time' => $this->start_shipping_time,
            'end_shipping_time' => $this->end_shipping_time,
            'start_take_time' => $this->start_take_time,
            'end_take_time' => $this->end_take_time,
            'order_status' => $this->order_status,
            'shipping_status' => $this->shipping_status,
            'pay_status' => $this->pay_status,
        );

        if ($this->seller_id > 0 || $this->mobile > 0 || ($this->start_take_time > -1 || $this->end_take_time > -1)) {
            $this->alias = 'o.';
        }

        $this->where = orderModel::get_where($this->where_val, $this->alias);
        $this->select = base::get_select_field($this->order_select);
    }

    public function order($where = array())
    {

        /* 初始查询条件值 */
        $this->method = $where['method'];
        $this->seller_id = $where['seller_id'];
        $this->order_id = $where['order_id'];
        $this->order_sn = $where['order_sn'];
        $this->start_add_time = $where['start_add_time'];
        $this->end_add_time = $where['end_add_time'];
        $this->start_confirm_time = $where['start_confirm_time'];
        $this->end_confirm_time = $where['end_confirm_time'];
        $this->start_pay_time = $where['start_pay_time'];
        $this->end_pay_time = $where['end_pay_time'];
        $this->start_shipping_time = $where['start_shipping_time'];
        $this->end_shipping_time = $where['end_shipping_time'];
        $this->start_take_time = $where['start_take_time'];
        $this->end_take_time = $where['end_take_time'];
        $this->order_status = $where['order_status'];
        $this->shipping_status = $where['shipping_status'];
        $this->pay_status = $where['pay_status'];
        $this->mobile = $where['mobile'];
        $this->rec_id = $where['rec_id'];
        $this->goods_sn = $where['goods_sn'];
        $this->goods_id = $where['goods_id'];
        $this->order_select = $where['order_select'];
        $this->format = $where['format'];
        $this->page_size = $where['page_size'];
        $this->page = $where['page'];
        $this->sort_by = !empty($where['sort_by']) ? $where['sort_by'] : $this->sort_by;
        $this->sort_order = $where['sort_order'];

        $this->orderLangList = orderLang::lang_order_request();
    }

    /**
     * 多条订单信息
     *
     * @access  public
     * @param   integer $goods_id 商品ID
     * @return  array
     */
    public function get_order_list($table)
    {
        $this->table = $table['order'];
        $result = orderModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order, $this->alias);
        $result = orderModel::get_list_common_data($result, $this->page_size, $this->page, $this->orderLangList, $this->format);

        return $result;
    }

    /**
     * 单条订单信息
     *
     * @access  public
     * @param   integer $goods_id 商品ID
     * @return  array
     */
    public function get_order_info($table)
    {
        $this->table = $table['order'];
        $result = orderModel::get_select_info($this->table, $this->select, $this->where, $this->alias);

        if (strlen($this->where) != 1) {
            $result = orderModel::get_info_common_data_fs($result, $this->orderLangList, $this->format);
        } else {
            $result = orderModel::get_info_common_data_f($this->orderLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入订单信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $order_select 商品字段信息
     * @return  array
     */
    public function get_order_insert($table)
    {
        $this->table = $table['order'];

        $orderLang = orderLang::lang_order_insert();

        $select = $this->order_select;

        $info = [];
        if ($select) {
            if (!isset($select['order_id'])) {
                if (isset($select['order_sn']) && !empty($select['order_sn'])) {
                    $where = "order_sn = '" . $select['order_sn'] . "'";
                    $info = $this->get_select_info($this->table, "*", $where);

                    if (!$info) {
                        return orderModel::get_insert($this->table, $this->order_select, $this->format);
                    } else {
                        $error = orderLang::INSERT_DATA_EXIST_FAILURE;
                        $info = $select;
                    }
                } else {
                    $error = orderLang::INSERT_KEY_PARAM_FAILURE;
                    $string = 'order_sn';
                }
            } else {
                $info = $select;
                $error = orderLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'order_id';
            }
        } else {
            $error = orderLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [orderLang::INSERT_CANNOT_PARAM_FAILURE, orderLang::INSERT_KEY_PARAM_FAILURE])) {
            $orderLang['msg_failure'][$error]['failure'] = sprintf($orderLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $orderLang['msg_failure'][$error]['failure'],
            'error' => $orderLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新订单信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $order_select 商品字段信息
     * @return  array
     */
    public function get_order_update($table)
    {
        $this->table = $table['order'];

        $orderLang = orderLang::lang_order_update();

        $select = $this->order_select;

        $info = [];
        if ($select) {
            if (!isset($select['order_id'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = orderLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        $order_sn = 0;
                        if (isset($select['order_sn']) && !empty($select['order_sn'])) {
                            $where = "order_sn = '" . $select['order_sn'] . "' AND order_id <> '" . $info['order_id'] . "'";
                            $order_sn = $this->get_select_info($this->table, "*", $where);
                        }

                        if ($order_sn) {
                            $error = orderLang::UPDATE_DATA_EXIST_FAILURE;
                            $info = $select;
                        } else {
                            return orderModel::get_update($this->table, $this->order_select, $this->where, $this->format, $info);
                        }
                    }
                } else {
                    $error = orderLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = orderLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'order_id';
            }
        } else {
            $error = orderLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [orderLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $orderLang['msg_failure'][$error]['failure'] = sprintf($orderLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $orderLang['msg_failure'][$error]['failure'],
            'error' => $orderLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除订单信息
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_order_delete($table)
    {
        $this->table = $table['order'];
        return orderModel::get_delete($this->table, $this->where, $this->format);
    }

    /**
     * 多条订单商品信息
     *
     * @access  public
     * @param   integer $goods_id 商品ID
     * @return  array
     */
    public function get_order_goods_list($table)
    {
        if (isset($this->order_sn) && $this->order_sn != -1) {
            $order_id = $this->get_order_id($table['order']);

            $this->where .= " AND order_id " . db_create_in($order_id);
        }

        $this->table = $table['goods'];
        $result = orderModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order);
        $result = orderModel::get_list_common_data($result, $this->page_size, $this->page, $this->orderLangList, $this->format);

        return $result;
    }

    /**
     * 单条订单商品信息
     *
     * @access  public
     * @param   integer $goods_id 商品ID
     * @return  array
     */
    public function get_order_goods_info($table)
    {
        if (isset($this->order_sn) && !empty($this->order_sn)) {
            $order_id = $this->get_order_id($table['order']);

            $this->where .= " AND order_id " . db_create_in($order_id);
        }

        $this->table = $table['goods'];

        if (isset($this->order_sn) && !empty($this->order_sn)) {
            $result = orderModel::get_select_list($this->table, $this->select, $this->where);
        } else {
            $result = orderModel::get_select_info($this->table, $this->select, $this->where);
        }

        if (strlen($this->where) != 1) {
            $result = orderModel::get_info_common_data_fs($result, $this->orderLangList, $this->format);
        } else {
            $result = orderModel::get_info_common_data_f($this->orderLangList, $this->format);
        }

        return $result;
    }

    /**
     * 插入订单商品信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $order_select 商品字段信息
     * @return  array
     */
    public function get_order_goods_insert($table)
    {
        $this->table = $table['goods'];

        $orderLang = orderLang::lang_order_insert();

        $select = $this->order_select;

        $info = [];
        if ($select) {
            if (!isset($select['rec_id'])) {
                if (isset($select['order_id']) && !empty($select['order_id'])) {
                    $where = " order_id = '" . $select['order_id'] . "'";
                    $order = $this->get_select_info($table['order'], "*", $where);

                    if (!$order) {
                        $error = orderLang::INSERT_DATA_EXIST_FAILURE;
                        $info = $select;
                    } else {
                        return orderModel::get_insert($this->table, $this->order_select, $this->format);
                    }
                } else {
                    $error = orderLang::INSERT_KEY_PARAM_FAILURE;
                    $string = 'order_id';
                }
            } else {
                $info = $select;
                $error = orderLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'rec_id';
            }
        } else {
            $error = orderLang::INSERT_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [orderLang::INSERT_CANNOT_PARAM_FAILURE, orderLang::INSERT_KEY_PARAM_FAILURE])) {
            $orderLang['msg_failure'][$error]['failure'] = sprintf($orderLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $orderLang['msg_failure'][$error]['failure'],
            'error' => $orderLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新订单商品信息
     *
     * @access  public
     * @param   integer $table 表名称
     * @param   integer $order_select 商品字段信息
     * @return  array
     */
    public function get_order_goods_update($table)
    {
        if (isset($this->order_sn) && !empty($this->order_sn)) {
            $order_id = $this->get_order_id($table['order']);

            $this->where .= " AND order_id " . db_create_in($order_id);
        }

        $this->table = $table['goods'];

        $orderLang = orderLang::lang_order_update();

        $select = $this->order_select;

        $info = [];
        if ($select) {
            if (!isset($select['rec_id'])) {
                if (strlen($this->where) != 1) {
                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = orderLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        if (isset($select['order_id']) && !empty($select['order_id'])) {
                            $error = orderLang::UPDATE_CANNOT_PARAM_FAILURE;
                            $string = 'order_id';
                            $info = $select;
                        } else {
                            return orderModel::get_update($this->table, $this->order_select, $this->where, $this->format, $info);
                        }
                    }
                } else {
                    $error = orderLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = orderLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'rec_id';
            }
        } else {
            $error = orderLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [orderLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $orderLang['msg_failure'][$error]['failure'] = sprintf($orderLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $orderLang['msg_failure'][$error]['failure'],
            'error' => $orderLang['msg_failure'][$error]['error'],
            'format' => $this->format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除订单商品信息
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_order_goods_delete($table)
    {
        if (isset($this->order_sn) && !empty($this->order_sn)) {
            $order_id = $this->get_order_id($table['order']);

            $this->where .= " AND order_id " . db_create_in($order_id);

            $sql = "DELETE FROM " . $GLOBALS['dsc']->table($table['order']) . " WHERE order_id " . db_create_in($order_id);
            $GLOBALS['db']->query($sql);
        }

        $this->table = $table['goods'];
        return orderModel::get_delete($this->table, $this->where, $this->format);
    }

    /**
     * 商品发货
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    public function get_order_confirmorder($table)
    {
        $this->table = $table['order'];
        return orderModel::get_confirmorder($this->table, $this->order_select, $this->format);
    }

    /**
     * 商品发货
     *
     * @access  public
     * @return  int
     */
    private function get_order_id($table)
    {
        $sql = "SELECT GROUP_CONCAT(order_id) AS order_id FROM " . $GLOBALS['dsc']->table($table) . " WHERE order_sn " . db_create_in($this->order_sn);
        return $GLOBALS['db']->getOne($sql);
    }
}
