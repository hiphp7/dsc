<?php

namespace App\Plugins\Dscapi\app\model;

use App\Plugins\Dscapi\app\func\common;
use App\Plugins\Dscapi\app\func\base;
use App\Plugins\Dscapi\languages\orderLang;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\TimeRepository;

abstract class orderModel extends common
{
    private $commonRepository;
    private $timeRepository;

    /* 订单状态 */
    const OS_UNCONFIRMED = 0;    // 未确认
    const OS_CONFIRMED = 1;    // 已确认
    const OS_CANCELED = 2;    // 已取消
    const OS_INVALID = 3;    // 无效
    const OS_RETURNED = 4;    // 退货
    const OS_SPLITED = 5;    // 已分单
    const OS_SPLITING_PART = 6;    // 部分分单
    const OS_RETURNED_PART = 7;    // 部分已退货
    const OS_ONLY_REFOUND = 8;    // 仅退款

    /* 支付类型 */
    const PAY_ORDER = 0;    // 订单支付
    const PAY_SURPLUS = 1;    // 会员预付款
    const PAY_APPLYGRADE = 2;    // 商家升级付款
    const PAY_TOPUP = 3;    // 商家账户充值
    const PAY_APPLYTEMP = 4;    // 商家购买模板付款
    const PAY_WHOLESALE = 5;    // 批发支付

    /* 配送状态 */
    const SS_UNSHIPPED = 0;    // 未发货
    const SS_SHIPPED = 1;    // 已发货
    const SS_RECEIVED = 2;    // 已收货
    const SS_PREPARING = 3;    // 备货中
    const SS_SHIPPED_PART = 4;    // 已发货(部分商品)
    const SS_SHIPPED_ING = 5;    // 发货中(处理分单)
    const OS_SHIPPED_PART = 6;    // 已发货(部分商品)

