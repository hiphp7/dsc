<?php

use App\Repositories\Common\DscRepository;
use App\Services\Order\OrderService as Order;
use App\Services\User\UserAddressService;

/**
 * 获得指定分类下的子分类的数组
 *
 * @access  public
 * @param int $cat_id 分类的ID
 * @param int $selected 当前选中分类的ID
 * @param boolean $re_type 返回的类型: 值为真时返回下拉列表,否则返回数组
 * @param int $level 限定返回的级数。为0时返回所有级数
 * @param int $is_show_all 如果为true显示所有分类，如果为false隐藏不可见分类。
 * @return  mix
 */
function wholesale_cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0, $is_show_all = true)
{
    static $res = null;

    if ($res === null) {
        $data = read_static_cache('wholesale_cat_releate');
        if ($data === false) {
            $sql = "SELECT w.cat_id, w.cat_name,w.is_show, w.parent_id, w.sort_order, COUNT(s.parent_id) AS has_children " .
                'FROM ' . $GLOBALS['dsc']->table('wholesale_cat') . " AS w " .
                "LEFT JOIN " . $GLOBALS['dsc']->table('wholesale_cat') . " AS s ON s.parent_id = w.cat_id " .
                "GROUP BY w.cat_id " .
                'ORDER BY w.parent_id, w.sort_order ASC';
            $res = $GLOBALS['db']->getAll($sql);

            //如果数组过大，不采用静态缓存方式
            if (count($res) <= 1000) {
                write_static_cache('wholesale_cat_releate', $res);
            }
        } else {
            $res = $data;
        }
    }

    if (empty($res) == true) {
        return $re_type ? '' : array();
    }

    $options = wholesale_cat_options($cat_id, $res); // 获得指定分类下的子分类的数组

    $children_level = 99999; //大于这个分类的将被删除
    if ($is_show_all == false) {
        foreach ($options as $key => $val) {
            if ($val['level'] > $children_level) {
                unset($options[$key]);
            } else {
                if ($val['is_show'] == 0) {
                    unset($options[$key]);
                    if ($children_level > $val['level']) {
                        $children_level = $val['level']; //标记一下，这样子分类也能删除
                    }
                } else {
                    $children_level = 99999; //恢复初始值
                }
            }
        }
    }

    /* 截取到指定的缩减级别 */
    if ($level > 0) {
        if ($cat_id == 0) {
            $end_level = $level;
        } else {
            $first_item = reset($options); // 获取第一个元素
            $end_level = $first_item['level'] + $level;
        }

        /* 保留level小于end_level的部分 */
        foreach ($options as $key => $val) {
            if ($val['level'] >= $end_level) {
                unset($options[$key]);
            }
        }
    }

    if ($re_type == true) {
        $select = '';
        foreach ($options as $var) {
            $select .= '<option value="' . $var['cat_id'] . '" ';
            $select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
            $select .= '>';
            if ($var['level'] > 0) {
                $select .= str_repeat('&nbsp;', $var['level'] * 4);
            }
            $select .= htmlspecialchars(addslashes($var['cat_name']), ENT_QUOTES) . '</option>';
        }

        return $select;
    } else {
        return $options;
    }
}

/**
 * 过滤和排序所有分类，返回一个带有缩进级别的数组
 *
 * @access  private
 * @param int $cat_id 上级分类ID
 * @param array $arr 含有所有分类的数组
 * @param int $level 级别
 * @return  void
 */
