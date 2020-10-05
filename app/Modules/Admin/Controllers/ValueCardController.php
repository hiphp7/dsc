<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Exchange;
use App\Models\ValueCardType;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\ValueCardManageService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 储值卡的处理
 */
class ValueCardController extends InitController
{
    protected $merchantCommonService;
    protected $valueCardManageService;
    protected $dscRepository;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        ValueCardManageService $valueCardManageService,
        DscRepository $dscRepository
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->valueCardManageService = $valueCardManageService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /* 初始化$exc对象 */
        $exc = new Exchange($this->dsc->table('value_card_type'), $this->db, 'id', 'name');

        $adminru = get_admin_ru_id();

        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        /* ------------------------------------------------------ */
        //-- 储值卡类型列表页面
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['vc_type_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['vc_type_add'], 'href' => 'value_card.php?act=vc_type_add']);
            $this->smarty->assign('full_page', 1);

            $list = $this->vc_type_list();

            $this->smarty->assign('value_card_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('vc_type_list.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 翻页、排序
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            $list = $this->vc_type_list();
            $this->smarty->assign('value_card_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('vc_type_list.dwt'), '', ['filter' => $list['filter'], 'page_count' => $list['page_count']]);
        }

        /* ------------------------------------------------------ */
        //-- 翻页、排序
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'vc_query') {
            $vc_list = $this->vc_list();
            $this->smarty->assign('value_card_list', $vc_list['item']);
            $this->smarty->assign('filter', $vc_list['filter']);
            $this->smarty->assign('record_count', $vc_list['record_count']);
            $this->smarty->assign('page_count', $vc_list['page_count']);

            $sort_flag = sort_flag($vc_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('value_card_view.dwt'), '', ['filter' => $vc_list['filter'], 'page_count' => $vc_list['page_count']]);
        }

        /* ------------------------------------------------------ */
        //-- 添加储值卡类型页面
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'vc_type_add' || $_REQUEST['act'] == 'vc_type_edit') {
            $row = [];
            if ($_REQUEST['act'] == 'vc_type_add') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['vc_type_add']);
                $this->smarty->assign('form_act', 'insert');
            } else {
                $id = $_REQUEST['id'] ? intval($_REQUEST['id']) : 0;
                $sql = " SELECT * FROM " . $this->dsc->table('value_card_type') . " WHERE id = '$id' ";
                $row = $this->db->getRow($sql);
                $row['vc_dis'] = $row['vc_dis'] * 100;

                //指定分类
                if ($row['use_condition'] == 1) {
                    $row['cats'] = get_choose_cat($row['spec_cat']);
                } //指定商品
                elseif ($row['use_condition'] == 2) {
                    $row['goods'] = get_choose_goods($row['spec_goods']);
                }

                if ($row['use_merchants'] == 'all') {
                    $row['use_merchants'] = 0;
                } elseif ($row['use_merchants'] == 'self') {
                    $row['use_merchants'] = 1;
                } else {
                    $row['selected_merchants'] = $row['use_merchants'];
                    $row['use_merchants'] = 2;
                }

                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['vc_type_edit']);
                $this->smarty->assign('form_act', 'update');
            }

            $this->smarty->assign('vc', $row);
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('action_link', ['href' => 'value_card.php?act=list', 'text' => $GLOBALS['_LANG']['vc_type_list']]);
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);

            set_default_filter(); //设置默认筛选

            return $this->smarty->display('vc_type_info.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 储值卡类型添加的处理
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            /* 过滤数据 */
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $vc_desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';
            $vc_limit = isset($_POST['limit']) ? intval($_POST['limit']) : 1;
            $vc_value = isset($_POST['value']) ? intval($_POST['value']) : 0;
            $vc_dis = !empty($_POST['vc_dis']) ? intval($_POST['vc_dis']) / 100 : 1;
            $vc_indate = !empty($_POST['indate']) ? intval($_POST['indate']) : 36;
            $use_condition = isset($_POST['use_condition']) ? intval($_POST['use_condition']) : 0;
            $use_merchants = isset($_POST['use_merchants']) ? intval($_POST['use_merchants']) : '';
            $spec_cat = !empty($_POST['vc_cat']) && $use_condition == 1 ? implode(',', array_unique($_POST['vc_cat'])) : '';
            $spec_goods = !empty($_POST['vc_goods']) && $use_condition == 2 ? implode(',', array_unique($_POST['vc_goods'])) : '';
            // $begin_time = isset($_POST['begin_time']) ? local_strtotime($_POST['begin_time']) : gmtime() ;
            // $end_time = isset($_POST['end_time']) ? local_strtotime($_POST['end_time']) : '';
            $prefix = isset($_POST['prefix']) ? trim($_POST['prefix']) : 0;
            $is_rec = isset($_POST['is_rec']) ? intval($_POST['is_rec']) : 0;
            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            if ($use_merchants == 0) {
                $use_merchants = 'all';
            } elseif ($use_merchants == 1) {
                $use_merchants = 'self';
            } elseif ($use_merchants == 2) {
                $use_merchants = isset($_POST['selected_merchants']) ? trim($_POST['selected_merchants']) : '';
            }

            if ($id > 0) {
                $sql = " UPDATE " . $this->dsc->table('value_card_type') . " SET " .
                    " name = '$name', " .
                    " vc_desc = '$vc_desc', " .
                    " vc_limit = '$vc_limit', " .
                    " vc_value = '$vc_value', " .
                    " vc_prefix = '$prefix', " .
                    " vc_dis = '$vc_dis', " .
                    " vc_indate = '$vc_indate', " .
                    " use_condition = '$use_condition', " .
                    " use_merchants = '$use_merchants', " .
                    " spec_goods = '$spec_goods', " .
                    " spec_cat = '$spec_cat', " .
                    // " begin_time = '$begin_time', ".
                    // " end_time = '$end_time', ".
                    " is_rec = '$is_rec' " .
                    " WHERE id = '$id' ";

                $this->db->query($sql);
                $notice = lang('admin/value_card.edit_type_success');
            } else {

                $time = gmtime();

                $count = ValueCardType::where('name', $name)->where('add_time', $time)->count();

                if ($count == 0) {
                    $value_card = [
                        'name' => $name,
                        'vc_desc' => $vc_desc,
                        'vc_limit' => $vc_limit,
                        'vc_value' => $vc_value,
                        'vc_prefix' => $prefix,
                        'vc_dis' => $vc_dis,
                        'vc_indate' => $vc_indate,
                        'use_condition' => $use_condition,
                        'use_merchants' => $use_merchants,
                        'spec_goods' => $spec_goods,
                        'spec_cat' => $spec_cat,
                        // 'begin_time'	=> $begin_time,
                        // 'end_time'		=> $end_time,
                        'is_rec' => $is_rec
                    ];

                    $this->valueCardManageService->ValueCardTypeInsert($value_card, $time);
                }

                $notice = lang('admin/value_card.add_type_success');
            }
            /* 提示信息 */
            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'value_card.php?act=list';

            return sys_msg($notice, 0, $link);
        }

        /* ------------------------------------------------------ */
        //-- 删除储值卡类型
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            $id = intval($_GET['id']);

            //检查是否存在已绑定用户的储值卡 如果有则无法删除
            $sql = " SELECT COUNT(*) FROM " . $this->dsc->table('value_card') . " WHERE tid = '$id' AND user_id > 0 ";
            $row = $this->db->getOne($sql);
            if ($row > 0) {
                return make_json_error($GLOBALS['_LANG']['notice_remove_type_error']);
            } else {
                $exc->drop($id);
                $sql = " DELETE FROM " . $this->dsc->table('value_card') . " WHERE tid = '$id' ";
                $this->db->query($sql);

                $url = 'value_card.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
                return dsc_header("Location: $url\n");
            }
        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 取得要操作的记录编号 */
            if (empty($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_record_selected']);
            } else {
                $ids = $_POST['checkboxes'];
                //检查是否存在已绑定用户的储值卡 如果有则无法删除
                $sql = " SELECT COUNT(*) FROM " . $this->dsc->table('value_card') . " WHERE tid" . db_create_in($ids) . " AND user_id > 0 ";
                $row = $this->db->getOne($sql);
                if (isset($_POST['drop'])) {
                    if ($row > 0) {
                        $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'value_card.php?act=list&' . list_link_postfix()];
                        return sys_msg($GLOBALS['_LANG']['notice_remove_type_error'], 1, $links);
                    } else {
                        /* 删除记录 */
                        $sql = "DELETE FROM " . $this->dsc->table('value_card_type') .
                            " WHERE id " . db_create_in($ids);
                        $res = $this->db->query($sql);
                        if ($res) {
                            $sql = " DELETE FROM " . $this->dsc->table('value_card') . " WHERE tid " . db_create_in($ids);
                            $this->db->query($sql);
                        }

                        /* 记日志 */
                        admin_log('', 'batch_remove', 'value_card');

                        /* 清除缓存 */
                        clear_cache_files();

                        $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'value_card.php?act=list&' . list_link_postfix()];
                        return sys_msg($GLOBALS['_LANG']['batch_drop_ok'], 0, $links);
                    }
                }
            }
        }

        /* ------------------------------------------------------ */
        //-- 删除储值卡
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove_vc') {
            $id = intval($_GET['id']);

            //检查是否存在已绑定用户的储值卡 如果有则无法删除
            $sql = " SELECT user_id FROM " . $this->dsc->table('value_card') . " WHERE vid = '$id' ";
            $row = $this->db->getOne($sql);
            if ($row > 0) {
                return make_json_error($GLOBALS['_LANG']['notice_remove_vc_error']);
            } else {
                $sql = " DELETE FROM " . $this->dsc->table('value_card') . " WHERE vid = '$id' ";
                $this->db->query($sql);

                $url = 'value_card.php?act=vc_query&' . str_replace('act=remove_vc', '', request()->server('QUERY_STRING'));
                return dsc_header("Location: $url\n");
            }
        }

        /* ------------------------------------------------------ */
        //-- 储值卡发放详情        页
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'send') {
            $id = $_REQUEST['id'] ? intval($_REQUEST['id']) : 0;

            $this->smarty->assign('type_id', $id);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['value_card_send']);
            return $this->smarty->display('value_card_send.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 查看储值卡列表页
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'vc_list') {
            $id = isset($_REQUEST['tid']) ? intval($_REQUEST['tid']) : 0;
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['export_vc_list'], 'href' => 'value_card.php?act=export_vc_list&id=' . $id]);
            $vc_list = $this->vc_list();
            $this->smarty->assign('value_card_list', $vc_list['item']);
            $this->smarty->assign('filter', $vc_list['filter']);
            $this->smarty->assign('record_count', $vc_list['record_count']);
            $this->smarty->assign('page_count', $vc_list['page_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['value_card_list']);

            return $this->smarty->display('value_card_view.dwt');
        }
        /* ------------------------------------------------------ */
        //-- 导出储值卡
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'export_vc_list') {
            $id = $_REQUEST['id'] ? intval($_REQUEST['id']) : 0;
            $where = " WHERE 1 ";
            if ($id > 0) {
                $where .= " AND vc.tid = '$id' ";
            }
            $arr = [];
            $sql = " SELECT vc.vid,vc.value_card_sn,vc.value_card_password,vc.vc_value,vc.bind_time, t.name, u.user_name FROM " . $this->dsc->table('value_card') . " AS vc " .
                " LEFT JOIN " . $this->dsc->table('value_card_type') . " AS t ON vc.tid = t.id " .
                " LEFT JOIN " . $this->dsc->table('users') . " AS u ON u.user_id = vc.user_id " .
                $where;
            $row = $this->db->getAll($sql);
            foreach ($row as $key => $val) {
                $arr[$key]['vid'] = $val['vid'];
                $arr[$key]['value_card_sn'] = $val['value_card_sn'];
                $arr[$key]['value_card_password'] = $val['value_card_password'];
                $arr[$key]['name'] = $val['name'];
                $arr[$key]['vc_value'] = $val['vc_value'];
                $arr[$key]['user_name'] = $val['user_name'];
                $arr[$key]['bind_time'] = $val['bind_time'] > 0 ? local_date($GLOBALS['_CFG']['date_format'], $val['bind_time']) : $GLOBALS['_LANG']['no_use'];
            }

            $prev = [$GLOBALS['_LANG']['record_id'], $GLOBALS['_LANG']['value_card_sn'], $GLOBALS['_LANG']['value_card_password'], $GLOBALS['_LANG']['value_card_type'], $GLOBALS['_LANG']['value_card_value'], $GLOBALS['_LANG']['bind_user'], $GLOBALS['_LANG']['bind_time']];
            export_csv_pro($arr, 'export_vc_list', $prev);
        }
        /* ------------------------------------------------------ */
        //-- 储值卡发放操作
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'send_value_card') {
            @set_time_limit(0);

            /* 储值卡类型和生成的数量的处理 */
            $tid = $_POST['type_id'] ? intval($_POST['type_id']) : 0;
            $send_sum = !empty($_POST['send_num']) ? intval($_POST['send_num']) : 1;
            $card_type = intval($_POST['card_type']);
            $password_type = intval($_POST['password_type']);

            $sql = " SELECT vc_value, vc_prefix FROM " . $this->dsc->table('value_card_type') . " WHERE id = '$tid' ";
            $row = $this->db->getRow($sql);
            $vc_prefix = $row['vc_prefix'] ? trim($row['vc_prefix']) : '';
            $prefix_len = strlen($vc_prefix);
            $length = $prefix_len + $card_type;

            /* 生成储值卡序列号 */
            $num = $this->db->getOne(" SELECT MAX(SUBSTRING(value_card_sn,$prefix_len+1)) FROM " . $this->dsc->table('value_card') . " WHERE tid = '$tid' AND LENGTH(value_card_sn) = '$length' ");
            $num = $num ? intval($num) : 1;

            for ($i = 0, $j = 0; $i < $send_sum; $i++) {
                $value_card_sn = $vc_prefix . str_pad($num + $i + 1, $card_type, '0', STR_PAD_LEFT);
                $value_card_password = strtoupper(mc_random($password_type));
                $this->db->query("INSERT INTO " . $this->dsc->table('value_card') . " (tid, value_card_sn, value_card_password, vc_value, card_money) VALUES('$tid', '$value_card_sn', '$value_card_password', '$row[vc_value]', '$row[vc_value]')");
                $j++;
            }

            /* 记录管理员操作 */
            admin_log($value_card_sn, 'add', 'value_card');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'value_card.php?act=list';
            return sys_msg($GLOBALS['_LANG']['creat_value_card'] . $j . $GLOBALS['_LANG']['value_card_num'], 0, $link);
        }

        /* ------------------------------------------------------ */
        //--  指定可使用的储值卡的店铺
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'select_merchants') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $selected = !empty($_GET['selected']) ? trim($_GET['selected']) : '';
            $sql = " SELECT ru_id FROM " . $this->dsc->table('seller_shopinfo') . " WHERE ru_id > 0 ";
            $shop_ids = $this->db->getAll($sql);

            $can_choice = [];
            foreach ($shop_ids as $k => $v) {
                $can_choice[$k]['ru_id'] = $v['ru_id'];
                $can_choice[$k]['rz_shopName'] = $this->merchantCommonService->getShopName($v['ru_id'], 1);
            }
            $is_choice = [];
            $is_choice = explode(',', $selected);

            $this->smarty->assign('can_choice', $can_choice);
            $this->smarty->assign('is_choice', $is_choice);
            $result['content'] = $GLOBALS['smarty']->fetch('library/merchants_list.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //--  储值卡前缀是否重复
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'code_notice') {
            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $prefix = isset($_REQUEST['prefix']) && !empty($_REQUEST['prefix']) ? trim($_REQUEST['prefix']) : '';

            /* 检查是否重复 */
            $is_only = ValueCardType::where('vc_prefix', $prefix);

            if ($id > 0) {
                $is_only = $is_only->where('id', '<>', $id);
            }

            $is_only = $is_only->count();

            if ($is_only > 0) {
                $error = false;
            } else {
                $error = true;
            }

            return response()->json($error);
        }
    }

    /**
     * 储值卡类型列表
     * @access  public
     * @return void
     */
    private function vc_type_list()
    {
        $result = get_filter();
        if ($result === false) {
            /* 过滤条件 */
            $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keyword'] = json_str_iconv($filter['keyword']);
            }

            /* 查询条件 */
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $where = " WHERE 1 ";

            $where .= (!empty($filter['keyword'])) ? " AND (ggt.gift_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%')" : '';

            $sql = " SELECT COUNT(*) FROM " . $this->dsc->table('value_card_type') . " AS t " . $where;
            $filter['record_count'] = $this->db->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            $sql = "SELECT * FROM " . $this->dsc->table('value_card_type') . " AS t" . " $where ORDER BY $filter[sort_by] $filter[sort_order]";

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $arr = [];
        $filter['start'] = $filter['start'] ?? 0;
        $res = $this->db->selectLimit($sql, $filter['page_size'], $filter['start']);

        foreach ($res as $row) {
            $array = [$GLOBALS['_LANG']['all_goods'], $GLOBALS['_LANG']['spec_cat'], $GLOBALS['_LANG']['spec_goods']];
            $row['use_condition'] = $array[$row['use_condition']];
            $row['vc_indate'] = $row['vc_indate'] . $GLOBALS['_LANG']['months'];
            $row['vc_dis'] = $row['vc_dis'] * 100 . '%';
            $row['send_amount'] = $this->send_amount($row['id']);
            $row['use_amount'] = $this->use_amount($row['id']);
            $arr[] = $row;
        }
        $arr = ['item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 储值卡列表
     * @access  public
     * @return void
     */
    private function vc_list()
    {
        $result = get_filter();
        if ($result === false) {
            /* 查询条件 */
            $filter['tid'] = empty($_REQUEST['tid']) ? 0 : trim($_REQUEST['tid']);
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'vc.vid' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
            $filter['value_card_type'] = empty($_REQUEST['value_card_type']) ? 0 : intval($_REQUEST['value_card_type']);

            $where = " WHERE 1 ";
            if ($filter['tid']) {
                $where .= " AND tid = '" . $filter['tid'] . "' ";
            }

            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('value_card') . $where;
            $filter['record_count'] = $this->db->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            $sql = " SELECT vc.*, t.name, u.user_name FROM " . $this->dsc->table('value_card') . " AS vc " .
                " LEFT JOIN " . $this->dsc->table('value_card_type') . " AS t ON vc.tid = t.id " .
                " LEFT JOIN " . $this->dsc->table('users') . " AS u ON u.user_id = vc.user_id " .
                $where .
                " ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ", $filter[page_size]";

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $row = $this->db->getAll($sql);
        foreach ($row as $key => $val) {
            $row[$key]['bind_time'] = $val['bind_time'] > 0 ? local_date($GLOBALS['_CFG']['date_format'], $val['bind_time']) : $GLOBALS['_LANG']['no_use'];

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $row[$key]['user_name'] = $this->dscRepository->stringToStar($val['user_name']);
            }
        }

        $arr = ['item' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /*
     * 已发放储值卡数量
     */

    private function send_amount($id)
    {
        $sql = " SELECT COUNT(*) FROM " . $this->dsc->table('value_card') . " WHERE tid = '$id' ";
        return $this->db->getOne($sql);
    }

    /*
     * 已使用储值卡数量
     */

    private function use_amount($id)
    {
        $sql = " SELECT COUNT(*) FROM " . $this->dsc->table('value_card') . " WHERE tid = '$id' AND user_id > 0 AND bind_time > 0 ";
        return $this->db->getOne($sql);
    }
}
