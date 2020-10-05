<?php

use App\Libraries\Image;
use App\Models\AdminLog;
use App\Models\OrderInfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Common\CommonManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderService;
use App\Services\User\UserAddressService;

/**
 * 系统提示信息
 *
 * @access      public
 * @param string      msg_detail      消息内容
 * @param int         msg_type        消息类型， 0消息，1错误，2询问
 * @param array       links           可选的链接
 * @param boolen $auto_redirect 是否需要自动跳转
 * @param boolen $is_ajax 执行异步加载代码
 * @return      void
 */
function sys_msg($msg_detail, $msg_type = 0, $links = [], $auto_redirect = true, $is_ajax = false)
{
    if (count($links) == 0) {
        $links[0]['text'] = lang('admin/common.go_back');
        $links[0]['href'] = 'javascript:history.go(-1)';

        $default_url = $links[0]['href'];
    } else {
        if (isset($links[0])) {
            $default_url = $links[0]['href'];
        } elseif (isset($links[1])) {
            $default_url = $links[1]['href'];
        }
    }

    $links[0] = isset($links[0]) ? $links[0] : ['href' => ''];
    $GLOBALS['smarty']->assign('ur_here', lang('admin/common.system_message'));
    $GLOBALS['smarty']->assign('msg_detail', $msg_detail);
    $GLOBALS['smarty']->assign('msg_type', $msg_type);
    $GLOBALS['smarty']->assign('links', $links);
    $GLOBALS['smarty']->assign('default_url', $default_url);
    $GLOBALS['smarty']->assign('auto_redirect', $auto_redirect);
    $GLOBALS['smarty']->assign('is_ajax', $is_ajax);

    return $GLOBALS['smarty']->display('message.dwt');
}

/**
 * 记录管理员的操作内容
 *
 * @access  public
 * @param string $sn 数据的唯一值
 * @param string $action 操作的类型
 * @param string $content 操作的内容
 * @return  void
 */
function admin_log($sn = '', $action, $content)
{
    $action = isset($GLOBALS['_LANG']['log_action'][$action]) ? $GLOBALS['_LANG']['log_action'][$action] : '';

    $log_action = isset($GLOBALS['_LANG']['log_action'][$content]) ? $GLOBALS['_LANG']['log_action'][$content] : '';

    $log_info = $action . $log_action;
    if ($sn) {
        $log_info .= ': ' . addslashes($sn);
    }

    AdminLog::insert([
        'log_time' => gmtime(),
        'user_id' => session('admin_id'),
        'log_info' => $log_info,
        'ip_address' => app(DscRepository::class)->dscIp()
    ]);
}

/**
 * 将通过表单提交过来的年月日变量合成为"2004-05-10"的格式。
 *
 * 此函数适用于通过smarty函数html_select_date生成的下拉日期。
 *
 * @param string $prefix 年月日变量的共同的前缀。
 * @return date                日期变量。
 */
function sys_joindate($prefix)
{
    /* 返回年-月-日的日期格式 */
    $year = empty($_POST[$prefix . 'Year']) ? '0' : $_POST[$prefix . 'Year'];
    $month = empty($_POST[$prefix . 'Month']) ? '0' : $_POST[$prefix . 'Month'];
    $day = empty($_POST[$prefix . 'Day']) ? '0' : $_POST[$prefix . 'Day'];

    return $year . '-' . $month . '-' . $day;
}

/**
 * 设置管理员的session内容
 *
 * @access  public
 * @param integer $user_id 管理员编号
 * @param string $username 管理员姓名
 * @param string $action_list 权限列表
 * @param string $last_time 最后登录时间
 * @return  void
 */
function set_admin_session($user_id, $username, $action_list, $last_time)
{
    $other = [
        'admin_id' => $user_id,
        'admin_name' => $username,
        'action_list' => $action_list,
        'last_check' => $last_time, // 用于保存最后一次检查订单的时间
        'admin_login_hash' => substr(strtoupper(md5($last_time)), 0, 10) // 最后登录时间的加密字符串substr(strtoupper(md5(string)), 0, 10)
    ];
    session($other);
}

/**
 * 插入一个配置信息
 *
 * @access  public
 * @param string $parent 分组的code
 * @param string $code 该配置信息的唯一标识
 * @param string $value 该配置信息值
 * @return  void
 */
function insert_config($parent, $code, $value)
{
    $sql = 'SELECT id FROM ' . $GLOBALS['dsc']->table('shop_config') . " WHERE code = '$parent' AND type = 1";
    $parent_id = $GLOBALS['db']->getOne($sql);

    $sql = 'INSERT INTO ' . $GLOBALS['dsc']->table('shop_config') . ' (parent_id, code, value) ' .
        "VALUES('$parent_id', '$code', '$value')";
    $GLOBALS['db']->query($sql);
}

/**
 * 判断管理员对某一个操作是否有权限。
 *
 * 根据当前对应的action_code，然后再和用户session里面的action_list做匹配，以此来决定是否可以继续执行。
 * @param string $priv_str 操作对应的priv_str
 * @param string $msg_type 返回的类型
 * @return true/false
 */
function admin_priv($priv_str, $msg_type = '', $msg_output = true)
{
    if (!session()->has('action_list')) {
        $admin_id = get_admin_id();
        $sql = 'SELECT action_list ' .
            ' FROM ' . $GLOBALS['dsc']->table('admin_user') .
            " WHERE user_id = '$admin_id'";
        $action_list = $GLOBALS['db']->getOne($sql, true);

        session([
            'action_list' => $action_list
        ]);
    } else {
        $action_list = session()->get('action_list');
    }

    if ($action_list == 'all') {
        return true;
    }

    if (strpos(',' . $action_list . ',', ',' . $priv_str . ',') === false) {
        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
        if ($msg_output) {
            return sys_msg($GLOBALS['_LANG']['priv_error'], 1, $link);
        }
        return false;
    } else {
        return true;
    }
}

/**
 * 检查管理员权限
 *
 * @access  public
 * @param string $authz
 * @return  boolean
 */
function check_authz($authz)
{
    return (preg_match('/,*' . $authz . ',*/', session()->get('action_list')) || session()->get('action_list') == 'all');
}

/**
 * 检查管理员权限，返回JSON格式数剧
 *
 * @access  public
 * @param string $authz
 * @return  string
 */
function check_authz_json($authz)
{
    if (!check_authz($authz)) {
        return make_json_error($GLOBALS['_LANG']['priv_error']);
    }

    return true;
}

/**
 * 取得红包类型数组（用于生成下拉列表）
 *
 * @return  array       分类数组 bonus_typeid => bonus_type_name
 */
function get_bonus_type()
{
    $bonus = [];
    $sql = 'SELECT type_id, type_name, type_money FROM ' . $GLOBALS['dsc']->table('bonus_type') .
        ' WHERE send_type = 3';
    $res = $GLOBALS['db']->query($sql);

    foreach ($res as $row) {
        $bonus[$row['type_id']] = $row['type_name'] . ' [' . sprintf($GLOBALS['_CFG']['currency_format'], $row['type_money']) . ']';
    }

    return $bonus;
}

/**
 * 取得购物卡类型数组（用于生成下拉列表）
 *
 * @return  array       分类数组 bonus_typeid => bonus_type_name
 */
function get_pay_card_type($id)
{
    $bonus = [];
    $sql = 'SELECT type_id, type_name, type_money FROM ' . $GLOBALS['dsc']->table('pay_card_type') . " WHERE type_id = '$id' LIMIT 1 ";
    $res = $GLOBALS['db']->query($sql);

    foreach ($res as $row) {
        $bonus['name'] = $row['type_name'] . ' [' . sprintf($GLOBALS['_CFG']['currency_format'], $row['type_money']) . ']';
    }

    return $bonus;
}

/**
 * 取得用户等级数组,按用户级别排序
 * @param bool $is_special 是否只显示特殊会员组
 * @return  array     rank_id=>rank_name
 */
function get_rank_list($is_special = false)
{
    $rank_list = [];
    $sql = 'SELECT rank_id, rank_name, min_points FROM ' . $GLOBALS['dsc']->table('user_rank');
    if ($is_special) {
        $sql .= ' WHERE special_rank = 1';
    }
    $sql .= ' ORDER BY min_points';

    $res = $GLOBALS['db']->query($sql);

    foreach ($res as $row) {
        $rank_list[$row['rank_id']] = $row['rank_name'];
    }

    return $rank_list;
}

/**
 * 按等级取得用户列表（用于生成下拉列表）
 *
 * @return  array       分类数组 user_id => user_name
 */
function get_user_rank($rankid, $where)
{
    $user_list = [];
    $sql = 'SELECT user_id, user_name FROM ' . $GLOBALS['dsc']->table('users') . $where .
        ' ORDER BY user_id DESC';
    $res = $GLOBALS['db']->query($sql);

    foreach ($res as $row) {
        $user_list[$row['user_id']] = $row['user_name'];
    }

    return $user_list;
}

function get_cfg_val($arr = [])
{
    $new_arr = [];
    if ($arr) {
        foreach ($arr as $row) {
            array_push($new_arr, $row['code'] . "**" . $row['value']);
        }

        $new_arr2 = [];
        foreach ($new_arr as $key => $rows) {
            $rows = explode('**', $rows);
            $new_arr2[$rows[0]] = $rows[1];
        }

        $new_arr = $new_arr2;
    }

    return $new_arr;
}

/**
 * 取得广告位置数组（用于生成下拉列表）
 *
 * @return  array       分类数组 position_id => position_name
 */
