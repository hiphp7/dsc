<?php

use App\Libraries\Image;
use App\Services\Order\OrderService as Order;
use App\Repositories\Common\DscRepository;
use App\Services\User\UserAddressService;
use App\Services\Common\CommonManageService;

/**
 * 供货商列表信息
 *
 * @param       string $conditions
 * @return      array
 */
function suppliers_list_info($conditions = '')
{
    $where = '';
    if (!empty($conditions)) {
        $where .= 'WHERE ';
        $where .= $conditions;
    }

    /* 查询 */
    $sql = "SELECT suppliers_id, suppliers_name, suppliers_desc
            FROM " . $GLOBALS['dsc']->table("suppliers") . "
            $where";

    return $GLOBALS['db']->getAll($sql);
}

/**
 * 设置管理员的session内容
 *
 * @access  public
 * @param   integer $user_id 管理员编号
 * @param   string $username 管理员姓名
 * @param   string $action_list 权限列表
 * @param   string $last_time 最后登录时间
 * @return  void
 */
function set_admin_session($user_id, $username, $action_list, $last_time)
{
    session([
        'supply_id' => $user_id,
        'supply_name' => $username,
        'supply_action_list' => $action_list,
        'supply_last_check' => $last_time, // 用于保存最后一次检查订单的时间
        'supplier_login_hash' => substr(strtoupper(md5($last_time)), 0, 10) // 最后登录时间的加密字符串substr(strtoupper(md5(string)), 0, 10)
    ]);
}

/**
 * 记录管理员的操作内容
 *
 * @access  public
 * @param   string $sn 数据的唯一值
 * @param   string $action 操作的类型
 * @param   string $content 操作的内容
 * @return  void
 */
function admin_log($sn = '', $action, $content)
{
    app(DscRepository::class)->helpersLang('log_action', 'suppliers');

    $supply_id = session('supply_id', 0);

    $log_action = isset($GLOBALS['_LANG']['log_action'][$action]) && $GLOBALS['_LANG']['log_action'][$action] ? $GLOBALS['_LANG']['log_action'][$action] : '';

    $log_info = $log_action . $GLOBALS['_LANG']['log_action'][$content];
    if ($sn) {
        $log_info .= ': ' . addslashes($sn);
    }

    $ip = app(DscRepository::class)->dscIp();

    $sql = 'INSERT INTO ' . $GLOBALS['dsc']->table('admin_log') . ' (log_time, user_id, log_info, ip_address) ' .
        " VALUES ('" . gmtime() . "', $supply_id, '" . stripslashes($log_info) . "', '" . $ip . "')";
    $GLOBALS['db']->query($sql);
}

/**
 * 系统提示信息
 *
 * @access      public
 * @param       string      msg_detail      消息内容
 * @param       int         msg_type        消息类型， 0消息，1错误，2询问
 * @param       array       links           可选的链接
 * @param       boolen $auto_redirect 是否需要自动跳转
 * @param       boolen $is_ajax 执行异步加载代码
 * @return      void
 */
function sys_msg($msg_detail, $msg_type = 0, $links = array(), $auto_redirect = true, $is_ajax = false)
{
    if (count($links) == 0) {
        $links[0]['text'] = $GLOBALS['_LANG']['go_back'];
        $links[0]['href'] = 'javascript:history.go(-1)';
    }

    $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['system_message']);
    $GLOBALS['smarty']->assign('msg_detail', $msg_detail);
    $GLOBALS['smarty']->assign('msg_type', $msg_type);
    $GLOBALS['smarty']->assign('links', $links);
    $GLOBALS['smarty']->assign('default_url', $links[0]['href']);
    $GLOBALS['smarty']->assign('auto_redirect', $auto_redirect);
    $GLOBALS['smarty']->assign('is_ajax', $is_ajax);

    return $GLOBALS['smarty']->display('message.dwt');
}

/**
 * 判断管理员对某一个操作是否有权限。
 *
 * 根据当前对应的action_code，然后再和用户session里面的action_list做匹配，以此来决定是否可以继续执行。
 * @param     string $priv_str 操作对应的priv_str
 * @param     string $msg_type 返回的类型
 * @return true/false
 */