function wholesale_cat_options($spec_cat_id, $arr)
{
    static $cat_options = array();

    if (isset($cat_options[$spec_cat_id])) {
        return $cat_options[$spec_cat_id];
    }

    if (!isset($cat_options[0])) {
        $level = $last_cat_id = 0;
        $options = $cat_id_array = $level_array = array();
        $data = read_static_cache('wholesale_cat_option_static');
        if ($data === false) {
            while (!empty($arr)) {
                foreach ($arr as $key => $value) {
                    $cat_id = $value['cat_id'];
                    if ($level == 0 && $last_cat_id == 0) {
                        if ($value['parent_id'] > 0) {
                            break;
                        }

                        $options[$cat_id] = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id'] = $cat_id;
                        $options[$cat_id]['name'] = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] == 0) {
                            continue;
                        }
                        $last_cat_id = $cat_id;
                        $cat_id_array = array($cat_id);
                        $level_array[$last_cat_id] = ++$level;
                        continue;
                    }

                    if ($value['parent_id'] == $last_cat_id) {
                        $options[$cat_id] = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id'] = $cat_id;
                        $options[$cat_id]['name'] = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] > 0) {
                            if (end($cat_id_array) != $last_cat_id) {
                                $cat_id_array[] = $last_cat_id;
                            }
                            $last_cat_id = $cat_id;
                            $cat_id_array[] = $cat_id;
                            $level_array[$last_cat_id] = ++$level;
                        }
                    } elseif ($value['parent_id'] > $last_cat_id) {
                        break;
                    }
                }

                $count = count($cat_id_array);
                if ($count > 1) {
                    $last_cat_id = array_pop($cat_id_array);
                } elseif ($count == 1) {
                    if ($last_cat_id != end($cat_id_array)) {
                        $last_cat_id = end($cat_id_array);
                    } else {
                        $level = 0;
                        $last_cat_id = 0;
                        $cat_id_array = array();
                        continue;
                    }
                }

                if ($last_cat_id && isset($level_array[$last_cat_id])) {
                    $level = $level_array[$last_cat_id];
                } else {
                    $level = 0;
                }
            }
            //如果数组过大，不采用静态缓存方式
            if (count($options) <= 2000) {
                write_static_cache('wholesale_cat_option_static', $options);
            }
        } else {
            $options = $data;
        }
        $cat_options[0] = $options;
    } else {
        $options = $cat_options[0];
    }

    if (!$spec_cat_id) {
        return $options;
    } else {
        if (empty($options[$spec_cat_id])) {
            return array();
        }

        $spec_cat_id_level = $options[$spec_cat_id]['level'];

        foreach ($options as $key => $value) {
            if ($key != $spec_cat_id) {
                unset($options[$key]);
            } else {
                break;
            }
        }

        $spec_cat_id_array = array();
        foreach ($options as $key => $value) {
            if (($spec_cat_id_level == $value['level'] && $value['cat_id'] != $spec_cat_id) ||
                ($spec_cat_id_level > $value['level'])) {
                break;
            } else {
                $spec_cat_id_array[$key] = $value;
            }
        }
        $cat_options[$spec_cat_id] = $spec_cat_id_array;

        return $spec_cat_id_array;
    }
}

/**
 * 计算退款金额
 * by Leah  by kong
 * @param type $order_id
 * @param type $rec_id
 * @param type $num
 * @return type
 */
function get_wholesale_return_refound($order_id = 0, $rec_id, $num)
{
    $sql = "SELECT goods_number, goods_price, (goods_number * goods_price) AS goods_amount FROM " . $GLOBALS['dsc']->table('wholesale_order_goods') . " WHERE rec_id = '$rec_id'";
    $res = $GLOBALS['db']->getRow($sql);

    if ($res && $num > $res['goods_number'] || empty($num)) {
        $num = $res['goods_number'];
    }

    $return_price = $num * $res['goods_price'];
    $return_shipping_fee = 0;

    if ($return_price > 0) {
        $return_price = number_format($return_price, 2, '.', '');
    }

    if ($return_shipping_fee > 0) {
        $return_shipping_fee = number_format($return_shipping_fee, 2, '.', '');
    }

    $arr = array(
        'return_price' => $return_price,
        'return_shipping_fee' => $return_shipping_fee
    );

    return $arr;
}

/**
 * 获得退换货商品
 * by  Leah
 */
function get_wholesale_return_goods($ret_id)
{
    $ret_id = intval($ret_id);
    $sql = "SELECT rg.*, g.goods_thumb, g.brand_id FROM " . $GLOBALS['dsc']->table('wholesale_return_goods') .
        " as rg  LEFT JOIN " . $GLOBALS['dsc']->table('wholesale_order_return') . "as r ON rg.rec_id = r.rec_id " .
        " LEFT JOIN " . $GLOBALS['dsc']->table('wholesale') . " AS g ON g.goods_id = rg.goods_id " .
        " WHERE r.ret_id = " . $ret_id;

    $res = $GLOBALS['db']->getAll($sql);

    $goods_list = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $row['refound'] = price_format($row['refound'], false);

            $brand = get_goods_brand_info($row['brand_id']);
            $row['brand_name'] = $brand['brand_name'];

            //图片显示
            $row['goods_thumb'] = get_image_path($row['goods_thumb']);

            $goods_list[] = $row;
        }
    }

    return $goods_list;
}

/**
 * 获取退换车订单是否的字符集
 */