function get_position_list()
{
    $adminru = get_admin_ru_id();
    $where = ' WHERE 1';

    if ($adminru['ru_id'] > 0) {
        $where .= " AND (user_id = '" . $adminru['ru_id'] . "' or is_public = 1) ";
    }

    //获取指定模版广告
    $where .= " AND theme = '" . $GLOBALS['_CFG']['template'] . "' ";

    $position_list = [];
    $sql = 'SELECT position_id, position_name, ad_width, ad_height ' .
        'FROM ' . $GLOBALS['dsc']->table('ad_position') . $where;
    $res = $GLOBALS['db']->query($sql);

    foreach ($res as $row) {
        $position_list[$row['position_id']] = addslashes($row['position_name']) . ' [' . $row['ad_width'] . 'x' . $row['ad_height'] . ']';
    }

    return $position_list;
}

/**
 * 生成编辑器
 * @param string  input_name  输入框名称
 * @param string  input_value 输入框值
 */
function create_html_editor($input_name, $input_value = '')
{
    $input_height = $GLOBALS['_CFG']['editing_tools'] == 'ueditor' ? 586 : 500;
    $FCKeditor = '<input type="hidden" id="' . $input_name . '" name="' . $input_name . '" value="' . htmlspecialchars($input_value) . '" /><iframe id="' . $input_name . '_frame" src="' . __ROOT__ . ADMIN_PATH . "/" . "editor.php?item=" . $input_name . '" width="100%" height="' . $input_height . '" frameborder="0" scrolling="no"></iframe>';
    $GLOBALS['smarty']->assign('FCKeditor', $FCKeditor);
}

/**
 * 生成编辑器2
 * @param string  input_name  输入框名称
 * @param string  input_value 输入框值
 */
function create_html_editor2($input_name, $output_name = 'goods_desc', $input_value = '')
{
    $input_height = $GLOBALS['_CFG']['editing_tools'] == 'ueditor' ? 586 : 500;
    $FCKeditor = '<input type="hidden" id="' . $input_name . '" name="' . $input_name . '" value="' . htmlspecialchars($input_value) . '" /><iframe id="' . $input_name . '_frame" src="' . __ROOT__ . ADMIN_PATH . "/" . "editor.php?item=" . $output_name . '" width="100%" height="' . $input_height . '" frameborder="0" scrolling="no"></iframe>';

    $GLOBALS['smarty']->assign($output_name, $FCKeditor);
}

/**
 * 取得商品列表：用于把商品添加到组合、关联类、赠品类
 * @param object $filters 过滤条件
 */
function get_goods_list($filter, $limit = ['start' => 0, 'number' => 50])
{
    $filter->keyword = isset($filter->keyword) ? json_str_iconv($filter->keyword) : '';
    $where = get_where_sql($filter); // 取得过滤条件

    /* 取得数据 */
    $sql = 'SELECT goods_id, goods_name, shop_price ' .
        'FROM ' . $GLOBALS['dsc']->table('goods') . ' AS g ' . $where .
        ' LIMIT ' . $limit['start'] . ',' . $limit['number'];

    $row = $GLOBALS['db']->getAll($sql);

    return $row;
}

/**
 * 取得文章列表：用于商品关联文章
 * @param object $filters 过滤条件
 */
function get_article_list($filter)
{
    /* 创建数据容器对象 */
    $ol = new OptionList();

    /* 取得过滤条件 */
    $where = ' WHERE a.cat_id = c.cat_id AND c.cat_type = 1 ';
    $where .= isset($filter->title) ? " AND a.title LIKE '%" . mysql_like_quote($filter->title) . "%'" : '';

    /* 取得数据 */
    $sql = 'SELECT a.article_id, a.title ' .
        'FROM ' . $GLOBALS['dsc']->table('article') . ' AS a, ' . $GLOBALS['dsc']->table('article_cat') . ' AS c ' . $where;
    $res = $GLOBALS['db']->query($sql);

    foreach ($res as $row) {
        $ol->add_option($row['article_id'], $row['title']);
    }

    /* 生成列表 */
    $ol->build_select();
}

/**
 * 返回是否
 * @param int $var 变量 1, 0
 */
function get_yes_no($var)
{
    return empty($var) ? '<img src="images/no.gif" border="0" />' : '<img src="images/yes.gif" border="0" />';
}

/**
 * 生成过滤条件：用于 get_goodslist 和 get_goods_list
 * @param object $filter
 * @return  string
 */
function get_where_sql($filter)
{
    $filter->brand_keyword = isset($filter->brand_keyword) ? $filter->brand_keyword : '';
    $filter->sel_mode = isset($filter->sel_mode) ? $filter->sel_mode : '';

    $adminru = get_admin_ru_id();

    $time = local_date('Y-m-d');

    $where = isset($filter->is_delete) && $filter->is_delete == '1' ?
        ' WHERE is_delete = 1 ' : ' WHERE is_delete = 0 ';
    $where .= (isset($filter->real_goods) && ($filter->real_goods > -1)) ? ' AND is_real = ' . intval($filter->real_goods) : '';
    $where .= isset($filter->cat_id) && $filter->cat_id > 0 ? ' AND ' . get_children($filter->cat_id) : '';

    // 品牌搜索 -qin
    $brand_keyword = $filter->brand_keyword;
    $sel_mode = $filter->sel_mode;

    if ($filter->brand_keyword) {
        if ($sel_mode == 1 && !empty($brand_keyword)) {
            $new_array = [];

            $sql = "SELECT brand_id FROM " . $GLOBALS['dsc']->table('brand') . " WHERE brand_name LIKE '%$brand_keyword%' ";
            $brand_id = $GLOBALS['db']->getAll($sql);

            foreach ($brand_id as $key => $value) {
                $new_array[] = $value['brand_id'];
            }

            $where .= isset($filter->brand_keyword) && trim($filter->brand_keyword) != '' ?
                " AND brand_id " . db_create_in($new_array) . "" : '';
        } elseif ($sel_mode == 1 && !empty($brand_keyword)) {
            $filter->brand_id = 0;
        }
    } else {
        $where .= isset($filter->brand_id) && $filter->brand_id > 0 ? " AND brand_id = '" . $filter->brand_id . "'" : '';
    }


    $where .= isset($filter->intro_type) && $filter->intro_type != '0' ? ' AND ' . $filter->intro_type . " = '1'" : '';
    $where .= isset($filter->intro_type) && $filter->intro_type == 'is_promote' ?
        " AND promote_start_date <= '$time' AND promote_end_date >= '$time' " : '';
    $where .= isset($filter->keyword) && trim($filter->keyword) != '' ?
        " AND (goods_name LIKE '%" . mysql_like_quote($filter->keyword) . "%' OR goods_sn LIKE '%" . mysql_like_quote($filter->keyword) . "%' OR goods_id LIKE '%" . mysql_like_quote($filter->keyword) . "%') " : '';
    $where .= isset($filter->suppliers_id) && trim($filter->suppliers_id) != '' ?
        " AND (suppliers_id = '" . $filter->suppliers_id . "') " : '';

    $where .= isset($filter->in_ids) ? ' AND goods_id ' . db_create_in($filter->in_ids) : '';
    $where .= isset($filter->exclude) ? ' AND goods_id NOT ' . db_create_in($filter->exclude) : '';
    $where .= isset($filter->stock_warning) ? ' AND goods_number <= warn_number' : '';
    //预售
    $where .= isset($filter->presale) ? " AND is_on_sale = 0 " : '';

    if (isset($filter->ru_id)) {
        $where .= " AND user_id = '" . $filter->ru_id . "'";
    } else {
        $where .= " AND user_id = '" . $adminru['ru_id'] . "'";
    }

    return $where;
}

function get_where_sql_unpre($filter)
{
    $time = local_date('Y-m-d');

    $where = isset($filter->is_delete) && $filter->is_delete == '1' ?
        ' WHERE g.is_delete = 1 ' : ' WHERE g.is_delete = 0 ';
    $where .= (isset($filter->real_goods) && ($filter->real_goods > -1)) ? ' AND g.is_real = ' . intval($filter->real_goods) : '';
    $where .= isset($filter->cat_id) && $filter->cat_id > 0 ? ' AND ' . get_children($filter->cat_id) : '';
    $where .= isset($filter->brand_id) && $filter->brand_id > 0 ? " AND b.brand_id = '" . $filter->brand_id . "'" : '';
    $where .= isset($filter->intro_type) && $filter->intro_type != '0' ? ' AND ' . $filter->intro_type . " = '1'" : '';
    $where .= isset($filter->intro_type) && $filter->intro_type == 'g.is_promote' ?
        " AND g.promote_start_date <= '$time' AND g.promote_end_date >= '$time' " : '';
    $where .= isset($filter->keyword) && trim($filter->keyword) != '' ?
        " AND (g.goods_name LIKE '%" . mysql_like_quote($filter->keyword) . "%' OR g.goods_sn LIKE '%" . mysql_like_quote($filter->keyword) . "%' OR g.goods_id LIKE '%" . mysql_like_quote($filter->keyword) . "%') " : '';
    $where .= isset($filter->suppliers_id) && trim($filter->suppliers_id) != '' ?
        " AND (g.suppliers_id = '" . $filter->suppliers_id . "') " : '';

    $where .= isset($filter->in_ids) ? ' AND g.goods_id ' . db_create_in($filter->in_ids) : '';
    $where .= isset($filter->exclude) ? ' AND g.goods_id NOT ' . db_create_in($filter->exclude) : '';
    $where .= isset($filter->stock_warning) ? ' AND g.goods_number <= warn_number' : '';

    return $where;
}