function admin_priv($priv_str, $msg_type = '', $msg_output = true)
{
    if (!session()->has('supply_action_list')) {
        $admin_id = get_admin_id();
        $sql = 'SELECT action_list ' .
            ' FROM ' . $GLOBALS['dsc']->table('admin_user') .
            " WHERE user_id = '$admin_id'";
        $action_list = $GLOBALS['db']->getOne($sql, true);
        session([
            'supply_action_list' => $action_list
        ]);
    } else {
        $action_list = session('supply_action_list');
    }

    // 登录状态
    $status = app(CommonManageService::class)->loginStatus();

    if($status == 1) {
        $Loaction = "privilege.php?act=login";
        return dsc_header("Location: $Loaction\n");
    }

    if ($action_list == 'all') {
        return true;
    }

    if (strpos(',' . $action_list . ',', ',' . $priv_str . ',') === false) {
        $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
        if ($msg_output) {
            sys_msg($GLOBALS['_LANG']['priv_error'], 0, $link);
        }
        return false;
    } else {
        return true;
    }
}

/**
 * 保存过滤条件
 * @param   array $filter 过滤条件
 * @param   string $sql 查询语句
 * @param   string $param_str 参数字符串，由list函数的参数组成
 */
function set_filter($filter, $sql, $param_str = '')
{
    $filterfile = basename(PHP_SELF, '.php');
    if ($param_str) {
        $filterfile .= $param_str;
    }

    cookie()->queue('ECSCP[lastfilterfile]', sprintf('%X', crc32($filterfile)), time() + 600);

    // 过渡空值数组
    $filter = array_filter($filter);
    cookie()->queue('ECSCP[lastfilter]', urlencode(serialize($filter)), time() + 600);
    cookie()->queue('ECSCP[lastfiltersql]', base64_encode($sql), time() + 600);
}

/**
 * 取得上次的过滤条件
 *
 * @param string $param_str 参数字符串，由list函数的参数组成
 * @return array|bool 如果有，返回array('filter' => $filter, 'sql' => $sql)；否则返回false
 */
function get_filter($param_str = '')
{
    $filterfile = basename(PHP_SELF, '.php');
    if ($param_str) {
        $filterfile .= $param_str;
    }

    $ecscpCookie = request()->cookie('ECSCP');

    $sql = $ecscpCookie['lastfiltersql'] ?? '';
    $sql = $sql ? base64_decode($sql) : '';

    if (isset($_GET['uselastfilter']) && isset($ecscpCookie['lastfilterfile'])
        && $ecscpCookie['lastfilterfile'] == sprintf('%X', crc32($filterfile))) {
        return [
            'filter' => unserialize(urldecode($ecscpCookie['lastfilter'] ?? '')),
            'sql' => $sql
        ];
    } else {
        return false;
    }
}

/**
 * 分页的信息加入条件的数组
 *
 * @access  public
 * @return  array
 */
function page_and_size($filter)
{
    $page_size = request()->cookie('dsccp_page_size');
    if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
        $filter['page_size'] = intval($_REQUEST['page_size']);
    } elseif (intval($page_size) > 0) {
        $filter['page_size'] = intval($page_size);
    } else {
        $filter['page_size'] = 15;
    }

    /* 每页显示 */
    $filter['page'] = (empty($_REQUEST['page']) || intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

    /* page 总数 */
    $filter['page_count'] = (!empty($filter['record_count']) && $filter['record_count'] > 0) ? ceil($filter['record_count'] / $filter['page_size']) : 1;

    /* 边界处理 */
    if ($filter['page'] > $filter['page_count']) {
        $filter['page'] = $filter['page_count'];
    }

    $filter['start'] = ($filter['page'] - 1) * $filter['page_size'];

    return $filter;
}

/*
 * 获取商品品牌
 */
function get_goods_brand_info($brand_id = 0)
{
    $sql = "SELECT * FROM " . $GLOBALS['dsc']->table('brand') . " WHERE brand_id = '$brand_id' LIMIT 1";
    return $GLOBALS['db']->getRow($sql);
}

/**
 * 根据过滤条件获得排序的标记
 *
 * @access  public
 * @param   array $filter
 * @return  array
 */
function sort_flag($filter)
{
    $flag['tag'] = 'sort_' . preg_replace('/^.*\./', '', $filter['sort_by']);
    $flag['img'] = '<img src="' . __TPL__ . '/images/' . ($filter['sort_order'] == "DESC" ? 'sort_desc.gif' : 'sort_asc.gif') . '"/>';

    return $flag;
}