function get_wholesale_order_return_rec($order_id)
{
    $sql = " SELECT GROUP_CONCAT(rec_id) AS rec_id FROM " . $GLOBALS['dsc']->table('wholesale_order_goods') . " WHERE order_id = '$order_id'";
    $rec_list = $GLOBALS['db']->getOne($sql);
    $rec_list = !empty($rec_list) ? explode(",", $rec_list) : array();

    $sql = " SELECT GROUP_CONCAT(rec_id) AS rec_id FROM " . $GLOBALS['dsc']->table('wholesale_order_return') . " WHERE order_id = '$order_id'";
    $return_goods = $GLOBALS['db']->getOne($sql);
    $return_goods = !empty($return_goods) ? explode(",", $return_goods) : array();

    $is_diff = false;
    if (!array_diff($rec_list, $return_goods)) {
        $is_diff = true;
    }

    return $is_diff;
}

/**
 * 相同商品退换货单 by leah
 * @param type $ret_id
 * @param type $order_sn
 */
function wholesale_return_order_info_byId($order_id, $refound = true)
{
    if (!$refound) {
        //获得唯一一个订单下 申请了全部退换货的退换货订单
        $sql = " SELECT count(*) FROM " . $GLOBALS['dsc']->table('wholesale_order_return') . " WHERE order_id = '$order_id' AND refound_status = 0";
        $res = $GLOBALS['db']->getOne($sql);
    } else {
        $sql = " SELECT * FROM " . $GLOBALS['dsc']->table('wholesale_order_return') . " WHERE order_id = '$order_id'";
        $res = $GLOBALS['db']->getAll($sql);
    }


    return $res;
}

/**
 * 查询订单退换货已退运费金额
 */
function wholesale_order_refound_fee($order_id = 0, $ret_id = 0)
{
    $where = "";
    if ($ret_id > 0) {
        $where = " AND ret_id <> '$ret_id'";
    }

    $sql = "SELECT SUM(actual_return) AS actual_return FROM " . $GLOBALS['dsc']->table('wholesale_order_return') . " WHERE order_id = '$order_id' " .
        " AND refund_type " . db_create_in('1,3') . " AND refound_status = 1 " . $where;
    $price = $GLOBALS['db']->getOne($sql);

    return $price;
}

/**
 * 取的订单上商品中的某一商品
 * by　Leah
 * @param type $rec_id
 */
function get_wholesale_return_order_goods1($rec_id)
{
    $sql = "select * FROM " . $GLOBALS['dsc']->table('wholesale_order_goods') . " WHERE rec_id =" . $rec_id;
    $goods_list = $GLOBALS['db']->getRow($sql);

    return $goods_list;
}

/**
 * 订单单品退款
 * @param array $order 订单
 * @param int $refund_type 退款方式 1 到帐户余额 2 到退款申请（先到余额，再申请提款） 3 不处理
 * @param string $refund_note 退款说明
 * @param float $refund_amount 退款金额（如果为0，取订单已付款金额）
 * @return  bool
 */