/**
 * 获取地区列表的函数。
 *
 * @access  public
 * @param int $region_id 上级地区id
 * @return  void
 */
function area_list($region_id = 0, $type = 0)
{
    $area_arr = [];

    $sql = 'SELECT * FROM ' . $GLOBALS['dsc']->table('region') .
        " WHERE parent_id = '$region_id' ORDER BY region_id";
    $res = $GLOBALS['db']->query($sql);

    $idx = 0;
    foreach ($res as $row) {
        $row['type'] = ($row['region_type'] == 0) ? $GLOBALS['_LANG']['country'] : '';
        $row['type'] .= ($row['region_type'] == 1) ? $GLOBALS['_LANG']['province'] : '';
        $row['type'] .= ($row['region_type'] == 2) ? $GLOBALS['_LANG']['city'] : '';
        $row['type'] .= ($row['region_type'] == 3) ? $GLOBALS['_LANG']['cantonal'] : '';
        $row['type'] .= ($row['region_type'] == 4) ? $GLOBALS['_LANG']['street'] : '';

        $area_arr[$idx] = $row;

        if ($type == 1) {
            $rw_id = get_table_date('region_warehouse', "regionId = '" . $row['region_id'] . "'", ['region_id'], 2);
            if ($rw_id) {
                unset($area_arr[$idx]);
            }
        }

        $idx++;
    }

    return $area_arr;
}

/**
 * 取得图表颜色
 *
 * @access  public
 * @param integer $n 颜色顺序
 * @return  void
 */
function chart_color($n)
{
    /* 随机显示颜色代码 */
    $arr = ['33FF66', 'FF6600', '3399FF', '009966', 'CC3399', 'FFCC33', '6699CC', 'CC3366', '33FF66', 'FF6600', '3399FF'];

    if ($n > 8) {
        $n = $n % 8;
    }

    return $arr[$n];
}

/**
 * 获得商品类型的列表
 *
 * @access  public
 * @param integer $selected 选定的类型编号
 * @param type $type 返回的数据类型，默认为html，也可为array
 * @return  string
 */
function goods_type_list($selected, $goods_id = 0, $type = 'html', $c_id = '')
{
    $adminru = get_admin_ru_id();

    $ruCat = '';
    if ($goods_id > 0) {
        $sql = "select user_id from " . $GLOBALS['dsc']->table('goods') . " where goods_id = '$goods_id'";
        $user_id = $GLOBALS['db']->getOne($sql);

        if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
            if ($adminru['ru_id'] > 0) {
                $ruCat = " and user_id = 0";
            }
        } elseif ($GLOBALS['_CFG']['attr_set_up'] == 1) {
            $ruCat = " and user_id = '$user_id'";
        }
    } else {
        if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
            if ($adminru['ru_id'] > 0) {
                $ruCat = " and user_id = 0";
            }
        } elseif ($GLOBALS['_CFG']['attr_set_up'] == 1) {
            $ruCat = " and user_id = '" . $adminru['ru_id'] . "'";
        }
    }

    if ($c_id) {
        $ruCat .= " and c_id = '$c_id' ";
    }

    $sql = 'SELECT cat_id, cat_name,c_id FROM ' . $GLOBALS['dsc']->table('goods_type') . ' WHERE enabled = 1 AND suppliers_id = 0' . $ruCat;
    $res = $GLOBALS['db']->query($sql);

    if ($type == 'array') {
        $lst = [];
        foreach ($res as $row) {
            $lst[] = [
                'cat_id' => $row['cat_id'],
                'cat_name' => htmlspecialchars($row['cat_name']),
                'c_id' => $row['c_id'],
                'selected' => ($selected == $row['cat_id']) ? 1 : 0
            ];
        }
    } else {
        $lst = '';
        foreach ($res as $row) {
            $lst .= "<li><a href='javascript:;' onclick='changeCat(this)' data-value='$row[cat_id]' class='ftx-01'>";
            $lst .= htmlspecialchars($row['cat_name']) . '</a></li>';
        }
    }

    return $lst;
}

/**
 * 取得货到付款和非货到付款的支付方式
 * @return  array('is_cod' => '', 'is_not_cod' => '')
 */
function get_pay_ids()
{
    $ids = ['is_cod' => '0', 'is_not_cod' => '0'];
    $sql = 'SELECT pay_id, is_cod FROM ' . $GLOBALS['dsc']->table('payment') . ' WHERE enabled = 1';
    $res = $GLOBALS['db']->query($sql);

    foreach ($res as $row) {
        if ($row['is_cod']) {
            $ids['is_cod'] .= ',' . $row['pay_id'];
        } else {
            $ids['is_not_cod'] .= ',' . $row['pay_id'];
        }
    }

    return $ids;
}

/**
 * 清空表数据
 * @param string $table_name 表名称
 */
function truncate_table($table_name)
{
    $sql = 'TRUNCATE TABLE ' . $GLOBALS['dsc']->table($table_name);

    return $GLOBALS['db']->query($sql);
}

/**
 *  返回字符集列表数组
 *
 * @access  public
 * @param
 *
 * @return void
 */