/**
 * 获得商品类型的列表
 *
 * @access  public
 * @param   integer $selected 选定的类型编号
 * @return  string
 */
function goods_type_list($selected, $goods_id = 0, $type = 'html')
{
    //ecmoban模板堂 --zhuo start
    $adminru = get_admin_ru_id();
    $ruCat = '';

    if ($goods_id > 0) {
        if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
            $ruCat = " and user_id = 0";
            $ruCat .= " AND suppliers_id = 0";
        } elseif ($GLOBALS['_CFG']['attr_set_up'] == 1) {
            $ruCat .= " AND suppliers_id = '" . $adminru['suppliers_id'] . "' AND user_id = '" . $adminru['ru_id'] . "'";
        }
    } else {
        if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
            $ruCat = " and user_id = 0";
            $ruCat .= " AND suppliers_id = 0";
        } elseif ($GLOBALS['_CFG']['attr_set_up'] == 1) {
            $ruCat .= " AND suppliers_id = '" . $adminru['suppliers_id'] . "' AND user_id = '" . $adminru['ru_id'] . "'";
        }
    }
    //ecmoban模板堂 --zhuo end

    $sql = 'SELECT cat_id, cat_name ,c_id FROM ' . $GLOBALS['dsc']->table('goods_type') . ' WHERE enabled = 1' . $ruCat;

    $res = $GLOBALS['db']->getAll($sql);

    if ($type == 'array') {
        $lst = array();

        if ($res) {
            foreach ($res as $key => $row) {
                $lst[] = array(
                    'cat_id' => $row['cat_id'],
                    'cat_name' => htmlspecialchars($row['cat_name']),
                    'c_id' => $row['c_id'],
                    'selected' => ($selected == $row['cat_id']) ? 1 : 0
                );
            }
        }
    } else {
        $lst = '';
        if ($res) {
            foreach ($res as $key => $row) {
                $lst .= "<li><a href='javascript:;' onclick='changeCat(this)' data-value='$row[cat_id]' class='ftx-01'>";
                $lst .= htmlspecialchars($row['cat_name']) . '</a></li>';
            }
        }
    }

    return $lst;
}

/**
 * 生成编辑器
 * @param   string  input_name  输入框名称
 * @param   string  input_value 输入框值
 */
function create_html_editor($input_name, $input_value = '')
{
    $input_height = $GLOBALS['_CFG']['editing_tools'] == 'ueditor' ? 586 : 500;
    $FCKeditor = '<input type="hidden" id="' . $input_name . '" name="' . $input_name . '" value="' . htmlspecialchars($input_value) . '" /><iframe id="' . $input_name . '_frame" src="' . __ROOT__ . SUPPLLY_PATH . "/" . "editor.php?item=" . $input_name . '" width="100%" height="' . $input_height . '" frameborder="0" scrolling="no"></iframe>';
    $GLOBALS['smarty']->assign('FCKeditor', $FCKeditor);
}

/**
 * 生成编辑器2
 * @param   string  input_name  输入框名称
 * @param   string  input_value 输入框值
 */
function create_html_editor2($input_name, $output_name, $input_value = '')
{
    $input_height = $GLOBALS['_CFG']['editing_tools'] == 'ueditor' ? 586 : 500;
    $FCKeditor = '<input type="hidden" id="' . $input_name . '" name="' . $input_name . '" value="' . htmlspecialchars($input_value) . '" /><iframe id="' . $input_name . '_frame" src="' . __ROOT__ . SUPPLLY_PATH . "/" . "editor.php?item=" . $output_name . '" width="100%" height="' . $input_height . '" frameborder="0" scrolling="no"></iframe>';

    $GLOBALS['smarty']->assign($output_name, $FCKeditor);
}

/**
 * 生成链接后缀
 */
function list_link_postfix()
{
    return 'uselastfilter=1';
}

/**
 * 创建一个JSON格式的数据
 *
 * @param string $content
 * @param int $error
 * @param string $message
 * @param array $append
 * @return \Illuminate\Http\JsonResponse
 */
function make_json_response($content = '', $error = 0, $message = '', $append = array())
{
    $res = array('error' => $error, 'message' => $message, 'content' => $content);

    if (!empty($append)) {
        foreach ($append as $key => $val) {
            $res[$key] = $val;
        }
    }

    return response()->json($res);
}