function wholesale_order_refound($order, $refund_type, $refund_note, $refund_amount = 0, $operation = '')
{
    /* 检查参数 */
    $user_id = $order['user_id'];
    if ($user_id == 0 && $refund_type == 1) {
        die('anonymous, cannot return to account balance');
    }

    $in_operation = array('refound');
    if (in_array($operation, $in_operation)) {
        $amount = $refund_amount;
    } else {
        $amount = $refund_amount > 0 ? $refund_amount : $order['should_return'];
    }

    if ($amount <= 0) {
        return 1;
    }

    if (!in_array($refund_type, array(1, 2, 3, 5))) { //5:白条退款 bylu;
        die('invalid params');
    }

    /* 备注信息 */
    if ($refund_note) {
        $change_desc = $refund_note;
    } else {
        $change_desc = sprintf(lang('suppliers/order.order_refund'), $order['order_sn']);
    }

    /* 处理退款 */
    if (1 == $refund_type) {
        /* 如果非匿名，退回余额 */
        if ($user_id > 0) {
            $is_ok = 1;
            if ($order['suppliers_id']) {
                $suppliers_info = get_suppliers_info($order['suppliers_id'], array('user_id', 'suppliers_name', 'suppliers_money'));

                if ($suppliers_info) {
                    $adminru = get_admin_ru_id();

                    $change_desc = "操作员：【" . $adminru['user_name'] . "】，订单退款【" . $order['order_sn'] . "】" . $refund_note;
                    $log = array(
                        'user_id' => $order['suppliers_id'],
                        //'user_money' => (-1) * $amount,
                        'user_money' => 0,
                        'change_time' => gmtime(),
                        'change_desc' => $change_desc,
                        'change_type' => 2
                    );
                    /*$GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('suppliers_account_log'), $log, 'INSERT');

                    $sql = "UPDATE " . $GLOBALS['dsc']->table('suppliers') . " SET suppliers_money = suppliers_money + '" . $log['user_money'] . "' WHERE suppliers_id = '" . $order['suppliers_id'] . "'";
                    $GLOBALS['db']->query($sql);*/
                } else {
                    $is_ok = 0;
                }
            }

            if ($is_ok == 1) {
                log_account_change($user_id, $amount, 0, 0, 0, $change_desc);
            } else {
                /* 返回失败，不允许退款 */
                return 2;
            }
        }

        return 1;
    } elseif (2 == $refund_type) {
        return true;
    } elseif (22222 == $refund_type) {
        /* 如果非匿名，退回余额 */
        if ($user_id > 0) {
            log_account_change($user_id, $amount, 0, 0, 0, $change_desc);
        }

        /* user_account 表增加提款申请记录 */
        $account = array(
            'user_id' => $user_id,
            'amount' => (-1) * $amount,
            'add_time' => gmtime(),
            'user_note' => $refund_note,
            'process_type' => SURPLUS_RETURN,
            'admin_user' => session('admin_name'),
            'admin_note' => sprintf(lang('suppliers/order.order_refund'), $order['order_sn']),
            'is_paid' => 0
        );

        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('user_account'), $account, 'INSERT');

        return 1;
    } /*  @bylu 白条退款 start */
    elseif (5 == $refund_type) {

        //查询当前退款订单使用了多少余额支付;
        $surplus = $GLOBALS['db']->getOne('SELECT surplus FROM' . $GLOBALS['dsc']->table('order_info') . 'WHERE order_id=' . $order['order_id']);

        //余额退余额,白条退白条;
        if ($surplus != 0.00) {
            log_account_change($user_id, $surplus, 0, 0, 0, lang('baitiao.baitiao') . $change_desc);
        } else {
            $baitiao_info = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['dsc']->table('baitiao_log') . "
              WHERE order_id='" . $order['order_id'] . "'");

            if ($baitiao_info['is_stages'] == 1) {
                $surplus = $baitiao_info['yes_num'] * $baitiao_info['stages_one_price'];
                log_account_change($user_id, $surplus, 0, 0, 0, lang('baitiao.baitiao_stages') . $change_desc);
            } else {
                $surplus = $order['order_amount'];
                log_account_change($user_id, $surplus, 0, 0, 0, lang('baitiao.baitiao') . $change_desc);
            }
        }

        //将当前退款订单的白条记录表中的退款信息变更为"退款";
        $sql = "update {$GLOBALS['dsc']->table('baitiao_log')} set is_refund=1 where order_id='{$order['order_id']}'";
        $GLOBALS['db']->query($sql);


        return 1;
    } /*  @bylu 白条退款 end */
    else {
        return 1;
    }
}

/**
 * 退换货  by  Leah
 * @return type
 */