function get_charset_list()
{
    return [
        'UTF8' => 'UTF-8',
        'GB2312' => 'GB2312/GBK',
        'BIG5' => 'BIG5',
    ];
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
function make_json_response($content = '', $error = 0, $message = '', $append = [])
{

    $res = ['error' => $error, 'message' => $message, 'content' => $content];

    if (!empty($append)) {
        foreach ($append as $key => $val) {
            $res[$key] = $val;
        }
    }

    return response()->json($res);
}

/**
 * @param $content
 * @param string $message
 * @param array $append
 * @return \Illuminate\Http\JsonResponse
 */
function make_json_result($content, $message = '', $append = [])
{
    return make_json_response($content, 0, $message, $append);
}

/**
 * @param $content
 * @param int $error
 * @param string $message
 * @param array $append
 * @return mixed|string
 */
function make_json_result_too($content, $error = 0, $message = '', $append = [])
{
    return make_json_response($content, $error, $message, $append);
}

/**
 * 创建一个JSON格式的错误信息
 *
 * @param $msg
 * @return mixed|string
 */
function make_json_error($msg)
{
    return make_json_response('', 1, $msg);
}

/**
 * 根据过滤条件获得排序的标记
 *
 * @access  public
 * @param array $filter
 * @return  array
 */
function sort_flag($filter)
{
    $filter['sort_by'] = isset($filter['sort_by']) ? $filter['sort_by'] : '';
    $filter['sort_order'] = isset($filter['sort_order']) ? $filter['sort_order'] : '';
    $flag['tag'] = 'sort_' . preg_replace('/^.*\./', '', $filter['sort_by']);
    $flag['img'] = '<img src="' . __TPL__ . '/images/' . ($filter['sort_order'] == "DESC" ? 'sort_desc.gif' : 'sort_asc.gif') . '"/>';

    return $flag;
}

/**
 * 分页的信息加入条件的数组
 *
 * @param $filter
 * @param int $type
 * @return mixed
 */
function page_and_size($filter, $type = 0)
{
    if ($type == 1) {
        $filter['page_size'] = 10;
    } elseif ($type == 2) {
        $filter['page_size'] = 14;
    } elseif ($type == 3) {
        $filter['page_size'] = 21;
    } elseif ($type == 4) {
        $filter['page_size'] = 18;
    } else {
        $page_size = request()->cookie('dsccp_page_size');
        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (intval($page_size) > 0) {
            $filter['page_size'] = intval($page_size);
        } else {
            $filter['page_size'] = 15;
        }
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

/**
 *  将含有单位的数字转成字节
 *
 * @access  public
 * @param string $val 带单位的数字
 *
 * @return  int         $val
 */
function return_bytes($val)
{
    $val = trim($val);
    $last = strtolower($val{strlen($val) - 1});
    switch ($last) {
        case 'g':
            $val = str_replace('G', '', $val) * 1024;
            $val .= 'M';
        // no break
        case 'm':
            $val = str_replace('M', '', $val) * 1024;
            $val .= 'K';
        // no break
        case 'k':
            $val = str_replace('K', '', $val) * 1024;
    }

    return $val;
}

/**
 * 获得指定的商品类型下所有的属性分组
 *
 * @param integer $cat_id 商品类型ID
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
        return [];
    }
}

/**
 * 生成链接后缀
 */
function list_link_postfix()
{
    return 'uselastfilter=1';
}

/**
 * 保存过滤条件
 * @param array $filter 过滤条件
 * @param string $sql 查询语句
 * @param string $param_str 参数字符串，由list函数的参数组成
 */
function set_filter($filter, $sql = '', $param_str = '')
{
    $filterfile = basename(PHP_SELF, '.php');
    if ($param_str) {
        $filterfile .= $param_str;
    }

    cookie()->queue('ECSCP[lastfilterfile]', sprintf('%X', crc32($filterfile)), 10);

    // 过渡空值数组
    $filter = array_filter($filter);

    cookie()->queue('ECSCP[lastfilter]', urlencode(serialize($filter)), 10);

    if ($sql) {
        cookie()->queue('ECSCP[lastfiltersql]', base64_encode($sql), 10);
    }
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
 * URL过滤
 * @param string $url 参数字符串，一个urld地址,对url地址进行校正
 * @return  返回校正过的url;
 */
function sanitize_url($url)
{
    if ($url && strpos($url, "http://") === false && strpos($url, "https://") === false) {
        $url = $GLOBALS['dsc']->http() . $url;
    }
    return $url;
}

/**
 * 检查分类是否已经存在
 *
 * @param string $cat_name 分类名称
 * @param integer $parent_cat 上级分类
 * @param integer $exclude 排除的分类ID
 *
 * @return  boolean
 */
function cat_exists($cat_name, $parent_cat, $exclude = 0, $ru_id = 0)
{
    if ($ru_id > 0) {
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('merchants_category') .
            " WHERE parent_id = '$parent_cat' AND cat_name = '$cat_name' AND  cat_id <> '$exclude' AND user_id = '$ru_id'";

        return ($GLOBALS['db']->getOne($sql) > 0) ? true : false;
    } else {
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('category') .
            " WHERE parent_id = '$parent_cat' AND cat_name = '$cat_name' AND cat_id<>'$exclude'";
        return ($GLOBALS['db']->getOne($sql) > 0) ? true : false;
    }
}

function brand_exists($brand_name)
{
    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('brand') .
        " WHERE brand_name = '" . $brand_name . "'";
    return ($GLOBALS['db']->getOne($sql) > 0) ? true : false;
}

/**
 * 获取当前管理员信息
 *
 * @access  public
 * @param
 *
 * @return  Array
 */
function admin_info()
{
    $sql = "SELECT * FROM " . $GLOBALS['dsc']->table('admin_user') . "
            WHERE user_id = '" . session('admin_id') . "' LIMIT 0, 1";
    $admin_info = $GLOBALS['db']->getRow($sql);

    if (empty($admin_info)) {
        return $admin_info = [];
    }

    return $admin_info;
}

/**
 * 供货商列表信息
 *
 * @param string $conditions
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
 * 供货商名
 *
 * @return  array
 */
function suppliers_list_name()
{
    /* 查询 */
    $suppliers_list = suppliers_list_info(' is_check = 1 ');

    /* 供货商名字 */
    $suppliers_name = [];
    if (count($suppliers_list) > 0) {
        foreach ($suppliers_list as $suppliers) {
            $suppliers_name[$suppliers['suppliers_id']] = $suppliers['suppliers_name'];
        }
    }

    return $suppliers_name;
}

//商创版--后台程序开发 start

/* 上传文件 start */
function get_upload_pic($fname)
{
    $allow_file_types = '|GIF|JPG|JPEG|PNG|';
    $ret = '';
    if (empty($_FILES[$fname]['error']) || (!isset($_FILES[$fname]['error']) && isset($_FILES[$fname]['tmp_name']) && $_FILES[$fname]['tmp_name'] != 'none')) {
        // 检查文件格式
        if (!check_file_type($_FILES[$fname]['tmp_name'], $_FILES[$fname]['name'], $allow_file_types)) {
            return sys_msg('无效的文件类型');
        }

        // 复制文件
        $res = upload_teacher_img($_FILES[$fname]);
        if ($res != false) {
            $ret = $res;
        }
    }
    return $ret;
}

/* 上传文件 */
function upload_teacher_img($upload)
{
    $img_dir = '/goods_attr_img';

    /* 创建目录失败 */
    if (!file_exists(storage_public(DATA_DIR . $img_dir))) {
        make_dir(storage_public(DATA_DIR . $img_dir));
    }

    $filename = app(Image::class)->random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
    $path = storage_public(DATA_DIR . $img_dir . '/' . $filename);

    if (move_upload_file($upload['tmp_name'], $path)) {
        return DATA_DIR . $img_dir . '/' . $filename;
    } else {
        return false;
    }
}

/* 上传文件 end */

//属性值信息
function get_add_attr_values($attr_id, $type = 0, $list = [], $attr_input_type = 0)
{
    if ($attr_input_type == 1) {
        $sql = "SELECT attr_values FROM " . $GLOBALS['dsc']->table('attribute') . " WHERE attr_id = '$attr_id'";
        $attr_values = $GLOBALS['db']->getOne($sql);
    } else {
        $sql = "SELECT GROUP_CONCAT(ga.attr_value) AS attr_values FROM " . $GLOBALS['dsc']->table('attribute') . " AS a, " .
            $GLOBALS['dsc']->table('goods_attr') . " AS ga " .
            " WHERE a.attr_id = ga.attr_id AND a.attr_id = '$attr_id'";
        $attribute_info = $GLOBALS['db']->getRow($sql);

        if ($attribute_info) {
            $attr_values = explode(",", trim($attribute_info['attr_values']));
        }
    }

    if (!empty($attr_values)) {
        if ($attr_input_type) {
            $attr_values = preg_replace(['/\r\n/', '/\n/', '/\r/'], ",", $attr_values); //替换空格回车换行符 为 英文逗号
            $attr_values = explode(',', $attr_values);
        }

        $arr = [];
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
        return [];
    }
}

//添加或修改属性图片
function get_attrimg_insert_update($attr_id, $attr_values)
{
    $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

    if (count($attr_values) > 0) {
        for ($i = 0; $i < count($attr_values); $i++) {
            $upload = $_FILES['attr_img_' . $i];
            $attr_site = trim($_POST['attr_site_' . $i]);

            $upFile = $image->upload_image($upload, 'septs_image/attr_img_' . $attr_id);
            $upFile = !empty($upFile) ? $upFile : '';

            $sql = "select id, attr_img from " . $GLOBALS['dsc']->table('attribute_img') . " where attr_id = '$attr_id' and attr_values = '" . $attr_values[$i]['values'] . "'";
            $res = $GLOBALS['db']->getRow($sql);

            $drop_img = 0;
            if (empty($upFile)) {
                $upFile = $res['attr_img'];
            }

            $other = [
                'attr_id' => $attr_id,
                'attr_values' => $attr_values[$i]['values'],
                'attr_img' => $upFile,
                'attr_site' => $attr_site,
            ];

            if (!empty($upFile)) {
                if ($res['id'] > 0) {
                    if ($upFile != $res['attr_img']) { //更新图片之前将上一张图片删除
                        @unlink(storage_public($res['attr_img']));
                    }

                    $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('attribute_img'), $other, "UPDATE", "attr_id = '$attr_id' and attr_values = '" . $attr_values[$i]['values'] . "'");
                } else {
                    $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('attribute_img'), $other, "INSERT");
                }
            }
        }
    }
}

//添加或编辑关联商品ID，实现多商品共同描述内容
function get_add_edit_link_desc($linked_array, $type = 0, $id = 0)
{
    $adminru = get_admin_ru_id();

    if ($linked_array) {
        $arr['goods_id'] = '';
        for ($i = 0; $i < count($linked_array); $i++) {
            $arr['goods_id'] .= $linked_array[$i] . ',';
        }

        if ($id > 0) {
            $sql = "SELECT goods_id, ru_id FROM " . $GLOBALS['dsc']->table('link_goods_desc') . " WHERE id = '$id'";
            $row = $GLOBALS['db']->getRow($sql, true);
            $desc_goods_id = $row['goods_id'];
            $ru_id = $row['ru_id'];
        } else {
            $ru_id = $adminru['ru_id'];
        }

        $arr['goods_id'] = substr($arr['goods_id'], 0, -1);
        $other['goods_id'] = $arr['goods_id'];

        if (!empty($desc_goods_id) && $type != 1) {
            $other['goods_id'] = $other['goods_id'] . ',' . $desc_goods_id;

            $other['goods_id'] = explode(',', $other['goods_id']);
            $other['goods_id'] = array_unique($other['goods_id']);
            $other['goods_id'] = implode(',', $other['goods_id']);
        }

        $other['ru_id'] = $adminru['ru_id'];

        $sql = "SELECT goods_id FROM " . $GLOBALS['dsc']->table('link_desc_temporary') . " WHERE ru_id = '$ru_id'";
        $tgoods = $GLOBALS['db']->getOne($sql, true);

        if ($type == 1) { //删除 由右至左
            if (!empty($tgoods)) {
                $other['goods_id'] = get_del_in_val($tgoods, $other['goods_id']);
                $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('link_desc_temporary'), $other, "UPDATE", "1");
            } else {
                $other['goods_id'] = get_del_in_val($desc_goods_id, $other['goods_id']);
                $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('link_desc_temporary'), $other, "INSERT");
            }
        } else { //添加 由左至右

            if (!empty($tgoods)) {
                $other['goods_id'] .= ',' . $tgoods;
                $other['goods_id'] = get_other_goods_id($other['goods_id']);

                $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('link_desc_temporary'), $other, "UPDATE", "1");
            } else {
                $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('link_desc_temporary'), $other, "INSERT");
            }
        }
    }
}

//处理重复值
function get_other_goods_id($goods_id)
{
    $goods_id = explode(',', $goods_id);
    $goods_id = array_unique($goods_id);
    $goods_id = implode(',', $goods_id);

    return $goods_id;
}

//查询临时关联商品ID表信息
function get_linked_goods_desc($id = 0, $ru_id = 0)
{
    if ($id > 0) {
        $table = "link_goods_desc";
        $where = ' WHERE id = ' . $id;
    } else {
        $ru_id = !empty($ru_id) ? intval($ru_id) : 0;

        $table = "link_desc_temporary";
        $where = ' WHERE 1';
        $where .= " AND ru_id = '$ru_id' ";
    }

    $sql = "SELECT goods_id FROM " . $GLOBALS['dsc']->table($table) . $where;
    $goods_id = $GLOBALS['db']->getOne($sql, true);

    $arr = [];
    if (!empty($goods_id)) {
        $goods_id = explode(',', $goods_id);
        for ($i = 0; $i < count($goods_id); $i++) {
            $sql = "SELECT goods_name FROM " . $GLOBALS['dsc']->table('goods') . " WHERE goods_id = '" . $goods_id[$i] . "'";
            $goods_name = $GLOBALS['db']->getOne($sql, true);
            $arr[$i]['goods_id'] = $goods_id[$i];
            $arr[$i]['goods_name'] = $goods_name;
        }
    }

    return $arr;
}