/**
 * 获得指定的商品类型下所有的属性分组
 *
 * @param   integer $cat_id 商品类型ID
 *
 * @return  array
 */
function get_attr_groups($cat_id)
{
    $sql = "SELECT attr_group FROM " . $GLOBALS['dsc']->table('goods_type') . " WHERE cat_id='$cat_id'";
    $grp = str_replace("\r", '', $GLOBALS['db']->getOne($sql));

    if ($grp) {
        return explode("\n", $grp);
    } else {
        return array();
    }
}

/**
 * 检查管理员权限，返回JSON格式数剧
 *
 * @access  public
 * @param   string $authz
 * @return  void
 */
function check_authz_json($authz)
{
    if (!check_authz($authz)) {
        return make_json_error($GLOBALS['_LANG']['priv_error']);
    }

    return true;
}

/**
 * 检查管理员权限
 *
 * @access  public
 * @param   string $authz
 * @return  boolean
 */
function check_authz($authz)
{
    return (preg_match('/,*' . $authz . ',*/', session('supply_action_list')) || session('supply_action_list') == 'all');
}

/**
 * 创建一个JSON格式的错误信息
 *
 * @access  public
 * @param   string $msg
 * @return  void
 */
function make_json_error($msg)
{
    return make_json_response('', 1, $msg);
}


//属性值信息
function get_add_attr_values($attr_id, $type = 0, $list = array())
{
    $sql = "select attr_values from " . $GLOBALS['dsc']->table('attribute') . " where attr_id = '$attr_id'";
    $attr_values = $GLOBALS['db']->getOne($sql);

    if (!empty($attr_values)) {
        $attr_values = preg_replace(['/\r\n/', '/\n/', '/\r/'], ",", $attr_values); //替换空格回车换行符 为 英文逗号
        $attr_values = explode(',', $attr_values);

        $arr = array();
        for ($i = 0; $i < count($attr_values); $i++) {
            $sql = "select attr_img, attr_site from " . $GLOBALS['dsc']->table('attribute_img') . " where attr_id = '$attr_id' and attr_values = '" . $attr_values[$i] . "'";
            $res = $GLOBALS['db']->getRow($sql);

            $arr[$i]['values'] = $attr_values[$i];
            $arr[$i]['attr_img'] = $res['attr_img'];
            $arr[$i]['attr_site'] = $res['attr_site'];

            if ($type == 1) {
                if ($list) {
                    foreach ($list as $lk => $row) {
                        if ($attr_values[$i] == $row[0]) {
                            $arr[$i]['color'] = !empty($row[1]) ? $row[1] : '';
                        }
                    }
                }
            }
        }

        return $arr;
    } else {
        return array();
    }
}

//添加或修改属性图片
function get_attrimg_insert_update($attr_id, $attr_values)
{
    $image = new Image($GLOBALS['_CFG']['bgcolor']);

    if (count($attr_values) > 0) {
        for ($i = 0; $i < count($attr_values); $i++) {
            $upload = $_FILES['attr_img_' . $i];
            $attr_site = trim($_POST['attr_site_' . $i]);

            $upFile = $image->upload_image($upload, 'septs_Image/attr_img_' . $attr_id);
            $upFile = !empty($upFile) ? $upFile : '';

            $sql = "select id, attr_img from " . $GLOBALS['dsc']->table('attribute_img') . " where attr_id = '$attr_id' and attr_values = '" . $attr_values[$i]['values'] . "'";
            $res = $GLOBALS['db']->getRow($sql);

            if (empty($upFile)) {
                $upFile = $res['attr_img'];
            }

            $other = array(
                'attr_id' => $attr_id,
                'attr_values' => $attr_values[$i]['values'],
                'attr_img' => $upFile,
                'attr_site' => $attr_site,
            );

            if (!empty($upFile)) {
                if ($res['id'] > 0) {
                    if ($upFile != $res['attr_img']) { //更新图片之前将上一张图片删除
                        dsc_unlink(storage_public($res['attr_img']));
                    }

                    $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('attribute_img'), $other, "UPDATE", "attr_id = '$attr_id' and attr_values = '" . $attr_values[$i]['values'] . "'");
                } else {
                    $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('attribute_img'), $other, "INSERT");
                }
            }
        }
    }
}

/**
 *
 *
 * @access  public
 * @param
 * @return  void
 */
function make_json_result($content, $message = '', $append = array())
{
    return make_json_response($content, 0, $message, $append);
}