    /* 支付状态 */
    const PS_UNPAYED = 0;    // 未付款
    const PS_PAYING = 1;    // 付款中
    const PS_PAYED = 2;    // 已付款
    const PS_PAYED_PART = 3;    // 部分付款--预售定金
    const PS_REFOUND = 4;    // 已退款

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function __construct(
        CommonRepository $commonRepository,
        TimeRepository $timeRepository
    )
    {
        $this->orderModel();
        $this->commonRepository = $commonRepository;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 构造函数
     *
     * @access  public
     * @param   integer $goods_id 商品ID
     * @return  bool
     */
    public function orderModel($table = '')
    {
        $this->alias_config = array(
            'order_info' => 'o',                    //订单表
            'order_goods' => 'og',                  //订单商品表
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
        $method = [
            'dsc.order.goods.list.get',
            'dsc.order.goods.info.get',
            'dsc.order.goods.update.post',
            'dsc.order.goods.del.get',
        ];

        $where = 1;

        $conditions = '';

        if ($val['seller_id'] > 0 && (isset($val['method']) && $val['method'] == 'dsc.order.list.get')) {
            $conditions .= " AND {$alias}main_count = 0";
        }

        /* 商家ID */
        if ($val['seller_id'] != -1) {
            $conditions .= " AND {$alias}ru_id" . base::db_create_in($val['seller_id']);
            $where .= base::get_where(0, '', $conditions);
        }

        /* 订单ID */
        $where .= base::get_where($val['order_id'], $alias . 'order_id');

        if (isset($val['method']) && !in_array($val['method'], $method)) {
            /* 订单编号 */
            $where .= base::get_where($val['order_sn'], $alias . 'order_sn');
        }

        /* 添加开始时间 */
        $where .= base::get_where_time($val['start_add_time'], $alias . 'add_time');

        /* 添加结束时间 */
        $where .= base::get_where_time($val['end_add_time'], $alias . 'add_time', 1);

        /* 确认开始时间 */
        $where .= base::get_where_time($val['start_confirm_time'], $alias . 'confirm_time');

        /* 确认结束时间 */
        $where .= base::get_where_time($val['end_confirm_time'], $alias . 'confirm_time', 1);

        /* 支付开始时间 */
        $where .= base::get_where_time($val['start_pay_time'], $alias . 'pay_time');

        /* 支付结束时间 */
        $where .= base::get_where_time($val['end_pay_time'], $alias . 'pay_time', 1);

        /* 配送开始时间 */
        $where .= base::get_where_time($val['start_shipping_time'], $alias . 'shipping_time');

        /* 配送结束时间 */
        $where .= base::get_where_time($val['end_shipping_time'], $alias . 'shipping_time', 1);

        $where .= $this->get_take_time($val['start_take_time'], $val['end_take_time'], $alias);

        /* 订单状态 */
        $where .= base::get_where($val['order_status'], $alias . 'order_status');

        /* 配送状态 */
        $where .= base::get_where($val['shipping_status'], $alias . 'shipping_status');

        /* 付款状态 */
        $where .= base::get_where($val['pay_status'], $alias . 'pay_status');

        /* 订单联系手机号码 */
        $where .= base::get_where($val['mobile'], $alias . 'mobile');

        /* 订单商品ID */
        $where .= base::get_where($val['rec_id'], $alias . 'rec_id');

        /* 商品货号 */
        $where .= base::get_where($val['goods_sn'], $alias . 'goods_sn');

        /* 商品ID */
        $where .= base::get_where($val['goods_id'], $alias . 'goods_id');

        /* 商家ID【goods.list】 */
        $where .= base::get_where($val['seller_id'], $alias . 'ru_id');

        return $where;
    }

    /**
     * 查询条件
     *
     * @access  public
     * @param   string where    查询条件
     * @return  string
     */
    private function get_take_time($start_take_time = '', $end_take_time = '', $alias = '')
    {
        $where = "";
        if ((!empty($start_take_time) && $start_take_time != -1) || (!empty($end_take_time) && $end_take_time != -1)) {
            $where_action = '';
            if ($start_take_time) {
                $where_action .= " AND oa.log_time >= '$start_take_time'";
            }
            if ($end_take_time) {
                $where_action .= " AND oa.log_time <= '$end_take_time'";
            }

            $where_action .= $this->order_take_query_sql('finished', "oa.");

            $where .= " AND (SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('order_action') . " AS oa WHERE {$alias}order_id = oa.order_id $where_action) > 0";
        }

        return $where;
    }

    /**
     * 生成查询订单的sql
     * @param   string $type 类型
     * @param   string $alias order表的别名（包括.例如 o.）
     * @return  string
     */
    private function order_take_query_sql($type = 'finished', $alias = '')
    {
        /* 已完成订单 */
        if ($type == 'finished') {
            return " AND {$alias}order_status " . db_create_in(array(self::OS_SPLITED)) .
                " AND {$alias}shipping_status " . db_create_in(array(self::SS_RECEIVED)) .
                " AND {$alias}pay_status " . db_create_in(array(self::PS_PAYED)) . " ";
        }
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
    public function get_select_list($table, $select, $where, $page_size, $page, $sort_by, $sort_order, $alias = '')
    {
        $table_alias = '';
        if (!empty($alias)) {
            $table_alias = " AS " . str_replace(".", "", $alias);
            $sort_by = $alias . $sort_by;
        }

        if ($table == 'order_info') {
            $where .= " AND {$alias}main_count = 0 ";  //主订单下有子订单时，则主订单不显示
        }

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table($table) . $table_alias . " WHERE " . $where;
        $result['record_count'] = $GLOBALS['db']->getOne($sql);

        if ($sort_by) {
            $where .= " ORDER BY $sort_by $sort_order ";
        }

        if ($page_size) {
            $where .= " LIMIT " . ($page - 1) * $page_size . ",$page_size";
        }

        $sql = "SELECT " . $select . " FROM " . $GLOBALS['dsc']->table($table) . $table_alias . " WHERE " . $where;

        $result['list'] = $GLOBALS['db']->getAll($sql);

        if ($result['list']) {
            foreach ($result['list'] as $key => $row) {
                $country = isset($row['country']) ? common::get_regions_info($row['country']) : [];
                $province = isset($row['province']) ? common::get_regions_info($row['province']) : [];
                $city = isset($row['city']) ? common::get_regions_info($row['city']) : [];
                $district = isset($row['district']) ? common::get_regions_info($row['district']) : [];
                $street = isset($row['street']) ? common::get_regions_info($row['street']) : [];

                $result['list'][$key]['country_name'] = $country['region_name'] ?? '';
                $result['list'][$key]['province_name'] = $province['region_name'] ?? '';
                $result['list'][$key]['city_name'] = $city['region_name'] ?? '';
                $result['list'][$key]['district_name'] = $district['region_name'] ?? '';
                $result['list'][$key]['street_name'] = $street['region_name'] ?? '';
            }
        }

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
    public function get_select_info($table, $select, $where, $alias = '')
    {
        $table_alias = '';
        if (!empty($alias)) {
            $table_alias = " AS " . str_replace(".", "", $alias);
        }

        $sql = "SELECT " . $select . " FROM " . $GLOBALS['dsc']->table($table) . $table_alias . " WHERE " . $where . " LIMIT 1";
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
        $config = array_flip($this->orderModel());

        $orderLang = orderLang::lang_order_insert();

        if ($table == 'order_info') {
            $pay_id = isset($payment['pay_id']) && !empty($payment['pay_id']) ? intval($payment['pay_id']) : 0;

            if (isset($select['pay_name']) && !empty($select['pay_name']) && $pay_id == 0) {
                $pay_name = addslashes($select['pay_name']);
                $sql = "SELECT * FROM " . $GLOBALS['dsc']->table('payment') . " WHERE pay_name = '$pay_name' LIMIT 1";
                $payment = $GLOBALS['db']->getRow($sql);

                $select['pay_id'] = $payment['pay_id'] ?? 0;
                $select['pay_name'] = $payment['pay_name'] ?? 0;
            }
        }

        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table), $select, "INSERT");
        $id = $GLOBALS['db']->insert_id();

        /* 插入支付日志 */
        if ($id > 0 && $table == 'order_info') {
            load_helper(['clips']);
            insert_pay_log($id, $select['order_amount'], PAY_ORDER);
        }

        $info = $select;

        if ($id) {
            if ($table == $config['o']) {
                $info['order_id'] = $id;
            } elseif ($table == $config['og']) {
                $info['rec_id'] = $id;
            }
        }

        $common_data = array(
            'result' => "success",
            'msg' => $orderLang['msg_success']['success'],
            'error' => $orderLang['msg_success']['error'],
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
        $orderLang = orderLang::lang_order_update();

        if (isset($info['order_status']) && isset($info['shipping_status']) && isset($info['pay_status'])) {
            if (($info['order_status'] == 1 || $info['order_status'] == 5) && $info['pay_status'] == 2 && $info['shipping_status'] == 2) {
                $select['confirm_take_time'] = gmtime();
                $info['confirm_take_time'] = $select['confirm_take_time'];
            }
        }

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
            'msg' => $orderLang['msg_success']['success'],
            'error' => $orderLang['msg_success']['error'],
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
        $orderLang = orderLang::lang_order_delete();

        $return = false;
        if (strlen($where) != 1) {
            $sql = "DELETE FROM " . $GLOBALS['dsc']->table($table) . " WHERE " . $where;
            $GLOBALS['db']->query($sql);

            $return = true;
        } else {
            $error = orderLang::DEL_NULL_PARAM_FAILURE;
        }

        $common_data = array(
            'result' => $return ? "success" : "failure",
            'msg' => $return ? $orderLang['msg_success']['success'] : $orderLang['msg_failure'][$error]['failure'],
            'error' => $return ? $orderLang['msg_success']['error'] : $orderLang['msg_failure'][$error]['error'],
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
    public function get_list_common_data($result, $page_size, $page, $orderLang, $format)
    {
        $common_data = array(
            'page_size' => $page_size,
            'page' => $page,
            'result' => empty($result) ? "failure" : 'success',
            'msg' => empty($result) ? $orderLang['msg_failure']['failure'] : $orderLang['msg_success']['success'],
            'error' => empty($result) ? $orderLang['msg_failure']['error'] : $orderLang['msg_success']['error'],
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
    public function get_info_common_data_fs($result, $orderLang, $format)
    {
        $common_data = array(
            'result' => empty($result) ? "failure" : 'success',
            'msg' => empty($result) ? $orderLang['msg_failure']['failure'] : $orderLang['msg_success']['success'],
            'error' => empty($result) ? $orderLang['msg_failure']['error'] : $orderLang['msg_success']['error'],
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
    public function get_info_common_data_f($orderLang, $format)
    {
        $result = array();

        $common_data = array(
            'result' => 'failure',
            'msg' => $orderLang['where_failure']['failure'],
            'error' => $orderLang['where_failure']['error'],
            'format' => $format
        );

        common::common($common_data);
        $result = common::data_back($result);

        return $result;
    }

    /**
     * 订单发货
     *
     * @access  public
     * @return  string
     */
    public function get_confirmorder($table, $order_select, $format)
    {
        $orderLang = orderLang::lang_order_confirmorder();
        $result = array();

        //转换数据
        if (!empty($order_select)) {
            /* 定义当前时间 */
            define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳
            //订单号不能为空
            if ($order_select['orderSn'] == '') {
                $common_data = array(
                    'code' => $orderLang['ordersn_failure']['code'],
                    'message' => $orderLang['ordersn_failure']['message'],
                    'format' => $format
                );

                common::common($common_data);
                $result = common::data_back('', 2);

                return $result;
            }
            //运单号不能为空
            if ($order_select['expressNo'] == '') {
                $common_data = array(
                    'code' => $orderLang['expressno_failure']['code'],
                    'message' => $orderLang['expressno_failure']['message'],
                    'format' => $format
                );

                common::common($common_data);
                $result = common::data_back('', 2);

                return $result;
            }
            //快递不能为空
            if ($order_select['expressCode'] == '') {
                $common_data = array(
                    'code' => $orderLang['code_failure']['code'],
                    'message' => $orderLang['code_failure']['message'],
                    'format' => $format
                );

                common::common($common_data);
                $result = common::data_back('', 2);

                return $result;
            }
            $invoice_no = trim($order_select['expressNo']);
            //$sql = "SELECT * FROM".$GLOBALS['dsc']->table('shipping')."WHERE shipping_code = '" . $order_select['expressCode']. "' LIMIT 1";
            //$shipping_info = $GLOBALS['db']->getRow($sql);
            $shipping_info = common::get_shipping_info($order_select['expressCode']);

            // 配送方式兼容处理
            if (empty($shipping_info)) {
                $shipping_info = [
                    'shipping_id' => 0,
                    'shipping_name' => $order_select['expressName'],
                ];
            }

            //如果不支持该配送方式  返回错误
            if (empty($shipping_info)) {
                $common_data = array(
                    'code' => $orderLang['shipping_failure']['code'],
                    'message' => $orderLang['shipping_failure']['message'],
                    'format' => $format
                );

                common::common($common_data);
                $result = common::data_back('', 2);

                return $result;
            }

            $sql = "SELECT oi.postscript,oi.best_time,oi.mobile,oi.tel,oi.zipcode,oi.email,oi.address,oi.consignee,oi.how_oos,oi.add_time,oi.order_id,oi.user_id,oi.country,oi.province,oi.city,oi.district,oi.agency_id,oi.insure_fee,oi.shipping_fee,oi.order_sn FROM" .
                $GLOBALS['dsc']->table('order_info') . "AS oi LEFT JOIN" .
                $GLOBALS['dsc']->table('order_goods') . " AS og ON oi.order_id = og.order_id LEFT JOIN" .
                $GLOBALS['dsc']->table('order_cloud') . " AS oc ON og.rec_id = oc.rec_id WHERE oc.apiordersn = '" . $order_select['orderSn'] . "'";
            $delivery = $GLOBALS['db']->getRow($sql);
            $delivery['shipping_id'] = $shipping_info['shipping_id'];
            $delivery['shipping_name'] = $shipping_info['shipping_name'];
            /* 获取发货单号和流水号 */
            mt_srand((double)microtime() * 1000000);
            $delivery['delivery_sn'] = $this->timeRepository->getLocalDate('YmdHi') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            /* 获取当前操作员 */
            $delivery['action_user'] = $orderLang['conf_message']['action_user'];
            /* 获取发货单生成时间 */
            $delivery['update_time'] = GMTIME_UTC;
            /* 设置默认值 */
            $delivery['status'] = 2; // 正常
            /* 过滤字段项 */
            $filter_fileds = array(
                'order_sn', 'add_time', 'user_id', 'how_oos', 'shipping_id', 'shipping_fee',
                'consignee', 'address', 'country', 'province', 'city', 'district',
                'email', 'zipcode', 'tel', 'mobile', 'best_time', 'postscript', 'insure_fee',
                'agency_id', 'delivery_sn', 'action_user', 'update_time',
                'status', 'order_id', 'shipping_name'
            );
            $_delivery = array();
            foreach ($filter_fileds as $value) {
                $_delivery[$value] = $delivery[$value];
            }

            /* 发货单入库 */
            $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('delivery_order'), $_delivery, 'INSERT', '', 'SILENT');
            $delivery_id = $GLOBALS['db']->insert_id();

            //判断是否入库成功
            if ($delivery_id > 0) {
                //获取订单商品
                $sql = "SELECT o.goods_number,o.goods_id,o.product_id,o.goods_name,o.goods_sn, o.goods_attr,g.is_real, IFNULL(b.brand_name, '') AS brand_name, p.product_sn " .
                    "FROM " . $GLOBALS['dsc']->table('order_goods') . " AS o " .
                    "LEFT JOIN " . $GLOBALS['dsc']->table('products') . " AS p ON o.product_id = p.product_id " .
                    "LEFT JOIN " . $GLOBALS['dsc']->table('goods') . " AS g ON o.goods_id = g.goods_id " .
                    "LEFT JOIN " . $GLOBALS['dsc']->table('brand') . " AS b ON g.brand_id = b.brand_id " .
                    "LEFT JOIN " . $GLOBALS['dsc']->table('order_cloud') . " AS oc ON o.rec_id = oc.rec_id " .
                    "WHERE o.order_id = '" . $_delivery['order_id'] . "' AND oc.apiordersn = '" . $order_select['orderSn'] . "'";

                $goods_list = $GLOBALS['db']->getAll($sql);
                if (!empty($goods_list)) {
                    //分单操作
                    $split_action_note = "";
                    foreach ($goods_list as $key => $val) {
                        //处理货品id
                        $delivery_goods = array('delivery_id' => $delivery_id,
                            'goods_id' => $val['goods_id'],
                            'product_id' => empty($val['product_id']) ? 0 : $val['product_id'],
                            'product_sn' => $val['product_sn'],
                            'goods_id' => $val['goods_id'],
                            'goods_name' => addslashes($val['goods_name']),
                            'brand_name' => addslashes($val['brand_name']),
                            'goods_sn' => $val['goods_sn'],
                            'send_number' => $val['goods_number'],
                            'parent_id' => 0,
                            'is_real' => $val['is_real'],
                            'goods_attr' => addslashes($val['goods_attr'])
                        );
                        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('delivery_goods'), $delivery_goods, 'INSERT', '', 'SILENT');
                        //分单操作
                        $split_action_note .= sprintf($orderLang['conf_message']['split_action_note'], $val['goods_sn'], $val['goods_number']) . "<br/>";
                        //更新订单发货数量
                        $sql = "UPDATE " . $GLOBALS['dsc']->table('order_goods') . "
						SET send_number = '" . $val['goods_number'] . "'
						WHERE order_id = '" . $_delivery['order_id'] . "'
						AND goods_id = '" . $val['goods_id'] . "' ";
                        $GLOBALS['db']->query($sql);
                    }
                }
            } else {
                $common_data = array(
                    'code' => $orderLang['delivery_failure']['code'],
                    'message' => $orderLang['delivery_failure']['message'],
                    'format' => $format
                );

                common::common($common_data);
                $result = common::data_back('', 2);

                return $result;
            }
        } else {
            $common_data = array(
                'code' => $orderLang['data_null']['code'],
                'message' => $orderLang['data_null']['message'],
                'format' => $format
            );

            common::common($common_data);
            $result = common::data_back('', 2);

            return $result;
        }

        /* 定单信息更新处理 */

        /* 标记订单为已确认 “发货中” */
        /* 更新发货时间 */
        $sql = "SELECT COUNT(rec_id) FROM" . $GLOBALS['dsc']->table('order_goods') . "WHERE order_id = '" . $_delivery['order_id'] . "' AND goods_number >= send_number";
        $order_finish = $GLOBALS['db']->getOne($sql);
        $shipping_status = SS_SHIPPED_ING;
        $sql = "SELECT mobile,email,consignee,order_id,order_status,pay_status,insure_fee,pay_id,invoice_no,pay_time,order_sn,extension_id,extension_code,goods_amount,user_id FROM" . $GLOBALS['dsc']->table('order_info') . "WHERE order_id = '" . $_delivery['order_id'] . "'";
        $order = $GLOBALS['db']->getRow($sql);

        if ($order['order_status'] != OS_CONFIRMED && $order['order_status'] != OS_SPLITED && $order['order_status'] != OS_SPLITING_PART) {
            $arr['order_status'] = OS_CONFIRMED;
            $arr['confirm_time'] = GMTIME_UTC;
        }
        $arr['order_status'] = $order_finish ? OS_SPLITED : OS_SPLITING_PART; // 全部分单、部分分单
        $arr['shipping_status'] = $shipping_status;
        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('order_info'), $arr, 'UPDATE', "order_id = '" . $_delivery['order_id'] . "'");
        /* 分单操作 */
        $action_note = $split_action_note;

        /* 记录log */
        $sql = "INSERT INTO " . $GLOBALS['dsc']->table('order_action') .
            " (order_id, action_user, order_status, shipping_status, pay_status,  action_note, log_time) " .
            "VALUES ('" . $_delivery['order_id'] . "','" . $orderLang['conf_message']['action_user'] . "','" . $arr['order_status'] . "','$shipping_status','" . $order['pay_status'] . "','$action_note','" . gmtime() . "')";

        $GLOBALS['db']->query($sql);
        $order_id = $_delivery['order_id'];
        unset($_delivery);
        //发货
        //获取发货单信息
        $sql = "SELECT * FROM " . $GLOBALS['dsc']->table('delivery_order') . " WHERE delivery_id = '$delivery_id' LIMIT 0, 1";
        $delivery_order = $GLOBALS['db']->getRow($sql);
        if ($delivery_order) {
            $delivery_order['agency_name'] = ''; //办事处为空
            /* 格式化金额字段 */
            $delivery_order['formated_insure_fee'] = common::price_format($delivery_order['insure_fee'], false);
            $delivery_order['formated_shipping_fee'] = common::price_format($delivery_order['shipping_fee'], false);

            /* 格式化时间字段 */
            $delivery_order['formated_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $delivery_order['add_time']);
            $delivery_order['formated_update_time'] = local_date($GLOBALS['_CFG']['time_format'], $delivery_order['update_time']);
        }
        /* 取得用户名 */
        if ($delivery_order['user_id'] > 0) {
            $sql = "SELECT user_name FROM " . $GLOBALS['dsc']->table('users') .
                " WHERE user_id = '" . $delivery_order['user_id'] . "'";
            $delivery_order['user_name'] = $GLOBALS['db']->getOne($sql);
        }
        /* 取得区域名 */
        $sql = "SELECT concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''), " .
            "'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region " .
            "FROM " . $GLOBALS['dsc']->table('order_info') . " AS o " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('region') . " AS c ON o.country = c.region_id " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('region') . " AS p ON o.province = p.region_id " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('region') . " AS t ON o.city = t.region_id " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('region') . " AS d ON o.district = d.region_id " .
            "WHERE o.order_id = '" . $delivery_order['order_id'] . "'";
        $delivery_order['region'] = $GLOBALS['db']->getOne($sql);
        /* 是否保价 */
        $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;

        /* 修改发货单信息 */
        $_delivery['invoice_no'] = $invoice_no;
        $_delivery['status'] = 0; // 0，为已发货
        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('delivery_order'), $_delivery, 'UPDATE', "delivery_id = $delivery_id", 'SILENT');

        /* 标记订单为已确认 “已发货” */
        /* 更新发货时间 */
        $sql = "SELECT COUNT(delivery_id)
                FROM " . $GLOBALS['dsc']->table('delivery_order') . "
                WHERE order_id = '$order_id'
                AND status = 2 ";
        $sum = $GLOBALS['db']->getOne($sql);
        // 全部发货
        $order_finish = 0;
        if (empty($sum)) {
            $order_finish = 1;
        } // 未全部发货
        else {
            /* 订单全部发货中时：当前发货单总数 */
            $sql = "SELECT COUNT(delivery_id)
            FROM " . $GLOBALS['dsc']->table('delivery_order') . "
            WHERE order_id = '$order_id'
            AND status <> 1 ";
            $_sum = $GLOBALS['db']->getOne($sql);
            if ($_sum == $sum) {
                $order_finish = -2; // 完全没发货
            } else {
                $order_finish = -1; // 部分发货
            }
        }
        $shipping_status = ($order_finish == 1) ? SS_SHIPPED : SS_SHIPPED_PART;
        $arr['shipping_status'] = $shipping_status;
        $arr['shipping_time'] = GMTIME_UTC; // 发货时间
        $arr['invoice_no'] = $invoice_no;
        if (empty($order['pay_time'])) {
            $arr['pay_time'] = gmtime();
        }
        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('order_info'), $arr, 'UPDATE', "order_id = '$order_id'");

        /* 记录log */
        $sql = "INSERT INTO " . $GLOBALS['dsc']->table('order_action') .
            " (order_id, action_user, order_status, shipping_status, pay_status,  action_note, log_time) " .
            "VALUES ('$order_id','" . $orderLang['conf_message']['action_user'] . "','" . OS_CONFIRMED . "','$shipping_status','" . $order['pay_status'] . "','$action_note','" . gmtime() . "')";

        $GLOBALS['db']->query($sql);
        /* 如果当前订单已经全部发货 */
        if ($order_finish) {
            /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
            if ($order['user_id'] > 0) {
                /* 计算并发放积分 */
                $integral = common::integral_to_give($order);

                common::log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($orderLang['conf_message']['order_gift_integral'], $order['order_sn']));

                /* 发放红包 */
                common::send_order_bonus($order_id);

                /* 发放优惠券 bylu */
                common::send_order_coupons($order);
            }

            /* 发送邮件 */
            $cfg = $GLOBALS['_CFG']['send_ship_email'];
            if ($cfg == '1') {
                $order['invoice_no'] = $invoice_no;
                $sql = 'SELECT template_subject, is_html, template_content FROM ' . $GLOBALS['dsc']->table('mail_templates') . " WHERE template_code = 'deliver_notice'";
                $tpl = $GLOBALS['db']->GetRow($sql);
                $GLOBALS['smarty']->assign('order', $order);
                $GLOBALS['smarty']->assign('send_time', local_date($GLOBALS['_CFG']['time_format']));
                $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                $GLOBALS['smarty']->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                $GLOBALS['smarty']->assign('sent_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                $GLOBALS['smarty']->assign('confirm_url', $GLOBALS['dsc']->url() . 'user_order.php?act=order_detail&order_id=' . $order['order_id']); //by wu
                $GLOBALS['smarty']->assign('send_msg_url', $GLOBALS['dsc']->url() . 'user_message.php?act=message_list&order_id=' . $order['order_id']);
                $content = $GLOBALS['smarty']->fetch('str:' . $tpl['template_content']);
                app(CommonRepository::class)->sendEmail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);
            }

            /* 如果需要，发短信 */
            if ($GLOBALS['_CFG']['sms_order_shipped'] == '1' && $order['mobile'] != '') {

                //短信接口参数
                $sql = "SELECT shop_name FROM" . $GLOBALS['dsc']->table('seller_shopinfo') . " WHERE ru_id = 0";
                $shop_name = $GLOBALS['db']->getOne($sql);

                $sql = "SELECT user_name FROM" . $GLOBALS['dsc']->table('users') . "WHERE user_id = '" . $order['user_id'] . "'";
                $user_name = $GLOBALS['db']->getOne($sql);
                $smsParams = array(
                    'shop_name' => $shop_name,
                    'shopname' => $shop_name,
                    'user_name' => $user_name,
                    'username' => $user_name,
                    'consignee' => $order['consignee'],
                    'order_sn' => $order['order_sn'],
                    'ordersn' => $order['order_sn'],
                    'invoice_no' => $invoice_no,
                    'invoiceno' => $invoice_no,
                    'mobile_phone' => $order['mobile'],
                    'mobilephone' => $order['mobile']
                );

                $this->commonRepository->smsSend($order['mobile'], $smsParams, 'sms_order_shipped');
            }
        }
        $common_data = array(
            'code' => $orderLang['msg_success']['code'],
            'message' => $orderLang['msg_success']['message'],
            'format' => $format
        );

        common::common($common_data);
        $result = common::data_back('', 2);

        return $result;
    }
}