//添加关联商品ID
function get_add_desc_goodsId($goods_id, $id)
{
    if (!empty($goods_id)) {
        $goods_id = explode(',', $goods_id);
        for ($i = 0; $i < count($goods_id); $i++) {
            $other = [
                'goods_id' => $goods_id[$i],
                'd_id' => $id,
            ];
            $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('link_desc_goodsid'), $other, "INSERT");
        }
    }
}

//去除商家主订单显示
function get_main_order_nodisplay($order_list)
{
    if ($order_list['orders']) {
        $arr = [];
        foreach ($order_list['orders'] as $key => $row) {
            $arr[$key] = $row;
            if ($arr[$key]['order_child'] > 0) {
                unset($arr[$key]);
            }
        }

        $order_list['orders'] = $arr;
    }

    return $order_list;
}

/**
 * 检查退换货原因是否有存在
 * @param type $cause_name
 * @return type
 */
function cause_exists($cause_name, $c_id = 0)
{
    $where = !empty($c_id) ? " AND cause_id <> '$c_id'" : '';
    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('return_cause') .
        " WHERE cause_name = '" . $cause_name . "'" . $where;
    return ($GLOBALS['db']->getOne($sql) > 0) ? true : false;
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

        //卖场 start
        $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
        if ($adminru['rs_id'] > 0) {
            $filter['rs_id'] = $adminru['rs_id'];
        }
        //卖场 end

        $where = 'WHERE 1 AND o.main_count = 0 ';

        if ($adminru['ru_id'] > 0) {
            $where .= " AND o.ru_id = '" . $adminru['ru_id'] . "' ";
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
            if (in_array($filter['return_type'], [1, 3])) {
                $where .= " AND r.return_type IN(1, 3)";
            }
        }

        $where_common = " AND o.ru_id = '" . $adminru['ru_id'] . "' ";

        //综合状态
        switch ($filter['composite_status']) {
            case CS_AWAIT_PAY:
                $where .= app(OrderService::class)->orderQuerySql('await_pay');
                break;

            case CS_AWAIT_SHIP:
                $where .= app(OrderService::class)->orderQuerySql('await_ship');
                break;

            case CS_FINISHED:
                $where .= app(OrderService::class)->orderQuerySql('finished');
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
                    $where .= " AND o.order_status NOT IN (2,3,4,7,8) " . $where_common;
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

        if ($GLOBALS['_CFG']['region_store_enabled']) {
            //卖场
            $filed = " (SELECT og.ru_id FROM " . $GLOBALS['dsc']->table('order_goods') . ' as og ' . " WHERE og.order_id = o.order_id LIMIT 1) ";
            $where .= get_rs_null_where($filed, $filter['rs_id']);
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

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = !isset($_REQUEST['store_search']) ? -1 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        $store_search = -1;
        $store_where = '';
        $store_search_where = '';
        if ($filter['store_search'] > -1) {
            if ($adminru['ru_id'] == 0) {
                if ($filter['store_search'] > 0) {
                    $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

                    if ($store_type) {
                        $store_search_where = "AND msi.shopNameSuffix = '$store_type'";
                    }

                    if ($filter['store_search'] == 1) {
                        $where .= " AND o.ru_id = '" . $filter['merchant_id'] . "' ";
                    } elseif ($filter['store_search'] == 2) {
                        $store_where .= " AND msi.rz_shopName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%'";
                    } elseif ($filter['store_search'] == 3) {
                        $store_where .= " AND msi.shoprz_brandName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%' " . $store_search_where;
                    }

                    if ($filter['store_search'] > 1) {
                        $where .= " AND (SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('merchants_shop_information') . ' AS msi ' .
                            " WHERE msi.user_id = o.ru_id $store_where) > 0 ";
                    }
                } else {
                    $store_search = 0;
                }
            }
        }
        //管理员查询的权限 -- 店铺查询 end

        /* 记录总数 */
        if ($filter['user_name']) {
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('order_return') . " AS o ," .
                $GLOBALS['dsc']->table('users') . " AS u " . $where;
        } else {
            if ($filter['seller_list']) {
                $where .= " AND o.ru_id > 0 ";
            } else {
                $where .= " AND o.ru_id = 0 ";
            }

            if ($filter['order_referer']) {
                if ($filter['order_referer'] == 'pc') {
                    $where .= " AND o.referer NOT IN ('mobile','touch','ecjia-cashdesk') ";
                } else {
                    $where .= " AND o.referer = '$filter[order_referer]' ";
                }
            }

            $sql = "SELECT COUNT(DISTINCT ret_id) FROM " . $GLOBALS['dsc']->table('order_return') . " AS r, " . $GLOBALS['dsc']->table('order_info') . " as o " . $where . " AND r.order_id = o.order_id";
        }

        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        $select = '';
        if (CROSS_BORDER === true) // 跨境多商户
        {
            $select = " r.return_rate_price, ";
        }

        /* 查询 */
        $sql = "SELECT DISTINCT r.ret_id ,o.order_id, o.order_sn, o.add_time, o.order_status, o.shipping_status, o.order_amount, o.money_paid, o.goods_amount, o.discount," .
            " o.ru_id, o.pay_status, o.consignee, o.email, o.tel, o.extension_code, o.extension_id," .
            " r.rec_id, r.address , r.back , r.exchange ,r.attr_val , r.cause_id , r.apply_time , r.should_return , r.actual_return , r.remark , r.address , o.sign_time ,r.return_status , r.refound_status , " . $select .
            " r.return_type, r.addressee, r.phone, r.return_sn, r.refund_type, r.return_shipping_fee, " .
            "(" . app(OrderService::class)->orderAmountField('o.') . ") AS total_fee, " .
            "IFNULL(u.user_name, '" . $GLOBALS['_LANG']['anonymous'] . "') AS buyer " .
            "FROM " . $GLOBALS['dsc']->table('order_return') . " AS r " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('order_info') . " AS o ON r.order_id = o.order_id " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('users') . " AS u ON u.user_id=o.user_id  " . $where .
            " ORDER BY $filter[sort_by] $filter[sort_order] " .
            " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";

        foreach (['order_sn', 'consignee', 'email', 'address', 'zipcode', 'tel', 'user_name'] as $val) {
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
        if ($value['discount'] > 0) {
            $discount_percent = $value['discount'] / $value['goods_amount'];
            $row[$key]['discount_percent_decimal'] = number_format($discount_percent, 2, '.', '');
            $row[$key]['discount_percent'] = $row[$key]['discount_percent_decimal'] * 100;
        } else {
            $row[$key]['discount_percent_decimal'] = 0;
            $row[$key]['discount_percent'] = 0;
        }

        $row[$key]['return_pay_status'] = $value['refound_status'];

        $row[$key]['formated_order_amount'] = price_format($value['order_amount']);
        $row[$key]['formated_money_paid'] = price_format($value['money_paid']);
        $row[$key]['formated_total_fee'] = price_format($value['total_fee']);
        $row[$key]['short_order_time'] = local_date('m-d H:i', $value['add_time']);
        $row[$key]['apply_time'] = local_date('m-d H:i', $value['apply_time']);
        $row[$key]['sign_time'] = local_date('m-d H:i', $value['sign_time']);
        $row[$key]['user_name'] = app(MerchantCommonService::class)->getShopName($value['ru_id'], 1); //ecmoban模板堂 --zhuo

        $should_return = $value['should_return'];
        if (CROSS_BORDER === true) // 跨境多商户
        {
            $should_return = $value['should_return'] + $value['return_rate_price'];
        }

        $row[$key]['discount_amount'] = number_format($value['should_return'] * $row[$key]['discount_percent_decimal'], 2, '.', ''); //折扣金额
        $row[$key]['formated_discount_amount'] = price_format($row[$key]['discount_amount']);
        $row[$key]['formated_should_return'] = price_format($should_return - $row[$key]['discount_amount']);

        $sql = "SELECT return_number, refound FROM " . $GLOBALS['dsc']->table('return_goods') . " WHERE rec_id = '" . $value['rec_id'] . "' LIMIT 1";
        $return_goods = $GLOBALS['db']->getRow($sql);

        if ($return_goods) {
            $return_number = $return_goods['return_number'];
        } else {
            $return_number = 0;
        }

        //获取订单总数
        $sql = "SELECT COUNT(goods_number)  FROM" . $GLOBALS['dsc']->table('order_goods') . "WHERE order_id = '" . $value['order_id'] . "'";
        $all_goods_number = $GLOBALS['db']->getOne($sql);

        //如果订单只有一个商品  折扣金额为全部折扣  否则按折扣比例计算
        if ($return_number == $all_goods_number) {
            $row[$key]['discount_amount'] = number_format($value['discount'], 2, '.', '');
        } else {
            $row[$key]['discount_amount'] = number_format($value['should_return'] * $row[$key]['discount_percent_decimal'], 2, '.', ''); //折扣金额
        }

        $row[$key]['formated_discount_amount'] = price_format($row[$key]['discount_amount']);
        $row[$key]['formated_should_return'] = price_format($should_return - $row[$key]['discount_amount']);

        $row[$key]['return_number'] = $return_number;
        $row[$key]['address_detail'] = app(UserAddressService::class)->getUserRegionAddress($value['ret_id'], '', 1);

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
            $row[$key]['buyer'] = app(DscRepository::class)->stringToStar($row[$key]['buyer']);
            $row[$key]['phone'] = app(DscRepository::class)->stringToStar($value['phone']);
        }
    }

    $arr = ['orders' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

    return $arr;
}

//商创版--后台程序开发 end

/** by li
 * 记录降价通知，邮件发送情况
 *
 * @access  public
 * @param string $sn 数据的唯一值
 * @param string $action 操作的类型
 * @param string $content 操作的内容
 * @return  void
 */
function notice_log($goods_id, $email, $send_ok, $send_type)
{
    $sql = 'INSERT INTO ' . $GLOBALS['dsc']->table('notice_log') . ' (goods_id, email, send_ok, send_time, send_type) ' .
        " VALUES ('$goods_id', '$email', '$send_ok', '" . gmtime() . "', '$send_type')";
    $GLOBALS['db']->query($sql);
}

//体验产品函数 --ecmoban by zhuo
function get_invite_Instantiation($sc_contents = '')
{
    $row = explode('-', $sc_contents);

    $arr['invite_code'] = $row[0];
    $arr['active_time'] = $row[1];
    $arr['end_time'] = $row[2];

    return $arr;
}

//删除商家信息 start
function get_delete_seller_info($table = '', $where = '')
{
    if (!empty($table) && !empty($where)) {
        $sql = "DELETE FROM " . $GLOBALS['dsc']->table($table) . " WHERE $where";
        $GLOBALS['db']->query($sql);
    }
}

//删除商家订单
function get_seller_delete_order_list($ru_id)
{
    $sql = "SELECT order_id FROM " . $GLOBALS['dsc']->table('order_goods') . " WHERE ru_id = '$ru_id'";
    $order_id = $GLOBALS['db']->getOne($sql);

    $sql = "SELECT ret_id FROM " . $GLOBALS['dsc']->table('order_return') . " WHERE order_id = '$order_id'";
    $ret_list = $GLOBALS['db']->getAll($sql);

    foreach ($ret_list as $key => $row) {
        $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['dsc']->table('return_goods') . " WHERE rec_id = '" . $row['rec_id'] . "'");
        $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['dsc']->table('return_action') . " WHERE ret_id = '" . $row['ret_id'] . "'");
    }

    $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['dsc']->table('order_return') . " WHERE order_id = '$order_id'");
    $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['dsc']->table('order_info') . " WHERE order_id = '$order_id'");
    $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['dsc']->table('order_goods') . " WHERE ru_id = '$ru_id'");
}

