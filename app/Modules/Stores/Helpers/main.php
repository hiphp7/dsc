<?php

use App\Libraries\Image;
use App\Models\AdminLog;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderService;

/**
 * 系统提示信息
 *
 * @access      public
 * @param string      msg_detail      消息内容
 * @param int         msg_type        消息类型， 0消息，1错误，2询问
 * @param array       links           可选的链接
 * @param boolen $auto_redirect 是否需要自动跳转
 * @return      void
 */
function sys_msg($msg_detail, $msg_type = 0, $links = [], $auto_redirect = true)
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
    $log_info = $GLOBALS['_LANG']['log_action'][$action] . $GLOBALS['_LANG']['log_action'][$content] . ': ' . addslashes($sn);

    $ip_address = app(DscRepository::class)->dscIp();

    AdminLog::insert([
        'log_time' => gmtime(),
        'user_id' => session('seller_id'),
        'log_info' => stripslashes($log_info),
        'ip_address' => $ip_address
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
 * @param int $user_id 管理员编号
 * @param string $username 管理员姓名
 * @param int $store_id
 */
function set_admin_session($user_id = 0, $username = '', $store_id = 0)
{
    session([
        'store_user_id' => $user_id,
        'stores_id' => $store_id,
        'stores_name' => $username,
        'store_login_hash' => substr(strtoupper(md5(gmtime())), 0, 10) // 最后登录时间的加密字符串substr(strtoupper(md5(string)), 0, 10)
    ]);
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
 * 检查管理员权限
 *
 * @access  public
 * @param string $authz
 * @return  boolean
 */
function check_authz($authz)
{
    return (preg_match('/,*' . $authz . ',*/', session('seller_action_list')) || session('seller_action_list') == 'all');
}

/**
 * 检查管理员权限，返回JSON格式数剧
 *
 * @access  public
 * @param string $authz
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

    //ecmoban模板堂 --zhuo start
    $adminru = get_admin_ru_id();
    $ruCat = '';
    if ($adminru['ru_id'] > 0) {
        $ruCat = " where (user_id = '" . $adminru['ru_id'] . "' or is_public = 1) ";
    }
    //ecmoban模板堂 --zhuo end

    $position_list = [];
    $sql = 'SELECT position_id, position_name, ad_width, ad_height ' .
        'FROM ' . $GLOBALS['dsc']->table('ad_position') . $ruCat;
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
    $FCKeditor = '<input type="hidden" id="' . $input_name . '" name="' . $input_name . '" value="' . htmlspecialchars($input_value) . '" /><iframe id="' . $input_name . '_frame" src="' . __ROOT__ . STORES_PATH . "/" . "editor.php?item=" . $input_name . '" width="100%" height="' . $input_height . '" frameborder="0" scrolling="no"></iframe>';
    $GLOBALS['smarty']->assign('FCKeditor', $FCKeditor);
}

/**
 * 生成编辑器2
 * @param string  input_name  输入框名称
 * @param string  input_value 输入框值
 */
function create_html_editor2($input_name, $output_name, $input_value = '')
{
    $input_height = $GLOBALS['_CFG']['editing_tools'] == 'ueditor' ? 586 : 500;
    $FCKeditor = '<input type="hidden" id="' . $input_name . '" name="' . $input_name . '" value="' . htmlspecialchars($input_value) . '" /><iframe id="' . $input_name . '_frame" src="' . __ROOT__ . STORES_PATH . "/" . "editor.php?item=" . $input_name . '" width="100%" height="' . $input_height . '" frameborder="0" scrolling="no"></iframe>';
    $GLOBALS['smarty']->assign($output_name, $FCKeditor);
}

/**
 * 取得商品列表：用于把商品添加到组合、关联类、赠品类
 * @param object $filters 过滤条件
 */
function get_goods_list($filter)
{
    $filter->keyword = json_str_iconv($filter->keyword);
    $where = get_where_sql($filter); // 取得过滤条件

    /* 取得数据 */
    $sql = 'SELECT goods_id, goods_name, shop_price ' .
        'FROM ' . $GLOBALS['dsc']->table('goods') . ' AS g ' . $where;
    // .' LIMIT 50';

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
        if ($adminru['ru_id'] > 0) {
            $where .= " AND user_id = '" . $adminru['ru_id'] . "'";
        }
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
function area_list($region_id = 0)
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

        $area_arr[$idx] = $row;

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
 * @return  string
 */
function goods_type_list($selected, $goods_id = 0)
{
    //ecmoban模板堂 --zhuo start
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
            if ($adminru['ru_id'] > 0) {
                $ruCat = " and user_id = '" . $adminru['ru_id'] . "'";
            }
        }
    }
    //ecmoban模板堂 --zhuo end

    $sql = 'SELECT cat_id, cat_name FROM ' . $GLOBALS['dsc']->table('goods_type') . ' WHERE enabled = 1' . $ruCat;
    $res = $GLOBALS['db']->query($sql);

    $lst = '';
    foreach ($res as $row) {
        $lst .= "<option value='$row[cat_id]'";
        $lst .= ($selected == $row['cat_id']) ? ' selected="true"' : '';
        $lst .= '>' . htmlspecialchars($row['cat_name']) . '</option>';
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
 * 返回字符集列表数组
 *
 * @return array
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
 * @return \Illuminate\Http\JsonResponse|mixed
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
 *
 *
 * @access  public
 * @param
 * @return  void
 */
function make_json_result($content, $message = '', $append = [])
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
function make_json_result_too($content, $error = 0, $message = '', $append = [])
{
    return make_json_response($content, $error, $message, $append);
}

/**
 * 创建一个JSON格式的错误信息
 *
 * @access  public
 * @param string $msg
 * @return  void
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
    $url = asset('/assets/stores');
    $flag['img'] = '<img src="' . $url . 'images/' . ($filter['sort_order'] == "DESC" ? 'sort_desc.gif' : 'sort_asc.gif') . '"/>';

    return $flag;
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
            $val *= 1024;
        // no break
        case 'm':
            $val *= 1024;
        // no break
        case 'k':
            $val *= 1024;
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
function set_filter($filter, $sql, $param_str = '')
{
    $filterfile = basename(PHP_SELF, '.php');
    if ($param_str) {
        $filterfile .= $param_str;
    }

    cookie()->queue('ECSCP[lastfilterfile]', sprintf('%X', crc32($filterfile)), 10);

    // 过渡空值数组
    $filter = array_filter($filter);
    cookie()->queue('ECSCP[lastfilter]', urlencode(serialize($filter)), 10);
    cookie()->queue('ECSCP[lastfiltersql]', base64_encode($sql), 10);
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
            WHERE user_id = '" . session('seller_id') . "'
            LIMIT 0, 1";
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

    $file_dir = storage_public(DATA_DIR . $img_dir);
    if (!file_exists($file_dir)) {
        if (!make_dir($file_dir)) {
            /* 创建目录失败 */
            return false;
        }
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
function get_add_attr_values($attr_id, $type = 0, $list = [])
{
    $sql = "select attr_values from " . $GLOBALS['dsc']->table('attribute') . " where attr_id = '$attr_id'";
    $attr_values = $GLOBALS['db']->getOne($sql);

    if (!empty($attr_values)) {
        $attr_values = preg_replace("/\r\n/", ",", $attr_values); //替换空格回车换行符 为 英文逗号
        $attr_values = explode(',', $attr_values);

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
    if ($linked_array) {
        $arr['goods_id'] = '';
        for ($i = 0; $i < count($linked_array); $i++) {
            $arr['goods_id'] .= $linked_array[$i] . ',';
        }

        if ($id > 0) {
            $sql = "select goods_id from " . $GLOBALS['dsc']->table('link_goods_desc') . " where id = '$id'";
            $desc_goods_id = $GLOBALS['db']->getOne($sql);
        }

        $arr['goods_id'] = substr($arr['goods_id'], 0, -1);
        $other['goods_id'] = $arr['goods_id'];

        if (!empty($desc_goods_id) && $type != 1) {
            $other['goods_id'] = $other['goods_id'] . ',' . $desc_goods_id;

            $other['goods_id'] = explode(',', $other['goods_id']);
            $other['goods_id'] = array_unique($other['goods_id']);
            $other['goods_id'] = implode(',', $other['goods_id']);
        }

        $sql = "select goods_id from " . $GLOBALS['dsc']->table('link_desc_temporary') . " where 1 limit 0, 1";
        $tgoods = $GLOBALS['db']->getOne($sql);

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
function get_linked_goods_desc($id = 0)
{
    if ($id > 0) {
        $table = "link_goods_desc";
        $where = ' where id = ' . $id;
    } else {
        $table = "link_desc_temporary";
        $where = ' where 1';
    }

    $sql = "select goods_id from " . $GLOBALS['dsc']->table($table) . $where;
    $goods_id = $GLOBALS['db']->getOne($sql);

    $arr = [];
    if (!empty($goods_id)) {
        $goods_id = explode(',', $goods_id);
        for ($i = 0; $i < count($goods_id); $i++) {
            $sql = "select goods_name from " . $GLOBALS['dsc']->table('goods') . " where goods_id = '" . $goods_id[$i] . "'";
            $goods_name = $GLOBALS['db']->getOne($sql);
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

//批量添加分类
function get_bacth_category($cat_name, $cat, $ru_id)
{
    for ($i = 0; $i < count($cat_name); $i++) {
        if (!empty($cat_name)) {
            $cat['cat_name'] = $cat_name[$i];
            if ($GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('category'), $cat) !== false) {
                $cat_id = $GLOBALS['db']->insert_id();
                if ($cat['show_in_nav'] == 1) {
                    $vieworder = $GLOBALS['db']->getOne("SELECT max(vieworder) FROM " . $GLOBALS['dsc']->table('nav') . " WHERE type = 'middle'");
                    $vieworder += 2;
                    //显示在自定义导航栏中
                    $sql = "INSERT INTO " . $GLOBALS['dsc']->table('nav') .
                        " (name,ctype,cid,ifshow,vieworder,opennew,url,type)" .
                        " VALUES('" . $cat['cat_name'] . "', 'c', '$cat_id','1','$vieworder','0', '" . app(DscRepository::class)->buildUri('category', ['cid' => $cat_id], $cat['cat_name']) . "','middle')";
                    $GLOBALS['db']->query($sql);
                }

                if ($ru_id > 0) {
                    $parent = [
                        'cat_id' => $cat_id,
                        'user_id' => $ru_id,
                        'is_show' => $cat['is_show_merchants'],
                        'add_titme' => gmtime()
                    ];
                    $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('merchants_category'), $parent, 'INSERT');
                }

                admin_log($cat['cat_name'], 'add', 'category');   // 记录管理员操作
            }
        }
    }
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
            $where .= " AND r.return_type = '$filter[return_type]'";
        }

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
        $sql = "SELECT agency_id FROM " . $GLOBALS['dsc']->table('admin_user') . " WHERE user_id = '" . session('seller_id') . "'";
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

        if ($store_search == 0 && $adminru['ru_id'] == 0) {
            $where_store = " AND o.ru_id = 0 AND o.main_count = 0";
        } else {
            $where_store = '';
        }

        /* 记录总数 */
        if ($filter['user_name']) {
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('order_return') . " AS o ," .
                $GLOBALS['dsc']->table('users') . " AS u " . $where . $where_store;
        } else {
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('order_return') . " AS r, " . $GLOBALS['dsc']->table('order_info') . " as o " . $where . " AND r.order_id = o.order_id";
        }

        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT o.order_id, o.order_sn, o.add_time, o.order_status, o.shipping_status, o.order_amount, o.money_paid, og.ru_id," .
            "o.pay_status, o.consignee, o.address, o.email, o.tel, o.extension_code, o.extension_id," .
            "r.ret_id ,r.rec_id , r.back , r.exchange ,r.attr_val , r.cause_id , r.apply_time , r.should_return , r.actual_return , r.remark , r.address , o.sign_time ,r.return_status , r.refound_status , " .
            " r.return_type, r.addressee, r.phone, r.return_sn, " .
            "(" . app(OrderService::class)->orderAmountField('o.') . ") AS total_fee, " .
            "IFNULL(u.user_name, '" . $GLOBALS['_LANG']['anonymous'] . "') AS buyer " .
            "FROM " . $GLOBALS['dsc']->table('order_return') . " AS r " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('order_info') . " AS o ON r.order_id = o.order_id " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('order_goods') . " AS og ON og.order_id=o.order_id " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('users') . " AS u ON u.user_id=o.user_id  " . $where . $where_store .
            " GROUP BY r.ret_id ORDER BY $filter[sort_by] $filter[sort_order] " .
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
        $row[$key]['formated_order_amount'] = price_format($value['order_amount']);
        $row[$key]['formated_money_paid'] = price_format($value['money_paid']);
        $row[$key]['formated_total_fee'] = price_format($value['total_fee']);
        $row[$key]['short_order_time'] = local_date('m-d H:i', $value['add_time']);
        $row[$key]['apply_time'] = local_date('m-d H:i', $value['apply_time']);
        $row[$key]['sign_time'] = local_date('m-d H:i', $value['sign_time']);
        $row[$key]['user_name'] = app(MerchantCommonService::class)->getShopName($value['ru_id'], 1);

        $sql = "select return_number from " . $GLOBALS['dsc']->table('return_goods') . " where rec_id = '" . $value['rec_id'] . "'";
        $return_number = $GLOBALS['db']->getOne($sql);

        $row[$key]['return_number'] = $return_number;
        $row[$key]['address_detail'] = get_consignee_info($value['order_id'], $value['address']);

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
        } elseif ($value['return_type'] == 1) {
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

//查询插件权限
/*function get_merchants_permissions($admin_id = 0, $string = ''){
    $sql = "SELECT action_list FROM " .$GLOBALS['dsc']->table('admin_user'). " WHERE user_id = '$admin_id'";
    $action_list = $GLOBALS['db']->getOne($sql);

    if($action_list == 'all'){
        return 1;
    }else{
        $action_list = explode(',', $action_list);
        if(in_array($string, $action_list)){
            return 1;
        }else{
            return 0;
        }
    }
}*/

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

        foreach ($attribute_list as $arow) {
            $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['dsc']->table('goods_attr') . " WHERE attr_id = '" . $row['attr_id'] . "'");
        }
    }
}

//删除商家信息 end

//获取URL文件名称
function get_php_self($type = 0)
{
    $php_self = substr(PHP_SELF, strrpos(PHP_SELF, '/') + 1);

    if ($type == 1) {
        $self = explode('.', $php_self);
        $php_self = $self[0];
    }

    return $php_self;
}

/*
 * 查询即将到期的确认收货订单
 */
function get_order_detection_list($is_ajax = 0)
{
    //ecmoban模板堂 --zhuo start
    $adminru = get_admin_ru_id();
    $where = ' WHERE 1 AND o.main_count = 0 ';
    if ($adminru['ru_id'] > 0) {
        $where .= " AND o.ru_id = '" . $adminru['ru_id'] . "' ";
    }

    $noTime = gmtime();
    //ecmoban模板堂 --zhuo end
    $result = get_filter();
    if ($result === false) {
        /* 过滤信息 */
        $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
        if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
            $_REQUEST['consignee'] = json_str_iconv($_REQUEST['consignee']);
            //$_REQUEST['address'] = json_str_iconv($_REQUEST['address']);
        }
        $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

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
            $where_store = " AND o.ru_id = 0 AND o.main_count = 0";
        } else {
            $where_store = '';
        }

        if ($is_ajax == 1) {
            $sql = "UPDATE " . $GLOBALS['dsc']->table('order_info') .
                " SET `order_status` = '" . OS_SPLITED . "', `shipping_status` = '" . SS_RECEIVED . "',`pay_status` = '" . PS_PAYED . "' " .
                " WHERE shipping_status = 1 AND (add_time + auto_delivery_time * 24 * 3600) <= '$noTime'";
            $GLOBALS['db']->query($sql);

            return true;
        } elseif ($is_ajax == 2) {
            $where .= " AND o.shipping_status = 2 ";
            $where .= " AND (o.add_time + o.auto_delivery_time * 24 * 3600) <= '$noTime'";
            $filter['page_size'] = 1;
        } elseif ($is_ajax == 3) {
            $where .= " AND o.shipping_status = 1 ";
            $where .= " AND (o.add_time + o.auto_delivery_time * 24 * 3600) <= '$noTime'";
        } else {
            $where .= " AND o.shipping_status = 1 ";
        }

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('order_info') . " AS o " .
            $where . $where_store;

        $record_count = $GLOBALS['db']->getOne($sql);

        $filter['record_count'] = $record_count;
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        $groupBy = " group by o.order_id ";

        /* 查询 */
        $sql = "SELECT ifnull(bai.is_stages,0) is_stages,og.ru_id, o.order_id, o.main_order_id, o.order_sn, o.add_time, o.order_status, o.shipping_status, o.order_amount, o.money_paid, o.is_delete," .
            "o.shipping_time, o.auto_delivery_time, o.pay_status, o.consignee, o.address, o.email, o.tel, o.mobile, o.extension_code, o.extension_id, " .
            "(" . app(OrderService::class)->orderAmountField('o.') . ") AS total_fee, " .
            "IFNULL(u.user_name, '" . $GLOBALS['_LANG']['anonymous'] . "') AS buyer, o.auto_delivery_time " .
            " FROM " . $GLOBALS['dsc']->table('order_info') . " AS o " .
            " LEFT JOIN " . $GLOBALS['dsc']->table('users') . " AS u ON u.user_id=o.user_id " .
            " LEFT JOIN " . $GLOBALS['dsc']->table('order_goods') . " AS og ON o.order_id=og.order_id " .
            " LEFT JOIN " . $GLOBALS['dsc']->table('baitiao_log') . " AS bai ON o.order_id=bai.order_id " .
            $where . $where_store . $groupBy .
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
        $row[$key]['formated_order_amount'] = price_format($value['order_amount']);
        $row[$key]['formated_money_paid'] = price_format($value['money_paid']);
        $row[$key]['formated_total_fee'] = price_format($value['total_fee']);
        $row[$key]['short_order_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['add_time']);

        $auto_confirm_time = $value['add_time'] + $value['auto_delivery_time'] * 3600 * 24;
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

        //ecmoban模板堂 --zhuo start
        $row[$key]['user_name'] = app(MerchantCommonService::class)->getShopName($value['ru_id'], 1);

        $order_id = $value['order_id'];
        $date = ['order_id'];

        $order_child = count(get_table_date('order_info', "main_order_id='$order_id'", $date, 1));
        $row[$key]['order_child'] = $order_child;

        $date = ['order_sn'];
        $child_list = get_table_date('order_info', "main_order_id='$order_id'", $date, 1);
        $row[$key]['child_list'] = $child_list;
        //ecmoban模板堂 --zhuo end
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

/*
*处理商家后台的分页效果
*param    array     $list   产品列表
*param    string    $nowpage   当前页数
*param    number    $show   显示分页数量 默认超过10页则显示前后共10页
*return   array     $arr    分页数量
*/
function seller_page($list, $nowpage, $show = '10')
{
    $arr = [];

    if ($show > $list['page_count']) {
        $show = $list['page_count']; //如显示分页条数大于总条数，则等于总条数
    }

    if ($show % 2 == 0) { //判断奇偶数
        $begin = $nowpage - ceil($show / 2);
        $end = $nowpage + floor($show / 2);
    } else {
        $begin = $nowpage - floor($show / 2);
        $end = $nowpage + ceil($show / 2);
    }

    if ($show > 1) {//显示分页数量大于1时做处理
        if ($nowpage > (ceil($show / 2) + 1) && $nowpage <= ($list['page_count'] - ceil($show / 2))) {
            for ($i = $begin; $i < $end; $i++) {
                $arr[$i] = $i;
            }
        } else {
            if ($nowpage > (ceil($show / 2) + 1) && $nowpage > ($list['page_count'] - ($show - 1))) {
                for ($i = $list['page_count'] - ($show - 1); $i <= $list['page_count']; $i++) {
                    $arr[$i] = $i;
                }
            } else {
                for ($i = 1; $i <= $show; $i++) {
                    $arr[$i] = $i;
                }
            }
        }
    } else {
        $arr[1] = 1;
    }

    return $arr;
}

/*
 * 优惠活动数量
 */
function get_favourable_count($ru_id)
{
    $sql = "SELECT count(*) FROM " . $GLOBALS['dsc']->table('favourable_activity') . " WHERE user_id = '$ru_id'";
    return $GLOBALS['db']->getOne($sql);
}

/*
 * 即将到期优惠活动
 */
function get_favourable_dateout_count($ru_id)
{
    $firstSecToday = local_mktime(date("h"), date("i"), date("s"), date("m"), date("d"), date("Y")) - (24 * 60 * 60 * 2); //返回两天前的当前时间
    $sql = "SELECT count(*) FROM " . $GLOBALS['dsc']->table('favourable_activity') . " WHERE user_id = '$ru_id' AND (end_time - 24 * 60 * 60 * 3) < '$firstSecToday'";
    return $GLOBALS['db']->getOne($sql);
}

/*
 * 待商品回复咨询
 */
function get_comment_reply_count($ru_id)
{
    $where = "(SELECT count(*) FROM " . $GLOBALS['dsc']->table('comment') . " AS c2 WHERE c2.parent_id = c1.comment_id LIMIT 1) < 1";
    $sql = 'SELECT count(*) FROM ' . $GLOBALS['dsc']->table('comment') . " AS c1 WHERE c1.comment_type = 0 AND c1.parent_id = 0 AND c1.ru_id = '$ru_id' AND c1.order_id > 0 AND $where";

    return $GLOBALS['db']->getOne($sql);
}

/*
 * 新品商品数
 * 精品商品数
 * 热销商品数
 * 促销商品数
 */
function get_goods_special_count($ru_id, $type = '')
{
    $time = gmtime();
    switch ($type) {
        case 'is_hot':
            $where = "AND is_hot = 1";
            break;
        case 'is_new':
            $where = "AND is_new = 1";
            break;
        case 'is_best':
            $where = "AND is_best = 1";
            break;
        case 'promotion':
            $where = "AND is_promote = 1 AND promote_start_date < '$time' AND promote_end_date > $time";
            break;
        default:
            $where = '';
            break;
    }

    $sql = "SELECT count(*) FROM " . $GLOBALS['dsc']->table('goods') . " WHERE user_id = '$ru_id' $where AND is_delete = 0";

    return $GLOBALS['db']->getOne($sql);
}

//检查门店权限 by wu
function store_priv($priv_str, $msg_type = '', $msg_output = true)
{
    $store_user_id = 0;
    if (isset($GLOBALS['store_user_id']) && $GLOBALS['store_user_id']) {
        $store_user_id = $GLOBALS['store_user_id'];
    } elseif (session('store_user_id')) {
        $store_user_id = session('store_user_id');
    }

    $sql = " SELECT store_action FROM " . $GLOBALS['dsc']->table('store_user') . " WHERE id = '$store_user_id' ";
    $store_action = $GLOBALS['db']->getOne($sql);

    if ($store_action == 'all') {
        return true;
    } elseif (!empty($store_action) && in_array($priv_str, explode(',', $store_action))) {
        return true;
    } else {
        if ($msg_output) {
            return sys_msg("您没有使用该功能的权限", 0);
        }
        return false;
    }
}

function get_store_ru_id()
{
    $admin_id = session('store_user_id');
    $sql = "SELECT ru_id FROM" . $GLOBALS['dsc']->table('store_user') . "WHERE id = '$admin_id'";
    return $GLOBALS['db']->getOne($sql);
}

//设置默认筛选 by wu
function set_default_filter($goods_id = 0, $cat_id = 0, $user_id = 0, $cat_type_show = 0, $table = 'category')
{
    //分类导航
    if ($cat_id) {
        $parent_cat_list = get_select_category($cat_id, 1, true, $user_id, $table);
        $filter_category_navigation = get_array_category_info($parent_cat_list, $table);
        $GLOBALS['smarty']->assign('filter_category_navigation', $filter_category_navigation);
    }

    if ($user_id) {
        $seller_shop_cat = seller_shop_cat($user_id);
    } else {
        $seller_shop_cat = [];
    }

    $GLOBALS['smarty']->assign('filter_category_list', get_category_list($cat_id, 0, $seller_shop_cat, $user_id, 2, $table)); //分类列表
    $GLOBALS['smarty']->assign('filter_brand_list', search_brand_list($goods_id)); //品牌列表
    $GLOBALS['smarty']->assign('cat_type_show', $cat_type_show); //平台分类

    return true;
}

/**
 * 商家入驻分类
 */
function seller_shop_cat($user_id = 0)
{
    $seller_shop_cat = '';
    if ($user_id) {
        $sql = "SELECT user_shopMain_category FROM " . $GLOBALS['dsc']->table('merchants_shop_information') . " WHERE user_id = '$user_id'";
        $seller_shop_cat = $GLOBALS['db']->getOne($sql, true);
    }

    $arr = [];
    $arr['parent'] = '';
    if ($seller_shop_cat) {
        $seller_shop_cat = explode("-", $seller_shop_cat);

        foreach ($seller_shop_cat as $key => $row) {
            if ($row) {
                $cat = explode(":", $row);
                $arr[$key]['cat_id'] = $cat[0];
                $arr[$key]['cat_tree'] = $cat[1];

                $arr['parent'] .= $cat[0] . ",";

                if ($cat[1]) {
                    $arr['parent'] .= $cat[1] . ",";
                }
            }
        }
    }

    $arr['parent'] = substr($arr['parent'], 0, -1);

    return $arr;
}