/**
 *
 *
 * @access  public
 * @param
 * @return  void
 */
function make_json_result_too($content, $error = 0, $message = '', $append = array())
{
    return make_json_response($content, $error, $message, $append);
}

/**
 * 退换货  by  Leah
 * @return type
 */
function return_order_list()
{
    $result = get_filter();

    $adminru = get_admin_ru_id();

    if ($result === false) {
        /* 过滤信息 */
        $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
        if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
            $_REQUEST['consignee'] = json_str_iconv($_REQUEST['consignee']);
            //$_REQUEST['address'] = json_str_iconv($_REQUEST['address']);
        }
        $filter['return_sn'] = isset($_REQUEST['return_sn']) ? trim($_REQUEST['return_sn']) : '';
        $filter['order_id'] = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
        $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);
        $filter['email'] = empty($_REQUEST['email']) ? '' : trim($_REQUEST['email']);
        $filter['address'] = empty($_REQUEST['address']) ? '' : trim($_REQUEST['address']);
        $filter['zipcode'] = empty($_REQUEST['zipcode']) ? '' : trim($_REQUEST['zipcode']);
        $filter['tel'] = empty($_REQUEST['tel']) ? '' : trim($_REQUEST['tel']);
        $filter['mobile'] = empty($_REQUEST['mobile']) ? 0 : intval($_REQUEST['mobile']);
        $filter['country'] = empty($_REQUEST['country']) ? 0 : intval($_REQUEST['country']);
        $filter['province'] = empty($_REQUEST['province']) ? 0 : intval($_REQUEST['province']);
        $filter['city'] = empty($_REQUEST['city']) ? 0 : intval($_REQUEST['city']);
        $filter['district'] = empty($_REQUEST['district']) ? 0 : intval($_REQUEST['district']);
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

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'ret_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

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
        if ($filter['country']) {
            $where .= " AND o.country = '$filter[country]'";
        }
        if ($filter['province']) {
            $where .= " AND o.province = '$filter[province]'";
        }
        if ($filter['city']) {
            $where .= " AND o.city = '$filter[city]'";
        }
        if ($filter['district']) {
            $where .= " AND o.district = '$filter[district]'";
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
            //已退款
            if ($filter['return_type'] == 1) {
                $where .= " AND r.refound_status = " . $filter['return_type'] . " AND r.return_type = 3";
            } elseif ($filter['return_type'] == 3) {
                $where .= " AND r.return_status = 1 AND r.return_type = 1";
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
        $sql = "SELECT agency_id FROM " . $GLOBALS['dsc']->table('admin_user') . " WHERE user_id = '" . session('supply_id') . "'";
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
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('wholesale_order_return') . " AS r, " . $GLOBALS['dsc']->table('wholesale_order_info') . " as o " . $where . " AND r.order_id = o.order_id";
        }

        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT o.order_id, o.order_sn, o.add_time, o.order_status, o.shipping_status, o.order_amount, " .
            "o.pay_status, o.consignee, o.email, o.tel, o.extension_code, " .
            "r.ret_id ,r.rec_id, r.address , r.back , r.exchange ,r.attr_val , r.cause_id , r.apply_time , r.should_return , r.actual_return , r.remark , r.address , r.return_status , r.refound_status , " .
            " r.return_type, r.addressee, r.phone, r.return_sn, return_shipping_fee, " .
            " o.order_amount AS total_fee, " .
            "IFNULL(u.user_name, '" . $GLOBALS['_LANG']['anonymous'] . "') AS buyer " .
            "FROM " . $GLOBALS['dsc']->table('wholesale_order_return') . " AS r " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('wholesale_order_info') . " AS o ON r.order_id = o.order_id " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('users') . " AS u ON u.user_id=o.user_id  " . $where . $where_store .
            " GROUP BY r.ret_id ORDER BY $filter[sort_by] $filter[sort_order] " .
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
    foreach ($row as $key => $value) {
        $row[$key]['return_pay_status'] = $value['refound_status'];

        $row[$key]['formated_order_amount'] = price_format($value['order_amount']);
        $row[$key]['formated_total_fee'] = price_format($value['total_fee']);
        $row[$key]['short_order_time'] = local_date('m-d H:i', $value['add_time']);
        $row[$key]['apply_time'] = local_date('m-d H:i', $value['apply_time']);

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
    }
    $arr = array('orders' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}