//删除商家商品
function get_seller_delete_goods_list($ru_id)
{
    get_delete_seller_info('goods', "user_id = '$ru_id'"); //删除商家商品


    //删除商家属性
    $sql = "SELECT cat_id FROM " . $GLOBALS['dsc']->table('goods_type') . " WHERE user_id = '$ru_id'";
    $goods_type = $GLOBALS['db']->getAll($sql);

    foreach ($goods_type as $key => $row) {
        $sql = "SELECT attr_id FROM " . $GLOBALS['dsc']->table('attribute') . " WHERE cat_id = '" . $row['cat_id'] . "'";
        $attribute_list = $GLOBALS['db']->getAll($sql);

        foreach ($attribute_list as $row) {
            $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['dsc']->table('goods_attr') . " WHERE attr_id = '" . $row['attr_id'] . "'");
        }
    }
}

//删除商家信息 end

/*
 * 查询即将到期的确认收货订单
 */
function get_order_detection_list($is_ajax = 0)
{
    $adminru = get_admin_ru_id();
    $where = ' WHERE 1 AND o.main_count = 0 ';

    $noTime = gmtime();
    $result = get_filter();
    if ($result === false) {
        /* 过滤信息 */
        $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
        if (isset($_REQUEST['consignee']) && !empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
            $_REQUEST['consignee'] = json_str_iconv($_REQUEST['consignee']);
        }
        $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $filter['order_id_list'] = isset($_REQUEST['order_id_list']) ? addslashes($_REQUEST['order_id_list']) : '';
        $filter['order_id'] = '';

        //卖场 start
        $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
        if ($adminru['rs_id'] > 0) {
            $filter['rs_id'] = $adminru['rs_id'];
        }
        //卖场 end

        if ($GLOBALS['_CFG']['region_store_enabled']) {
            //卖场
            $filed = " (SELECT og.ru_id FROM " . $GLOBALS['dsc']->table('order_goods') . ' as og ' . " WHERE og.order_id = o.order_id LIMIT 1) ";
            $where .= get_rs_null_where($filed, $filter['rs_id']);
        }

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = !isset($_REQUEST['store_search']) ? -1 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';
        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识

        $store_search = -1;
        $store_where = '';
        $store_search_where = '';
        if ($filter['store_search'] > -1) {
            if ($adminru['ru_id'] == 0) {
                if ($filter['store_search'] > 0) {
                    $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

                    if ($store_type) {
                        $store_search_where = "AND msi.shopNameSuffix = '$store_type'";
                    }

                    if ($filter['store_search'] == 1) {
                        $where .= " AND o.ru_id = '" . $filter['merchant_id'] . "' ";
                    } elseif ($filter['store_search'] == 2) {
                        $store_where .= " AND msi.rz_shopName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%'";
                    } elseif ($filter['store_search'] == 3) {
                        $store_where .= " AND msi.shoprz_brandName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%' " . $store_search_where;
                    }

                    if ($filter['store_search'] > 1) {
                        $where .= " AND (SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('merchants_shop_information') . ' AS msi ' .
                            " WHERE msi.user_id = o.ru_id $store_where) > 0 ";
                    }
                } else {
                    $store_search = 0;
                }
            }
        }
        //管理员查询的权限 -- 店铺查询 end

        if ($filter['order_sn']) {
            $where .= " AND o.order_sn LIKE '%" . mysql_like_quote($filter['order_sn']) . "%'";
        }
        if ($filter['consignee']) {
            $where .= " AND o.consignee LIKE '%" . mysql_like_quote($filter['consignee']) . "%'";
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

        if ($store_search == 0 && $adminru['ru_id'] == 0) {
            $where_store = " AND o.ru_id = 0";
        } else {
            $where_store = '';
        }

        if ($is_ajax == 1) {
            if ($filter['order_id_list']) {
                $sql = "UPDATE " . $GLOBALS['dsc']->table('order_info') .
                    " SET `order_status` = '" . OS_SPLITED . "', `shipping_status` = '" . SS_RECEIVED . "',`pay_status` = '" . PS_PAYED . "' " .
                    " WHERE shipping_status = 1 AND order_id " . db_create_in($filter['order_id_list']);
                $GLOBALS['db']->query($sql);

                /* 记录log */
                $sql = "SELECT order_sn,order_status,pay_status,confirm_take_time FROM " . $GLOBALS['dsc']->table('order_info') . " WHERE order_id " . db_create_in($filter['order_id_list']);
                $order_arr = $GLOBALS['db']->getAll($sql);
                foreach ($order_arr as $key => $val) {
                    /* 记录log */
                    order_action($val['order_sn'], $val['order_status'], SS_RECEIVED, $val['pay_status'], '', session('admin_name'), 0, $val['confirm_take_time']);
                }
            }

            $arr = ['filter' => $filter];
            return $arr;
        } elseif ($is_ajax == 2) {
            $where .= " AND o.shipping_status = 2 ";
            $where .= " AND (o.shipping_time + o.auto_delivery_time * 24 * 3600) <= '$noTime'";
            $filter['page_size'] = 1;
        } elseif ($is_ajax == 3) {
            $where .= " AND o.shipping_status = 1 ";
            $where .= " AND (o.shipping_time + o.auto_delivery_time * 24 * 3600) <= '$noTime'";
        } else {
            $where .= " AND o.shipping_status = 1 ";
        }

        if ($filter['order_id_list']) {
            $where .= " AND o.order_id IN(" . $filter['order_id_list'] . ") ";
        }

        if ($filter['seller_list']) {
            $where .= " AND o.ru_id > 0 ";
        } else {
            $where .= " AND o.ru_id = 0 ";
        }

        $sql = "SELECT COUNT(DISTINCT o.order_id) FROM " . $GLOBALS['dsc']->table('order_info') . " AS o " .
            $where . $where_store;

        $record_count = $GLOBALS['db']->getOne($sql);

        $filter['record_count'] = $record_count;
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT ifnull(bai.is_stages,0) is_stages, o.order_id, o.user_id, o.main_order_id, o.order_sn, o.ru_id, o.add_time, o.order_status, o.shipping_status, o.order_amount, o.money_paid, o.is_delete," .
            "o.shipping_time, o.auto_delivery_time, o.pay_status, o.consignee, o.address, o.email, o.tel, o.mobile, o.extension_code, o.extension_id, o.goods_amount, " .
            "(" . app(OrderService::class)->orderAmountField('o.') . ") AS total_fee, o.tax, o.shipping_fee, o.insure_fee, o.pay_fee, o.pack_fee, o.card_fee, o.bonus, o.integral_money, o.coupons, o.discount, " .
            "IFNULL(u.user_name, '" . $GLOBALS['_LANG']['anonymous'] . "') AS buyer, o.auto_delivery_time, o.money_paid, o.surplus, o.rate_fee " .
            " FROM " . $GLOBALS['dsc']->table('order_info') . " AS o " .
            " LEFT JOIN " . $GLOBALS['dsc']->table('users') . " AS u ON u.user_id=o.user_id " .
            " LEFT JOIN " . $GLOBALS['dsc']->table('baitiao_log') . " AS bai ON o.order_id=bai.order_id " .
            $where . $where_store .
            " ORDER BY $filter[sort_by] $filter[sort_order] " .
            " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";

        foreach (['order_sn', 'consignee', 'email', 'address', 'zipcode', 'tel', 'user_name'] as $val) {
            $filter[$val] = isset($filter[$val]) ? stripslashes($filter[$val]) : '';
        }

        set_filter($filter, $sql);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }

    $row = $GLOBALS['db']->getAll($sql);

    $overtime_order = '';

    /* 格式话数据 */
    foreach ($row as $key => $value) {
        $row[$key]['ru_id'] = $value['ru_id'];
        $row[$key]['formated_order_amount'] = price_format($value['order_amount']);
        $row[$key]['formated_money_paid'] = price_format($value['money_paid']);
        $row[$key]['formated_total_fee'] = price_format($value['total_fee']);
        $row[$key]['short_order_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['add_time']);

        $auto_confirm_time = $value['shipping_time'] + $value['auto_delivery_time'] * 3600 * 24;
        $row[$key]['auto_confirm_time'] = local_date($GLOBALS['_CFG']['time_format'], ($auto_confirm_time));

        /* 取得区域名 */
        $sql = "SELECT concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''), " .
            "'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region " .
            "FROM " . $GLOBALS['dsc']->table('order_info') . " AS o " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('region') . " AS c ON o.country = c.region_id " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('region') . " AS p ON o.province = p.region_id " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('region') . " AS t ON o.city = t.region_id " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('region') . " AS d ON o.district = d.region_id " .
            "WHERE o.order_id = '" . $value['order_id'] . "'";
        $row[$key]['region'] = $GLOBALS['db']->getOne($sql);

        $row[$key]['user_name'] = app(MerchantCommonService::class)->getShopName($value['ru_id'], 1);

        $order_id = $value['order_id'];

        $order_child = OrderInfo::where('main_order_id', $order_id)->count();
        $row[$key]['order_child'] = $order_child;

        $child_list = OrderInfo::select('order_sn')->where('main_order_id', $order_id)->get();
        $child_list = $child_list ? $child_list->toArray() : [];

        $row[$key]['child_list'] = $child_list;

        if ($value['order_status'] == OS_INVALID || $value['order_status'] == OS_CANCELED) {
            /* 如果该订单为无效或取消则显示删除链接 */
            $row[$key]['can_remove'] = 1;
        } else {
            $row[$key]['can_remove'] = 0;
        }

        if ($auto_confirm_time <= $noTime) {
            $row[$key]['is_auto_confirm'] = 1;
        } else {
            $row[$key]['is_auto_confirm'] = 0;
        }

        $row[$key]['new_shipping_status'] = $GLOBALS['_LANG']['ss'][$value['shipping_status']];

        if ($auto_confirm_time <= $noTime) {
            $overtime_order = $value['order_id'];
        }

        if ($overtime_order) {
            $filter['order_id'] .= $overtime_order . ",";
        }

        if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
            $row[$key]['buyer'] = app(DscRepository::class)->stringToStar($value['buyer']);
            $row[$key]['mobile'] = app(DscRepository::class)->stringToStar($value['mobile']);
        }
    }

    if ($filter['order_id']) {
        $filter['order_id'] = substr($filter['order_id'], 0, -1);
    }

    $arr = ['orders' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    return $arr;
}

/*
 * 获取商品品牌
 */
function get_goods_brand_info($brand_id = 0)
{
    $sql = "SELECT * FROM " . $GLOBALS['dsc']->table('brand') . " WHERE brand_id = '$brand_id' LIMIT 1";
    return $GLOBALS['db']->getRow($sql);
}

//设置刷新后显示的页面 by wu
function set_current_page()
{
    $domain = $GLOBALS['dsc']->http() . request()->server('SERVER_NAME');
    $script = request()->server('PHP_SELF');
    $query = request()->server('QUERY_STRING');
    $app = basename($script);
    //首页不计
    if ($app != 'index.php') {
        $page_url = $domain . $script;
        if ($query) {
            parse_str($query, $output);
            $page_url = $page_url . '?' . $query;
        }

        //部分操作也不能保存，如下载、上传、导出、退出、登录等页面，或用户自定义页面，填入下数组中
        $act_status = 1;
        $act_array = ['download', 'upload', 'export', 'signin', 'login', 'logout', 'print_index', 'clear_cache'];
        if (isset($output['act']) && in_array($output['act'], $act_array)) {
            $act_status = 0;
        }

        if (isset($output['act']) && (preg_match("/insert/", $output['act']) || preg_match("/update/", $output['act']))) {
            $act_status = 0;
        }

        //设置session，判断是否为ajax操作
        if ((empty(session('current_page')) || session('current_page') != $page_url) && empty($output['is_ajax']) && $act_status) {
            session([
                'current_page' => $page_url
            ]);
        }
    } else {
        if (empty(session('current_page'))) {
            session([
                'current_page' => 'index.php?act=main'
            ]);
        }
    }

    $current_page = session()->has('current_page') ? session('current_page') : 1;

    $GLOBALS['smarty']->assign('current_page', $current_page);
}

/* 设置商品属性 by wu */
function set_goods_attribute($goods_type = 0, $goods_id = 0, $goods_model = 0)
{
    $admin_id = get_admin_id();

    //获取属性列表
    $sql = " SELECT a.attr_id, a.attr_name, a.attr_input_type, a.attr_type, a.attr_values " .
        " FROM " . $GLOBALS['dsc']->table('attribute') . " AS a " .
        " WHERE a.cat_id = " . intval($goods_type) . " AND a.cat_id <> 0 " .
        " ORDER BY a.sort_order, a.attr_type, a.attr_id ";
    $attribute_list = $GLOBALS['db']->getAll($sql);

    $attr_where = '';
    if (empty($goods_id)) {
        $attr_where = " AND admin_id = '$admin_id'";
    }

    //获取商品属性
    $sql = " SELECT v.goods_attr_id, v.attr_id, v.attr_value, v.attr_price, v.attr_sort, v.attr_checked, v.attr_img_flie, v.attr_gallery_flie  " .
        " FROM " . $GLOBALS['dsc']->table('goods_attr') . " AS v " .
        " WHERE v.goods_id = '$goods_id' $attr_where ORDER BY v.attr_sort, v.goods_attr_id ";
    $attr_list = $GLOBALS['db']->getAll($sql);

    if ($attribute_list) {
        foreach ($attribute_list as $key => $val) {
            $is_selected = 0; //属性是否被选择
            $this_value = ""; //唯一属性的值

            if ($val['attr_type'] > 0) {
                if ($val['attr_values']) {
                    $attr_values = preg_replace(['/\r\n/', '/\n/', '/\r/'], ",", $val['attr_values']); //替换空格回车换行符为英文逗号
                    $attr_values = explode(',', $attr_values);
                } else {
                    $sql = "SELECT attr_value FROM " . $GLOBALS['dsc']->table('goods_attr') . " WHERE goods_id = '$goods_id' AND attr_id = '" . $val['attr_id'] . "' ORDER BY attr_sort, goods_attr_id";
                    $attr_values = $GLOBALS['db']->getAll($sql);

                    $values_list = app(BaseRepository::class)->getKeyPluck($attr_values, 'attr_value');

                    $attribute_list[$key]['attr_values'] = $values_list;
                    $attr_values = $attribute_list[$key]['attr_values'];
                }

                $attr_values_arr = [];

                if ($attr_values) {
                    for ($i = 0; $i < count($attr_values); $i++) {
                        $sql = "SELECT goods_attr_id, attr_price, attr_sort FROM " . $GLOBALS['dsc']->table('goods_attr') . " WHERE goods_id = '$goods_id' AND attr_value = '" . $attr_values[$i] . "' AND attr_id = '" . $val['attr_id'] . "' LIMIT 1";
                        $goods_attr = $GLOBALS['db']->getRow($sql);
                        $attr_values_arr[$i] = ['is_selected' => 0, 'goods_attr_id' => $goods_attr['goods_attr_id'], 'attr_value' => $attr_values[$i], 'attr_price' => $goods_attr['attr_price'], 'attr_sort' => $goods_attr['attr_sort']];
                    }
                }

                $attribute_list[$key]['attr_values_arr'] = $attr_values_arr;
            }

            foreach ($attr_list as $k => $v) {
                if ($val['attr_id'] == $v['attr_id']) {
                    $is_selected = 1;
                    if ($val['attr_type'] == 0) {
                        $this_value = $v['attr_value'];
                    } else {
                        foreach ($attribute_list[$key]['attr_values_arr'] as $a => $b) {
                            if ($goods_id) {
                                if ($b['attr_value'] == $v['attr_value']) {
                                    $attribute_list[$key]['attr_values_arr'][$a]['is_selected'] = 1;
                                }
                            } else {
                                if ($b['attr_value'] == $v['attr_value']) {
                                    $attribute_list[$key]['attr_values_arr'][$a]['is_selected'] = 1;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            $attribute_list[$key]['is_selected'] = $is_selected;
            $attribute_list[$key]['this_value'] = $this_value;
            if ($val['attr_input_type'] == 1) {
                $attr_values = preg_replace(['/\r\n/', '/\n/', '/\r/'], ",", $val['attr_values']); //替换空格回车换行符为英文逗号
                $attribute_list[$key]['attr_values'] = explode(',', $attr_values);
            }
            if ($val['attr_type'] == 0) {
                $goods_attr = $GLOBALS['db']->getRow("SELECT goods_attr_id FROM " . $GLOBALS['dsc']->table('goods_attr') . " WHERE goods_id = '$goods_id' AND attr_value = '$this_value' AND attr_id = '" . $val['attr_id'] . "' LIMIT 1");
                $attribute_list[$key]['goods_attr_id'] = $goods_attr['goods_attr_id'];
            }
        }
    }

    $attribute_list = app(CommonManageService::class)->getNewGoodsAttr($attribute_list);

    $GLOBALS['smarty']->assign('goods_id', $goods_id);
    $GLOBALS['smarty']->assign('goods_model', $goods_model);

    $GLOBALS['smarty']->assign('attribute_list', $attribute_list);
    $goods_attribute = $GLOBALS['smarty']->fetch('library/goods_attribute.lbi');

    $attr_spec = $attribute_list['spec'];

    if ($attr_spec) {
        $arr['is_spec'] = 1;
    } else {
        $arr['is_spec'] = 0;
    }

    /* 过滤空数据 */
    if ($attr_spec) {
        foreach ($attr_spec as $key => $val) {
            if (!$val['attr_values']) {
                unset($attr_spec[$key]);
            } elseif ($val['attr_values_arr']) {
                foreach ($val['attr_values_arr'] as $k => $v) {
                    if (!$v['goods_attr_id']) {
                        unset($attr_spec[$key]['attr_values_arr'][$k]);
                    }
                }
            }

            if (isset($attr_spec[$key]['attr_values_arr']) && !$attr_spec[$key]['attr_values_arr']) {
                unset($attr_spec[$key]);
            }
        }
    }

    $GLOBALS['smarty']->assign('attr_spec', $attr_spec);
    $GLOBALS['smarty']->assign('goods_attr_price', $GLOBALS['_CFG']['goods_attr_price']);
    $goods_attr_gallery = $GLOBALS['smarty']->fetch('library/goods_attr_gallery.lbi');

    $arr['goods_attribute'] = $goods_attribute;
    $arr['goods_attr_gallery'] = $goods_attr_gallery;

    return $arr;
}

/**
 * 属性零时表录入
 *
 * @access  public
 * @param string $goods_id 商品id
 * @param float $attr_info 属性数组
 * @param mix $insure 保价比例
 * @return  float
 *
 * @return  array
 */
function insert_attr_changelog($goods_id = 0, $attr_info = [], $goods_model = 0, $warehouse_id = 0, $region_id = 0, $city_id = 0)
{
    if (!empty($attr_info)) {
        //初始化初始值
        $changelog = [
            'goods_id' => $goods_id,
            'admin_id' => session('admin_id')
        ];
        //处理属性id  以“|”隔开
        $goods_attr = array_reduce($attr_info, function ($result, $v) {
            $result .= $v["goods_attr_id"] . "|";
            return $result;
        });

        $goods_attr = substr($goods_attr, 0, strlen($goods_attr) - 1);
        $changelog['goods_attr'] = $goods_attr;
        if ($goods_model == 1) {
            $changelog['warehouse_id'] = $region_id;
        } elseif ($goods_model == 2) {
            $changelog['warehouse_id'] = $warehouse_id;
            $changelog['area_id'] = $region_id;
            $changelog['city_id'] = $city_id;
        }

        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('products_changelog'), $changelog, 'INSERT');
        $product_info = get_product_info_by_attr($goods_id, $attr_info, $goods_model, $region_id, 1, $city_id); //获取属性表零时数据
        return $product_info;
    }
    return '';
}

//通过一组属性获取货品的相关信息 by wu
function get_product_info_by_attr($goods_id = 0, $attr_arr = [], $goods_model = 0, $region_id = 0, $changelog = 0, $city_id = 0)
{
    if (!empty($attr_arr)) {
        $where = "";
        //判断商品类型
        if ($goods_model == 1) {
            $table = "products_warehouse";
            if ($region_id) {
                $where .= " AND warehouse_id = '$region_id' ";
            }
        } elseif ($goods_model == 2) {
            $table = "products_area";
            $where .= " AND area_id = '$region_id' ";
            if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                $where .= " AND city_id = '$city_id' ";
            }
        } else {
            $table = "products";
        }

        //获取零时表数据
        if ($changelog == 1) {
            $table = "products_changelog";//属性临时表
            $where .= " AND admin_id = '" . session('admin_id') . "'";//调取当前管理员添加的数据
        }
        //获取属性组合
        $attr = [];
        foreach ($attr_arr as $key => $val) {
            if ($val && $val['goods_attr_id']) {
                $attr[] = $val['goods_attr_id'];
            }
        }

        $set = "";
        foreach ($attr as $key => $val) {
            $set .= " AND FIND_IN_SET('$val', REPLACE(goods_attr, '|', ',')) ";
        }
        $sql = " SELECT * FROM " . $GLOBALS['dsc']->table($table) . " WHERE 1 $set AND goods_id = '$goods_id' " . $where . " LIMIT 1 ";
        $product_info = $GLOBALS['db']->getRow($sql);
        return $product_info;
    } else {
        return false;
    }
}

//获取所有仓库地区列表 by wu
function get_warehouse_region()
{
    $sql = "select region_id, region_name from " . $GLOBALS['dsc']->table('region_warehouse') . " where 1 and region_type = '0'";
    $warehouse_list = $GLOBALS['db']->getAll($sql);

    foreach ($warehouse_list as $key => $val) {
        $sql = "select region_id, region_name from " . $GLOBALS['dsc']->table('region_warehouse') . " where parent_id = '$val[region_id]'";
        $warehouse_list[$key]['area_list'] = $GLOBALS['db']->getAll($sql);
    }

    return $warehouse_list;
}

//消费满N金额减N减额
function get_goods_payfull($is_fullcut = 0, $full, $reduce, $id, $goods_id, $table, $type = 0)
{
    if ($is_fullcut) {
        if (count($reduce) > 0) {
            for ($i = 0; $i < count($reduce); $i++) {
                if (!empty($full[$i])) {
                    $full[$i] = trim($full[$i]);
                    $full[$i] = floatval($full[$i]);
                    $reduce[$i] = trim($reduce[$i]);
                    $reduce[$i] = floatval($reduce[$i]);

                    //添加或修改 start
                    if ($type == 1) {
                        $other = [
                            'sfull' => $full[$i],
                            'sreduce' => $reduce[$i]
                        ];
                    } else {
                        $other = [
                            'cfull' => $full[$i],
                            'creduce' => $reduce[$i]
                        ];
                    }

                    if (!empty($id[$i])) {
                        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table), $other, 'UPDATE', "id='" . $id[$i] . "'");
                    } else {
                        $other['goods_id'] = $goods_id;
                        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table), $other, 'INSERT');
                    }
                    //添加或修改 end
                }
            }
        }
    } else {
        $sql = "DELETE FROM " . $GLOBALS['dsc']->table($table) . " WHERE goods_id = '$goods_id'";
        $GLOBALS['db']->query($sql);
    }
}

/* 查看教程开关 */
function open_study()
{
    $res = config('shop.open_study');
    return $res;
}

/* 区分自营和店铺 */
function self_seller($filename = "index.php", $act = "list", $param_str = '')
{
    //过滤参数seller_list
    $url = str_replace('&seller_list=0', '', $filename);
    $url = str_replace('&seller_list=1', '', $url);
    $filename = str_replace('&uselastfilter=1', '', $url);

    $result = get_filter($param_str);//获取过滤条件内的设置
    if ($result && isset($result['filter']['seller_list'])) {
        $_REQUEST['seller_list'] = $result['filter']['seller_list'];
    }

    $seller_order = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? intval($_REQUEST['seller_list']) : 0;  //商家和自营订单标识

    if ($seller_order == 3) {
        $seller_order = 3;
        $seller_list = "&seller_list=3";
    } else {
        if ($seller_order) {
            $seller_order = 1;
            $seller_list = "&seller_list=1";
        } else {
            $seller_order = 0;
            $seller_list = '&seller_list=0';
        }
    }

    $GLOBALS['smarty']->assign('seller_list', $seller_list);
    /* 订单内平台、店铺区分 */
    $url = !empty($filename) ? $filename : "";
    $GLOBALS['smarty']->assign('common_tabs', ['info' => $seller_order, 'url' => $url]);
    $GLOBALS['smarty']->assign('seller_order', $seller_order);
    $GLOBALS['smarty']->assign('filename', $filename);
}

//删除无用属性
function delete_invalid_goods_attr($attr_group = [], $goods_id = 0, $goods_model = 0, $region_id = 0, $city_id = 0)
{
    $admin_id = get_admin_id();
    $where = " AND admin_id = '$admin_id'"; //调取当前管理员添加的数据

    //判断商品类型
    if ($goods_model == 1) {
        $where .= " AND warehouse_id = '$region_id' ";
    } elseif ($goods_model == 2) {
        if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
            $where .= " AND area_id = '$region_id' AND city_id = '$city_id' ";
        } else {
            $where .= " AND area_id = '$region_id' ";
        }
    }
    if (!empty($attr_group)) {
        $count_attr = count($attr_group[0]);

        //获取商品的所有零时数据
        $sql = " SELECT product_id,goods_attr FROM " . $GLOBALS['dsc']->table('products_changelog') . " WHERE 1 AND goods_id = '$goods_id' " . $where;
        $product_info = $GLOBALS['db']->getAll($sql);
        if (!empty($product_info)) {
            foreach ($product_info as $k => $v) {
                $goods_attr = explode('|', $v['goods_attr']);
                if (count($goods_attr) != $count_attr) {
                    $sql = "DELETE FROM" . $GLOBALS['dsc']->table('products_changelog') . "WHERE product_id = '" . $v['product_id'] . "'";
                    $GLOBALS['db']->query($sql);
                }
            }
        }
    }
}