function wholesale_return_order_list()
{
    $result = get_filter();

    $adminru = get_admin_ru_id();

    if ($result === false) {
        /* 过滤信息 */
        $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
        if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
            $_REQUEST['consignee'] = json_str_iconv($_REQUEST['consignee']);
        }
        $filter['return_sn'] = isset($_REQUEST['return_sn']) ? trim($_REQUEST['return_sn']) : '';
        $filter['order_id'] = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
        $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);
        $filter['email'] = empty($_REQUEST['email']) ? '' : trim($_REQUEST['email']);
        $filter['address'] = empty($_REQUEST['address']) ? '' : trim($_REQUEST['address']);
        $filter['zipcode'] = empty($_REQUEST['zipcode']) ? '' : trim($_REQUEST['zipcode']);
        $filter['tel'] = empty($_REQUEST['tel']) ? '' : trim($_REQUEST['tel']);
        $filter['mobile'] = empty($_REQUEST['mobile']) ? 0 : intval($_REQUEST['mobile']);
        $filter['shipping_id'] = empty($_REQUEST['shipping_id']) ? 0 : intval($_REQUEST['shipping_id']);
        $filter['pay_id'] = empty($_REQUEST['pay_id']) ? 0 : intval($_REQUEST['pay_id']);
        $filter['order_status'] = isset($_REQUEST['order_status']) ? intval($_REQUEST['order_status']) : -1;
        $filter['shipping_status'] = isset($_REQUEST['shipping_status']) ? intval($_REQUEST['shipping_status']) : -1;
        $filter['pay_status'] = isset($_REQUEST['pay_status']) ? intval($_REQUEST['pay_status']) : -1;
        $filter['user_id'] = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
        $filter['user_name'] = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);
        $filter['composite_status'] = isset($_REQUEST['composite_status']) ? intval($_REQUEST['composite_status']) : -1;
        $filter['group_buy_id'] = isset($_REQUEST['group_buy_id']) ? intval($_REQUEST['group_buy_id']) : 0;
        $filter['return_type'] = isset($_REQUEST['return_type']) ? intval($_REQUEST['return_type']) : -1;
        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'ret_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['order_referer'] = isset($_REQUEST['order_referer']) ? trim($_REQUEST['order_referer']) : '';

        $filter['start_time'] = empty($_REQUEST['start_time']) ? '' : (strpos($_REQUEST['start_time'], '-') > 0 ? local_strtotime($_REQUEST['start_time']) : $_REQUEST['start_time']);
        $filter['end_time'] = empty($_REQUEST['end_time']) ? '' : (strpos($_REQUEST['end_time'], '-') > 0 ? local_strtotime($_REQUEST['end_time']) : $_REQUEST['end_time']);

        $where = 'WHERE 1 ';

        if ($adminru['suppliers_id'] > 0) {
            $where .= " AND o.suppliers_id = '" . $adminru['suppliers_id'] . "' ";
        }

        if ($filter['order_id']) {
            $where .= " AND o.order_id = '" . $filter['order_id'] . "'";
        }

        if ($filter['return_sn']) {
            $where .= " AND r.return_sn LIKE '%" . mysql_like_quote($filter['return_sn']) . "%'";
        }

        if ($filter['order_sn']) {
            $where .= " AND o.order_sn LIKE '%" . mysql_like_quote($filter['order_sn']) . "%'";
        }

        if ($filter['consignee']) {
            $where .= " AND o.consignee LIKE '%" . mysql_like_quote($filter['consignee']) . "%'";
        }
        if ($filter['email']) {
            $where .= " AND o.email LIKE '%" . mysql_like_quote($filter['email']) . "%'";
        }
        if ($filter['address']) {
            $where .= " AND o.address LIKE '%" . mysql_like_quote($filter['address']) . "%'";
        }
        if ($filter['zipcode']) {
            $where .= " AND o.zipcode LIKE '%" . mysql_like_quote($filter['zipcode']) . "%'";
        }
        if ($filter['tel']) {
            $where .= " AND o.tel LIKE '%" . mysql_like_quote($filter['tel']) . "%'";
        }
        if ($filter['mobile']) {
            $where .= " AND o.mobile LIKE '%" . mysql_like_quote($filter['mobile']) . "%'";
        }
        if ($filter['shipping_id']) {
            $where .= " AND o.shipping_id  = '$filter[shipping_id]'";
        }
        if ($filter['pay_id']) {
            $where .= " AND o.pay_id  = '$filter[pay_id]'";
        }
        if ($filter['order_status'] != -1) {
            $where .= " AND o.order_status  = '$filter[order_status]'";
        }
        if ($filter['shipping_status'] != -1) {
            $where .= " AND o.shipping_status = '$filter[shipping_status]'";
        }
        if ($filter['pay_status'] != -1) {
            $where .= " AND o.pay_status = '$filter[pay_status]'";
        }
        if ($filter['user_id']) {
            $where .= " AND o.user_id = '$filter[user_id]'";
        }
        if ($filter['user_name']) {
            $where .= " AND u.user_name LIKE '%" . mysql_like_quote($filter['user_name']) . "%'";
        }
        if ($filter['start_time']) {
            $where .= " AND o.add_time >= '$filter[start_time]'";
        }
        if ($filter['end_time']) {
            $where .= " AND o.add_time <= '$filter[end_time]'";
        }

        if ($filter['return_type'] != -1) {
            if (in_array($filter['return_type'], array(1, 3))) {
                $where .= " AND r.return_type IN(1, 3)";
            }
        }

        //综合状态
        switch ($filter['composite_status']) {
            case CS_AWAIT_PAY:
                $where .= app(Order::class)->orderQuerySql('await_pay');
                break;

            case CS_AWAIT_SHIP:
                $where .= app(Order::class)->orderQuerySql('await_ship');
                break;

            case CS_FINISHED:
                $where .= app(Order::class)->orderQuerySql('finished');
                break;

            case PS_PAYING:
                if ($filter['composite_status'] != -1) {
                    $where .= " AND o.pay_status = '$filter[composite_status]' ";
                }
                break;
            case OS_SHIPPED_PART:
                if ($filter['composite_status'] != -1) {
                    $where .= " AND o.shipping_status  = '$filter[composite_status]'-2 ";
                }
                break;
            case CS_ORDER_BACK:
                if ($filter['composite_status'] != -1) {
                    $where .= " AND is_check = '0' AND agree_apply = '0' AND return_status = '0' AND refound_status = '0' AND return_type NOT IN('0') ";
                }
                break;
            default:
                if ($filter['composite_status'] != -1) {
                    $where .= " AND o.order_status = '$filter[composite_status]' ";
                }
        }

        /* 团购订单 */
        if ($filter['group_buy_id']) {
            $where .= " AND o.extension_code = 'group_buy' AND o.extension_id = '$filter[group_buy_id]' ";
        }

        /* 如果管理员属于某个办事处，只列出这个办事处管辖的订单 */
        $sql = "SELECT agency_id FROM " . $GLOBALS['dsc']->table('admin_user') . " WHERE user_id = '" . session('admin_id') . "'";
        $agency_id = $GLOBALS['db']->getOne($sql);
        if ($agency_id > 0) {
            $where .= " AND o.agency_id = '$agency_id' ";
        }

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        $page_size = request()->cookie('dsccp_page_size');
        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (intval($page_size) > 0) {
            $filter['page_size'] = intval($page_size);
        } else {
            $filter['page_size'] = 15;
        }

        $where_store = '';

        /* 记录总数 */
        if ($filter['user_name']) {
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('wholesale_order_return') . " AS o ," .
                $GLOBALS['dsc']->table('users') . " AS u " . $where . $where_store;
        } else {
            if ($filter['order_referer']) {
                if ($filter['order_referer'] == 'pc') {
                    $where .= " AND o.referer NOT IN ('mobile','touch','ecjia-cashdesk') ";
                } else {
                    $where .= " AND o.referer = '$filter[order_referer]' ";
                }
            }

            $sql = "SELECT COUNT(DISTINCT ret_id) FROM " . $GLOBALS['dsc']->table('wholesale_order_return') . " AS r, " . $GLOBALS['dsc']->table('wholesale_order_info') . " as o " . $where . " AND r.order_id = o.order_id";
        }

        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT DISTINCT r.ret_id, o.suppliers_id, o.order_id, o.order_sn, o.add_time, o.order_status, o.shipping_status, o.order_amount, " .
            "o.pay_status, o.consignee, o.email, o.tel, o.extension_code, " .
            " r.rec_id, r.address , r.back , r.exchange ,r.attr_val , r.cause_id , r.apply_time , r.should_return , r.actual_return , r.remark , r.address , r.return_status , r.refound_status , " .
            " r.return_type, r.addressee, r.phone, r.return_sn, r.refund_type, r.return_shipping_fee, " .
            " o.order_amount AS total_fee, " .
            "IFNULL(u.user_name, '" . $GLOBALS['_LANG']['anonymous'] . "') AS buyer " .
            "FROM " . $GLOBALS['dsc']->table('wholesale_order_return') . " AS r " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('wholesale_order_info') . " AS o ON r.order_id = o.order_id " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('users') . " AS u ON u.user_id=o.user_id  " . $where . $where_store .
            " ORDER BY $filter[sort_by] $filter[sort_order] " .
            " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";

        foreach (array('order_sn', 'consignee', 'email', 'address', 'zipcode', 'tel', 'user_name') as $val) {
            $filter[$val] = stripslashes($filter[$val]);
        }

        set_filter($filter, $sql);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }

    $row = $GLOBALS['db']->getAll($sql);

    /* 格式话数据 */
    if ($row) {
        foreach ($row as $key => $value) {
            $row[$key]['return_pay_status'] = $value['refound_status'];

            $row[$key]['formated_order_amount'] = price_format($value['order_amount']);
            $row[$key]['formated_total_fee'] = price_format($value['total_fee']);
            $row[$key]['short_order_time'] = local_date('m-d H:i', $value['add_time']);
            $row[$key]['apply_time'] = local_date('m-d H:i', $value['apply_time']);
            $suppliers_info = get_suppliers_info($value['suppliers_id'], array('user_id', 'suppliers_name'));
            $row[$key]['suppliers_name'] = $suppliers_info['suppliers_name'];

            $row[$key]['discount_amount'] = number_format($value['should_return'], 2, '.', ''); //折扣金额
            $row[$key]['formated_discount_amount'] = price_format($row[$key]['discount_amount']);
            $row[$key]['formated_should_return'] = price_format($value['should_return'] - $row[$key]['discount_amount']);

            $sql = "SELECT return_number, refound FROM " . $GLOBALS['dsc']->table('wholesale_return_goods') . " WHERE rec_id = '" . $value['rec_id'] . "' LIMIT 1";
            $return_goods = $GLOBALS['db']->getRow($sql);

            if ($return_goods) {
                $return_number = $return_goods['return_number'];
            } else {
                $return_number = 0;
            }

            $row[$key]['return_number'] = $return_number;
            $row[$key]['address_detail'] = app(UserAddressService::class)->getUserRegionAddress($value['ret_id'], '', 2);

            if ($value['order_status'] == OS_INVALID || $value['order_status'] == OS_CANCELED) {
                /* 如果该订单为无效或取消则显示删除链接 */
                $row[$key]['can_remove'] = 1;
            } else {
                $row[$key]['can_remove'] = 0;
            }

            if ($value['return_type'] == 0) {
                if ($value['return_status'] == 4) {
                    $row[$key]['refound_status'] = FF_MAINTENANCE;
                } else {
                    $row[$key]['refound_status'] = FF_NOMAINTENANCE;
                }
            } elseif ($value['return_type'] == 1 || $value['return_type'] == 3) {
                if ($value['refound_status'] == 1) {
                    $row[$key]['refound_status'] = FF_REFOUND;
                } else {
                    $row[$key]['refound_status'] = FF_NOREFOUND;
                }
            } elseif ($value['return_type'] == 2) {
                if ($value['return_status'] == 4) {
                    $row[$key]['refound_status'] = FF_EXCHANGE;
                } else {
                    $row[$key]['refound_status'] = FF_NOEXCHANGE;
                }
            }

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $row[$key]['phone'] = app(DscRepository::class)->stringToStar($value['phone']);
                $row[$key]['buyer'] = app(DscRepository::class)->stringToStar($value['buyer']);
                $row[$key]['tel'] = app(DscRepository::class)->stringToStar($value['tel']);
                $row[$key]['email'] = app(DscRepository::class)->stringToStar($value['email']);
            }
        }
    }

    $arr = array('orders' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

/**
 * 取得订单商品
 * @param int $order_id 订单id
 * @return  array   订单商品数组
 */
function wholesale_order_goods($order_id)
{
    $sql = "SELECT og.*, (og.goods_price * og.goods_number) AS subtotal,og.goods_price, g.is_shipping, g.goods_weight AS goodsweight, g.goods_img, g.goods_thumb, " .
        "g.goods_cause, g.is_shipping FROM " . $GLOBALS['dsc']->table('wholesale_order_goods') . " AS og " .
        "LEFT JOIN " . $GLOBALS['dsc']->table('wholesale') . " AS g ON og.goods_id = g.goods_id " .
        " WHERE og.order_id = '$order_id'";

    $res = $GLOBALS['db']->query($sql);

    $goods_list = array();

    if ($res) {
        foreach ($res as $key => $row) {
            $order_info = get_table_date('wholesale_order_info', "order_id='$order_id'", array('suppliers_id'));
            $suppliers_info = get_suppliers_info($order_info['suppliers_id'], array('user_id', 'suppliers_name'));
            $row['suppliers_name'] = $suppliers_info['suppliers_name'];

            $row['url'] = app(DscRepository::class)->buildUri('wholesale_goods', array('aid' => $row['goods_id']), $row['goods_name']);
            $row['goods_img'] = get_image_path($row['goods_img']);
            $row['goods_thumb'] = get_image_path($row['goods_thumb']);

            $goods_list[] = $row;
        }
    }


    return $goods_list;
}

/**
 * by Leah
 * @param type $rec_id
 * @return intb
 */
function wholesale_get_is_refound($rec_id)
{
    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('wholesale_order_return') . " WHERE rec_id=" . $rec_id;
    $is_refound = 0;
    if ($GLOBALS['db']->getOne($sql)) {
        $is_refound = 1;
    }

    return $is_refound;
}

/**
 * 获得指定的商品属性
 *
 * @access      public
 * @param array $arr 规格、属性ID数组
 * @param type $type 设置返回结果类型：pice，显示价格，默认；no，不显示价格
 *
 * @return      string
 */
function get_wholesale_goods_attr_info_new($arr, $type = 'pice', $warehouse_id = 0, $area_id = 0)
{
    $attr = '';
    if (!empty($arr)) {
        $fmt = "%s:%s[%s] \n";

        $leftJoin = " left join " . $GLOBALS['dsc']->table('wholesale') . " as g on g.goods_id = ga.goods_id";
        $sql = "SELECT ga.goods_attr_id, a.attr_name, ga.attr_value, ga.attr_price " .
            "FROM " . $GLOBALS['dsc']->table('wholesale_goods_attr') . " AS ga " .
            $leftJoin .
            " left join " . $GLOBALS['dsc']->table('attribute') . " AS a " . "on a.attr_id = ga.attr_id " .
            "WHERE " . db_create_in($arr, 'ga.goods_attr_id') . " ORDER BY a.sort_order, a.attr_id, ga.goods_attr_id";

        $res = $GLOBALS['db']->getAll($sql);

        if ($res) {
            foreach ($res as $key => $row) {
                $attr_price = round(floatval($row['attr_price']), 2);

                $attr_price = price_format($attr_price, false);

                $attr .= sprintf($fmt, $row['attr_name'], $row['attr_value'], $attr_price);
            }
        }

        $attr = str_replace('[0]', '', $attr);
    }

    return $attr;
}

/**
 * 确认一个用户换货订单
 *
 * @access  public
 * @param int $order_id 订单ID
 * @param int $user_id 用户ID
 *
 * @return  bool        $bool
 */
function wholesale_affirm_return_received($order_id, $user_id = 0)
{
    /* 查询订单信息，检查状态 */
    $sql = "SELECT user_id, return_sn , return_status, order_sn FROM " . $GLOBALS['dsc']->table('wholesale_order_return') . " WHERE order_id = '$order_id'";

    $return_order = $GLOBALS['db']->GetRow($sql);

    // 如果用户ID大于 0 。检查订单是否属于该用户
    if ($user_id > 0 && $return_order['user_id'] != $user_id) {
        $GLOBALS['err']->add($GLOBALS['_LANG']['no_priv']);

        return false;
    } /* 检查订单 */
    elseif ($return_order['return_status'] == 4) {
        $GLOBALS['err']->add(lang('order.order_confirm_receipt'));

        return false;
    } /* 修改订单发货状态为"确认收货" */
    else {
        $sql = "UPDATE " . $GLOBALS['dsc']->table('wholesale_order_return') . " SET return_status = '" . 4 . "' WHERE order_id = '$order_id'";
        if ($GLOBALS['db']->query($sql)) {
            /* 记录日志 */
            order_action($return_order['return_sn'], $return_order['return_status'], SS_RECEIVED, '', '', $GLOBALS['_LANG']['buyer']);

            return true;
        } else {
            die($GLOBALS['db']->errorMsg());
        }
    }
}

/**
 * 获取退款后的订单状态数组 by kong
 * $goods_number_return   类型：退换货商品数量
 * $rec_id   类型：退换货订单中的rec_id
 * $order_goods    类型：订单商品
 * $order_info    类型：订单详情
 */
function get_wholesale_order_arr($goods_number_return = 0, $rec_id = 0, $order_goods = array(), $order_info = array())
{
    $goods_number = 0;
    $goods_count = count($order_goods);
    $i = 1;
    foreach ($order_goods as $k => $v) {
        if ($rec_id == $v['rec_id']) {
            $goods_number = $v['goods_number'];
        }
        $sql = "SELECT ret_id FROM" . $GLOBALS['dsc']->table('wholesale_order_return') . " WHERE rec_id = '" . $v['rec_id'] . "' AND order_id = '" . $v['order_id'] . "' AND refound_status = 1";
        if ($GLOBALS['db']->getOne($sql) > 0) {
            $i++;
        }
    }
    if ($goods_number > $goods_number_return || $goods_count > $i) {
        //单品退货
        $arr = array(
            'order_status' => OS_RETURNED_PART
        );
    } else {
        //整单退货
        $arr = array(
            'order_status' => OS_RETURNED,
            'pay_status' => PS_UNPAYED,
            'shipping_status' => SS_UNSHIPPED,
            //'money_paid' => 0,
            'invoice_no' => '',
            'order_amount' => 0
        );
    }
    return $arr;
}
