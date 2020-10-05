<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Models\BonusType;
use App\Models\UserBonus;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;

/**
 * 红包类型的处理
 */
class BonusController extends InitController
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $storeCommonService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        MerchantCommonService $merchantCommonService,
        StoreCommonService $storeCommonService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->storeCommonService = $storeCommonService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {

        /* act操作项的初始化 */
        $act = addslashes(trim(request()->input('act', 'list')));
        $act = $act ? $act : 'list';

        $this->smarty->assign('controller', basename(PHP_SELF, '.php'));
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "bonus");
        /* 初始化$exc对象 */
        $exc = new Exchange($this->dsc->table('bonus_type'), $this->db, 'type_id', 'type_name');

        $adminru = get_admin_ru_id();
        //ecmoban模板堂 --zhuo start
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        $this->smarty->assign('url', $this->dsc->seller_url());

        $this->smarty->assign('menu_select', ['action' => '02_promotion', 'current' => '04_bonustype_list']);

        /*------------------------------------------------------ */
        //-- 红包        类型列表页面
        /*------------------------------------------------------ */
        if ($act == 'list') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_bonustype_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['bonustype_add'], 'href' => 'bonus.php?act=add', 'class' => 'icon-plus']);
            $this->smarty->assign('full_page', 1);

            $list = $this->get_type_list($adminru['ru_id']);

            //分页
            $page = (int)request()->input('page', 1);
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('type_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('bonus_type.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页、排序
        /*------------------------------------------------------ */

        if ($act == 'query') {
            $page = (int)request()->input('page', 1);

            $list = $this->get_type_list($adminru['ru_id']);

            //分页
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('type_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('bonus_type.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 编辑红包        类型名称
        /*------------------------------------------------------ */

        if ($act == 'edit_type_name') {
            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = (int)request()->input('id', 0);
            $val = json_str_iconv(trim(request()->input('val', '')));

            /* 检查红包类型名称是否重复 */
            if (!$exc->is_only('type_name', $id, $val)) {
                return make_json_error($GLOBALS['_LANG']['type_name_exist']);
            } else {
                $exc->edit("type_name='$val'", $id);

                return make_json_result(stripslashes($val));
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑红包        金额
        /*------------------------------------------------------ */

        if ($act == 'edit_type_money') {
            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $id = (int)request()->input('id', 0);
            $val = floatval(request()->input('val', 0));

            /* 检查红包类型名称是否重复 */
            if ($val <= 0) {
                return make_json_error($GLOBALS['_LANG']['type_money_error']);
            } else {
                $exc->edit("type_money='$val'", $id);

                return make_json_result(number_format($val, 2));
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑订单下限
        /*------------------------------------------------------ */

        if ($act == 'edit_min_amount') {
            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = (int)request()->input('id', 0);
            $val = floatval(request()->input('val', 0));

            if ($val < 0) {
                return make_json_error($GLOBALS['_LANG']['min_amount_empty']);
            } else {
                $exc->edit("min_amount='$val'", $id);

                return make_json_result(number_format($val, 2));
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑订单下限
        /*------------------------------------------------------ */

        if ($act == 'edit_min_goods_amount') {
            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = (int)request()->input('id', 0);
            $val = floatval(request()->input('val', 0));

            if ($val < 0) {
                return make_json_error($GLOBALS['_LANG']['min_amount_empty']);
            } else {
                $exc->edit("min_goods_amount='$val'", $id);

                return make_json_result(number_format($val, 2));
            }
        }

        /*------------------------------------------------------ */
        //-- 删除红包        类型
        /*------------------------------------------------------ */
        if ($act == 'remove') {
            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = (int)request()->input('id', 0);

            $bonus_arr = $this->db->getRow("SELECT * FROM " . $this->dsc->table('bonus_type') . " WHERE type_id = '$id'");

            if ($bonus_arr['user_id'] != $adminru['ru_id']) {
                $url = 'bonus.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
                return dsc_header("Location: $url\n");
            }

            $exc->drop($id);

            /* 更新商品信息 */
            $this->db->query("UPDATE " . $this->dsc->table('goods') . " SET bonus_type_id = 0 WHERE bonus_type_id = '$id'");

            /* 删除用户的红包 */
            $this->db->query("DELETE FROM " . $this->dsc->table('user_bonus') . " WHERE bonus_type_id = '$id'");

            $url = 'bonus.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 红包        类型添加页面
        /*------------------------------------------------------ */
        if ($act == 'add') {
            admin_priv('bonus_manage');

            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['bonustype_add']);
            $this->smarty->assign('action_link', ['href' => 'bonus.php?act=list', 'text' => $GLOBALS['_LANG']['04_bonustype_list'], 'class' => 'icon-reply']);
            $this->smarty->assign('action', 'add');

            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);

            $next_month = local_strtotime('+1 months');
            $bonus_arr['send_start_date'] = local_date('Y-m-d H:i:s');
            $bonus_arr['use_start_date'] = local_date('Y-m-d H:i:s');
            $bonus_arr['send_end_date'] = local_date('Y-m-d H:i:s', $next_month);
            $bonus_arr['use_end_date'] = local_date('Y-m-d H:i:s', $next_month);

            $bonus_arr['bonus_send_time'] = 0;
            $this->smarty->assign('bonus_arr', $bonus_arr);


            return $this->smarty->display('bonus_type_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 红包        类型添加的处理
        /*------------------------------------------------------ */
        if ($act == 'insert') {
            /* 去掉红包类型名称前后的空格 */
            $type_name = addslashes(trim(request()->input('type_name', '')));

            /* 初始化变量 */
            $bonus_send_time = (int)request()->input('bonus_send_time', 0);
            $min_amount = (int)request()->input('min_amount', 0);
            $usebonus_type = (int)request()->input('usebonus_type', 0);
            //有效期限
            $valid_period = (int)request()->input('valid_period', 0);

            $type_money = request()->input('type_money');
            $min_goods_amount = floatval(request()->input('min_goods_amount'));
            $send_type = request()->input('send_type');

            /* 检查类型是否有重复 */
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('bonus_type') . " WHERE type_name='$type_name' AND user_id = '$adminru[ru_id]'";
            if ($this->db->getOne($sql) > 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['type_name_exist'], 0, $link);
            }

            /* 检查红包金额是否大于最小订单金额 */
            if ($type_money > $min_goods_amount) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['type_money_beyond'], 0, $link);
            }
            /*
             *红包使用时间机制
            */
            if ($bonus_send_time == 7 && $valid_period == 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['bonus_send_time_beyond'], 0, $link);
            }

            if ($bonus_send_time == 8) {
                $data_type = 0;//使用时间
            } else {
                $data_type = 1;//有效期限
            }
            /* 获得日期信息 */
            $send_startdate = local_strtotime(request()->input('send_start_date', ''));
            $send_startdate = $send_startdate > 1 ? $send_startdate : 0;
            $send_enddate = local_strtotime(request()->input('send_end_date', ''));
            $send_enddate = $send_enddate > 1 ? $send_enddate : 0;
            $use_startdate = local_strtotime(request()->input('use_start_date', ''));
            $use_startdate = $use_startdate > 1 ? $use_startdate : 0;
            $use_enddate = local_strtotime(request()->input('use_end_date', ''));
            $use_enddate = $use_enddate > 1 ? $use_enddate : 0;

            //ecmoban模板堂 --zhuo
            /* 插入数据库。 */ //ecmoban模板堂 --zhuo usebonus_type
            $sql = "INSERT INTO " . $this->dsc->table('bonus_type') . " (type_name, valid_period,date_type,type_money,send_start_date,send_end_date,use_start_date,use_end_date,send_type, usebonus_type, user_id, min_amount,  min_goods_amount)
    VALUES ('$type_name',
            '$valid_period',
            '$data_type',
            '$type_money',
            '$send_startdate',
            '$send_enddate',
            '$use_startdate',
            '$use_enddate',
            '$send_type',
            '$usebonus_type',
            '$adminru[ru_id]',
            '$min_amount'," .
                "'" . $min_goods_amount . "'" .
                ")";

            $this->db->query($sql);
            /* 记录管理员操作 */
            admin_log($type_name, 'add', 'bonustype');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[0]['text'] = $GLOBALS['_LANG']['continus_add'];
            $link[0]['href'] = 'bonus.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'bonus.php?act=list';

            return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $type_name . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 红包        类型编辑页面
        /*------------------------------------------------------ */
        if ($act == 'edit') {
            admin_priv('bonus_manage');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);

            /* 获取红包类型数据 */
            $type_id = (int)request()->input('type_id', 0);
            $bonus_arr = $this->db->getRow("SELECT * FROM " . $this->dsc->table('bonus_type') . " WHERE type_id = '$type_id'");

//    if ($bonus_arr['user_id'] != $adminru['ru_id']) {
//        $Loaction = "bonus.php?act=list";
//        return dsc_header("Location: $Loaction\n");
//
//    }

            $bonus_arr['send_start_date'] = local_date('Y-m-d H:i:s', $bonus_arr['send_start_date']);
            $bonus_arr['send_end_date'] = local_date('Y-m-d H:i:s', $bonus_arr['send_end_date']);
            $bonus_arr['use_start_date'] = local_date('Y-m-d H:i:s', $bonus_arr['use_start_date']);
            $bonus_arr['use_end_date'] = local_date('Y-m-d H:i:s', $bonus_arr['use_end_date']);

            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['bonustype_edit']);
            $this->smarty->assign('action_link', ['href' => 'bonus.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['04_bonustype_list'], 'class' => 'icon-reply']);
            $this->smarty->assign('form_act', 'update');

            if ($bonus_arr['date_type'] == 1) {
                $bonus_arr['bonus_send_time'] = 7;
            }
            if ($bonus_arr['date_type'] == 0) {
                $bonus_arr['bonus_send_time'] = 8;
            }

            $this->smarty->assign('bonus_arr', $bonus_arr);


            return $this->smarty->display('bonus_type_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 红包类型编辑的处理
        /*------------------------------------------------------ */
        if ($act == 'update') {
            /* 获得日期信息 */
            $send_startdate = request()->has('send_start_date') ? local_strtotime(request()->input('send_start_date')) : 0;
            $send_enddate = request()->has('send_end_date') ? local_strtotime(request()->input('send_end_date')) : 0;
            $use_startdate = request()->has('use_start_date') ? local_strtotime(request()->input('use_start_date')) : 0;
            $use_enddate = request()->has('use_end_date') ? local_strtotime(request()->input('use_end_date')) : 0;

            $bonus_send_time = (int)request()->input('bonus_send_time', 0);

            /* 对数据的处理 */
            $type_name = addslashes(trim(request()->input('type_name', '')));

            $type_id = (int)request()->input('type_id', 0);
            $min_amount = (int)request()->input('min_amount', 0);
            //有效期限
            $valid_period = (int)request()->input('valid_period', 0);
            $usebonus_type = (int)request()->input('usebonus_type', 0);
            $review_status = 1;

            $type_money = request()->input('type_money');
            $min_goods_amount = floatval(request()->input('min_goods_amount'));
            $send_type = request()->input('send_type');


            /* 检查红包金额是否大于最小订单金额 */
            if ($type_money > $min_goods_amount) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['type_money_beyond'], 0, $link);
            }

            /**
             * 红包使用时间机制
             */
            if ($bonus_send_time == 7 && $valid_period == 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['bonus_send_time_beyond'], 0, $link);
            }

            if ($bonus_send_time == 8) {
                $data_type = 0;//使用时间
            } else {
                $data_type = 1;//有效期限
            }

            $sql = "UPDATE " . $this->dsc->table('bonus_type') . " SET " .
                "type_name       = '$type_name', " .
                "date_type       = '$data_type', " .
                "valid_period       = '$valid_period', " .
                "type_money      = '$type_money', " .
                "send_start_date = '$send_startdate', " .
                "send_end_date   = '$send_enddate', " .
                "use_start_date  = '$use_startdate', " .
                "use_end_date    = '$use_enddate', " .
                "send_type       = '$send_type', " .
                "usebonus_type   = '$usebonus_type', " . //ecmoban模板堂 --zhuo
                "min_amount      = '$min_amount', " .
                "review_status   = '$review_status', " .
                "min_goods_amount = '" . $min_goods_amount . "' " .
                "WHERE type_id   = '$type_id'";

            $this->db->query($sql);
            /* 记录管理员操作 */
            admin_log($type_name, 'edit', 'bonustype');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'bonus.php?act=list&' . list_link_postfix()];
            return sys_msg($GLOBALS['_LANG']['edit'] . ' ' . $type_name . ' ' . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 红包        发送页面
        /*------------------------------------------------------ */
        if ($act == 'send') {
            admin_priv('bonus_manage');

            /* 取得参数 */
            $id = (int)request()->input('id', 0);

            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['send_bonus']);
            $this->smarty->assign('action_link', ['href' => 'bonus.php?act=list', 'text' => $GLOBALS['_LANG']['04_bonustype_list'], 'class' => 'icon-reply']);

            $send_by = request()->input('send_by');
            if ($send_by == SEND_BY_USER) {
                $this->smarty->assign('id', $id);
                $this->smarty->assign('ranklist', get_rank_list());

                return $this->smarty->display('bonus_by_user.dwt');
            } elseif ($send_by == SEND_BY_GOODS) {
                $this->smarty->assign('id', $id);
                set_default_filter(0, 0, $adminru['ru_id']); //by wu
                /* 查询此红包类型信息 */
                $bonus_type = $this->db->GetRow("SELECT type_id, type_name FROM " . $this->dsc->table('bonus_type') .
                    " WHERE type_id='$id'");

                /* 查询红包类型的商品列表 */
                $goods_list = $this->get_bonus_goods($id);

                /* 获取下拉列表 by wu start */
                $select_category_html = insert_select_category(0, 0, 0, 'cat_id', 1);
                $this->smarty->assign('select_category_html', $select_category_html);

                /* 模板赋值 */
                $this->smarty->assign('brand_list', get_brand_list());

                $this->smarty->assign('bonus_type', $bonus_type);
                $this->smarty->assign('goods_list', $goods_list);

                return $this->smarty->display('bonus_by_goods.dwt');
            } elseif ($send_by == SEND_BY_PRINT) {
                $this->smarty->assign('type_list', get_bonus_type());

                return $this->smarty->display('bonus_by_print.dwt');
            } elseif ($send_by == SEND_BY_GET) {
                $bonus = [];
                $sql = 'SELECT type_id, type_name, type_money FROM ' . $this->dsc->table('bonus_type') .
                    ' WHERE send_type = 4';
                $res = $this->db->query($sql);

                foreach ($res as $row) {
                    $bonus[$row['type_id']] = $row['type_name'] . ' [' . sprintf($GLOBALS['_CFG']['currency_format'], $row['type_money']) . ']';
                }

                $this->smarty->assign('type_list', $bonus);

                return $this->smarty->display('bonus_by_print.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- 处理红包        的发送页面
        /*------------------------------------------------------ */
        if ($act == 'send_by_user') {
            $user_list = [];
            $id = (int)request()->input('id', 0);

            $start = (int)request()->input('start', 0);
            $limit = (int)request()->input('limit', 10);
            $validated_email = (int)request()->input('validated_email', 0);
            $send_count = 0;
            $count = 0;

            if (request()->exists('send_rank')) {
                /* 按会员等级来发放红包 */
                $rank_id = (int)request()->input('rank_id', 0);

                if ($rank_id > 0) {
//                    $sql = "SELECT min_points, max_points, special_rank FROM " . $this->dsc->table('user_rank') . " WHERE rank_id = '$rank_id'";
//                    $row = $this->db->getRow($sql);
//                    if ($row['special_rank']) {
                    /* 特殊会员组处理 */
                    $sql = 'SELECT COUNT(*) FROM ' . $this->dsc->table('users') . " WHERE user_rank = '$rank_id'";
                    $send_count = $this->db->getOne($sql);
                    if ($validated_email) {
                        $sql = 'SELECT user_id, email, user_name FROM ' . $this->dsc->table('users') .
                            " WHERE user_rank = '$rank_id' AND is_validated = 1" .
                            " LIMIT $start, $limit";
                    } else {
                        $sql = 'SELECT user_id, email, user_name FROM ' . $this->dsc->table('users') .
                            " WHERE user_rank = '$rank_id'" .
                            " LIMIT $start, $limit";
                    }
//                    } else {
//                        $sql = 'SELECT COUNT(*) FROM ' . $this->dsc->table('users') .
//                            " WHERE rank_points >= " . intval($row['min_points']) . " AND rank_points < " . intval($row['max_points']);
//                        $send_count = $this->db->getOne($sql);
//
//                        if ($validated_email) {
//                            $sql = 'SELECT user_id, email, user_name FROM ' . $this->dsc->table('users') .
//                                " WHERE rank_points >= " . intval($row['min_points']) . " AND rank_points < " . intval($row['max_points']) .
//                                " AND is_validated = 1 LIMIT $start, $limit";
//                        } else {
//                            $sql = 'SELECT user_id, email, user_name FROM ' . $this->dsc->table('users') .
//                                " WHERE rank_points >= " . intval($row['min_points']) . " AND rank_points < " . intval($row['max_points']) .
//                                " LIMIT $start, $limit";
//                        }
//                    }

                    $user_list = $this->db->getAll($sql);
                    $count = count($user_list);
                } else {
                    return sys_msg($GLOBALS['_LANG']['send_user_empty'], 1);
                }
            } elseif (request()->exists('send_user')) {

                /* 按会员列表发放红包 */
                /* 如果是空数组，直接返回 */
                if (empty(request()->input('user', ''))) {
                    return sys_msg($GLOBALS['_LANG']['send_user_empty'], 1);
                }

                $user_array = request()->input('user');
                $user_array = (is_array($user_array)) ? $user_array : explode(',', $user_array);
                $send_count = count($user_array);

                $id_array = array_slice($user_array, $start, $limit);

                /* 根据会员ID取得用户名和邮件地址 */
                $sql = "SELECT user_id, email, user_name FROM " . $this->dsc->table('users') .
                    " WHERE user_id " . db_create_in($id_array);
                $user_list = $this->db->getAll($sql);
                $count = count($user_list);
            }

            /* 发送红包 */
            $loop = 0;
            $bonus_type = $this->bonus_type_info($id);

            $tpl = get_mail_template('send_bonus');
            $today = local_date($GLOBALS['_CFG']['time_format'], gmtime());

            foreach ($user_list as $key => $val) {
                /* 发送邮件通知 */
                $this->smarty->assign('user_name', $val['user_name']);
                $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                $this->smarty->assign('send_date', $today);
                $this->smarty->assign('sent_date', $today);
                $this->smarty->assign('count', 1);
                $this->smarty->assign('money', price_format($bonus_type['type_money']));

                $content = $this->smarty->fetch('str:' . $tpl['template_content']);

                $bonus_info = BonusType::where('type_id', $id);
                $bonus_info = $this->baseRepository->getToArrayFirst($bonus_info);

                if (empty($bonus_info)) {
                    $bonus_info = [
                        'date_type' => 0,
                        'valid_period' => 0,
                        'use_start_date' => '',
                        'use_end_date' => '',
                    ];
                }

                $other = [
                    'bonus_type_id' => $id,
                    'bonus_sn' => 0,
                    'user_id' => $val['user_id'],
                    'used_time' => 0,
                    'order_id' => 0,
                    'bind_time' => gmtime(),
                    'date_type' => $bonus_info['date_type'],
                ];
                if ($bonus_info['valid_period'] > 0) {
                    $other['start_time'] = $other['bind_time'];
                    $other['end_time'] = $other['bind_time'] + $bonus_info['valid_period'] * 3600 * 24;
                } else {
                    $other['start_time'] = $bonus_info['use_start_date'];
                    $other['end_time'] = $bonus_info['use_end_date'];
                }

                if ($this->add_to_maillist($val['user_name'], $val['email'], $tpl['template_subject'], $content, $tpl['is_html'])) {
                    /* 向会员红包表录入数据 */
                    $other['emailed'] = BONUS_MAIL_SUCCEED;
                } else {
                    /* 邮件发送失败，更新数据库 */
                    $other['emailed'] = BONUS_MAIL_FAIL;
                }

                UserBonus::insert($other);


                if ($loop >= $limit) {
                    break;
                } else {
                    $loop++;
                }
            }

            //admin_log(addslashes($GLOBALS['_LANG']['send_bonus']), 'add', 'bonustype');
            if ($send_count > ($start + $limit)) {
                /*  */
                $href = "bonus.php?act=send_by_user&start=" . ($start + $limit) . "&limit=$limit&id=$id&";

                if (request()->exists('send_rank')) {
                    $href .= "send_rank=1&rank_id=$rank_id";
                }

                if (request()->exists('send_user')) {
                    $href .= "send_user=1&user=" . implode(',', $user_array);
                }

                $link[] = ['text' => $GLOBALS['_LANG']['send_continue'], 'href' => $href];
            }

            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'bonus.php?act=list'];

            return sys_msg(sprintf($GLOBALS['_LANG']['sendbonus_count'], $count), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 发送邮件
        /*------------------------------------------------------ */

        if ($act == 'send_mail') {
            /* 取得参数：红包id */
            $bonus_id = (int)request()->input('bonus_id', 0);
            if ($bonus_id <= 0) {
                return 'invalid params';
            }

            /* 取得红包信息 */
            load_helper('order');
            $bonus = bonus_info($bonus_id);
            if (empty($bonus)) {
                return sys_msg($GLOBALS['_LANG']['bonus_not_exist']);
            }

            /* 发邮件 */
            $count = $this->send_bonus_mail($bonus['bonus_type_id'], [$bonus_id]);

            $link[0]['text'] = $GLOBALS['_LANG']['back_bonus_list'];
            $link[0]['href'] = 'bonus.php?act=bonus_list&bonus_type=' . $bonus['bonus_type_id'];

            return sys_msg(sprintf($GLOBALS['_LANG']['success_send_mail'], $count), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 按印刷品发放红包
        /*------------------------------------------------------ */

        if ($act == 'send_by_print') {
            @set_time_limit(0);

            /* 红下红包的类型ID和生成的数量的处理 */
            $bonus_typeid = (int)request()->input('bonus_type_id', 0);
            $bonus_sum = (int)request()->input('bonus_sum', 1);

            /* 生成红包序列号 */
            $num = $this->db->getOne("SELECT MAX(bonus_sn) FROM " . $this->dsc->table('user_bonus'));
            $num = $num ? floor($num / 10000) : 100000;

            for ($i = 0, $j = 0; $i < $bonus_sum; $i++) {
                $bonus_sn = ($num + $i) . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $bonus_password = strtoupper(mc_random(10));
                $this->db->query("INSERT INTO " . $this->dsc->table('user_bonus') . " (bonus_type_id, bonus_sn, bonus_password) VALUES('$bonus_typeid', '$bonus_sn', '$bonus_password')");

                $j++;
            }

            /* 记录管理员操作 */
            admin_log($bonus_sn, 'add', 'userbonus');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[0]['text'] = $GLOBALS['_LANG']['back_bonus_list'];
            $link[0]['href'] = 'bonus.php?act=bonus_list&bonus_type=' . $bonus_typeid;

            return sys_msg($GLOBALS['_LANG']['creat_bonus'] . $j . $GLOBALS['_LANG']['creat_bonus_num'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 导出线下发放的信息
        /*------------------------------------------------------ */
        if ($act == 'gen_excel') {
            @set_time_limit(0);

            /* 获得此线下红包类型的ID */
            $tid = (int)request()->input('tid', 0);
            $type_name = $this->db->getOne("SELECT type_name FROM " . $this->dsc->table('bonus_type') . " WHERE type_id = '$tid'");

            /* 文件名称 */
            $bonus_filename = $type_name . '_bonus_list';
            if (EC_CHARSET != 'gbk') {
                $bonus_filename = dsc_iconv('UTF8', 'GB2312', $bonus_filename);
            }

            header("Content-type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename=$bonus_filename.xls");

            /* 文件标题 */
            if (EC_CHARSET != 'gbk') {
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['bonus_excel_file']) . "\t\n";
                /* 红包序列号, 红包金额, 类型名称(红包名称), 使用结束日期 */
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['bonus_sn']) . "\t";
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['bind_password']) . "\t";
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['type_money']) . "\t";
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['type_name']) . "\t";
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['use_enddate']) . "\t\n";
            } else {
                echo $GLOBALS['_LANG']['bonus_excel_file'] . "\t\n";
                /* 红包序列号, 红包金额, 类型名称(红包名称), 使用结束日期 */
                echo $GLOBALS['_LANG']['bonus_sn'] . "\t";
                echo $GLOBALS['_LANG']['bind_password'] . "\t";
                echo $GLOBALS['_LANG']['type_money'] . "\t";
                echo $GLOBALS['_LANG']['type_name'] . "\t";
                echo $GLOBALS['_LANG']['use_enddate'] . "\t\n";
            }

            $val = [];
            $sql = "SELECT ub.bonus_id, ub.bonus_type_id, ub.bonus_sn, bonus_password, bt.type_name, bt.type_money, bt.use_end_date " .
                "FROM " . $this->dsc->table('user_bonus') . " AS ub, " . $this->dsc->table('bonus_type') . " AS bt " .
                "WHERE bt.type_id = ub.bonus_type_id AND ub.bonus_type_id = '$tid' ORDER BY ub.bonus_id DESC";
            $res = $this->db->query($sql);

            $code_table = [];
            foreach ($res as $val) {
                echo $val['bonus_sn'] . "\t";
                echo $val['bonus_password'] . "\t";
                echo $val['type_money'] . "\t";
                if (!isset($code_table[$val['type_name']])) {
                    if (EC_CHARSET != 'gbk') {
                        $code_table[$val['type_name']] = dsc_iconv('UTF8', 'GB2312', $val['type_name']);
                    } else {
                        $code_table[$val['type_name']] = $val['type_name'];
                    }
                }
                echo $code_table[$val['type_name']] . "\t";
                echo local_date('Y-m-d H:i:s', $val['use_end_date']);
                echo "\t\n";
            }
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */
        if ($act == 'get_goods_list') {
            $filters = dsc_decode(request()->input('JSON', ''));

            $arr = get_goods_list($filters);
            $opt = [];

            foreach ($arr as $key => $val) {
                $opt[] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => $val['shop_price']];
            }

            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 添加发放红包        的商品
        /*------------------------------------------------------ */
        if ($act == 'add_bonus_goods') {

            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $add_ids = request()->input('add_ids', '');
            $add_ids = $add_ids ? explode(',', trim($add_ids)) : '';
            $type_id = request()->input('bid');

            foreach ($add_ids as $key => $val) {
                $sql = "UPDATE " . $this->dsc->table('goods') . " SET bonus_type_id='$type_id' WHERE goods_id='$val'";
                $this->db->query($sql, 'SILENT');
            }

            /* 重新载入 */
            $arr = $this->get_bonus_goods($type_id);
            $opt = [];
            foreach ($arr as $key => $val) {
                $opt[] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 删除发放红包        的商品
        /*------------------------------------------------------ */
        if ($act == 'drop_bonus_goods') {

            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $drop_ids = request()->input('drop_ids', '');
            $drop_ids = $drop_ids ? explode(',', trim($drop_ids)) : '';
            $drop_goods_ids = db_create_in($drop_ids);

            $type_id = request()->input('bid', '');

            $this->db->query("UPDATE " . $this->dsc->table('goods') . " SET bonus_type_id = 0 " .
                "WHERE bonus_type_id = '$type_id' AND goods_id " . $drop_goods_ids);

            /* 重新载入 */
            $arr = $this->get_bonus_goods($type_id);
            $opt = [];

            foreach ($arr as $key => $val) {
                $opt[] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }
            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 搜索用户
        /*------------------------------------------------------ */
        if ($act == 'search_users') {
            $keywords = json_str_iconv(trim(request()->input('keywords', '')));

            $sql = "SELECT user_id, user_name, mobile_phone, email FROM " . $this->dsc->table('users') .
                " WHERE user_name LIKE '%" . mysql_like_quote($keywords) . "%' OR user_id LIKE '%" . mysql_like_quote($keywords) . "%' OR mobile_phone LIKE '%" . mysql_like_quote($keywords) . "%'";
            $row = $this->db->getAll($sql);

            if ($row) {
                foreach ($row as $key => $val) {
                    if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                        $row[$key]['mobile_phone'] = $this->dscRepository->stringToStar($val['mobile_phone']);
                        $row[$key]['user_name'] = $this->dscRepository->stringToStar($val['user_name']);
                        $row[$key]['email'] = $this->dscRepository->stringToStar($val['email']);
                    }
                }
            }

            return make_json_result($row);
        }

        /*------------------------------------------------------ */
        //-- 红包        列表
        /*------------------------------------------------------ */

        if ($act == 'bonus_list') {
            $page = (int)request()->input('page', 1);

            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['bonus_list']);
            $this->smarty->assign('action_link', ['href' => 'bonus.php?act=list', 'text' => $GLOBALS['_LANG']['04_bonustype_list'], 'class' => 'icon-reply']);

            $list = $this->get_bonus_list();

            /* 赋值是否显示红包序列号 */
            $bonus_type = $this->bonus_type_info(intval(request()->input('bonus_type', 0)));
            if ($bonus_type['send_type'] == SEND_BY_PRINT) {
                $this->smarty->assign('show_bonus_sn', 1);
            } /* 赋值是否显示发邮件操作和是否发过邮件 */
            elseif ($bonus_type['send_type'] == SEND_BY_USER) {
                $this->smarty->assign('show_mail', 1);
            }

            $this->smarty->assign('bonus_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            return $this->smarty->display('bonus_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 红包        列表翻页、排序
        /*------------------------------------------------------ */

        if ($act == 'query_bonus') {
            $page = (int)request()->input('page', 1);

            $list = $this->get_bonus_list();

            /* 赋值是否显示红包序列号 */
            $bonus_type = $this->bonus_type_info(intval(request()->input('bonus_type', 0)));
            if ($bonus_type['send_type'] == SEND_BY_PRINT) {
                $this->smarty->assign('show_bonus_sn', 1);
            } /* 赋值是否显示发邮件操作和是否发过邮件 */
            elseif ($bonus_type['send_type'] == SEND_BY_USER) {
                $this->smarty->assign('show_mail', 1);
            }

            $this->smarty->assign('bonus_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            return make_json_result(
                $this->smarty->fetch('bonus_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 删除红包
        /*------------------------------------------------------ */
        elseif ($act == 'remove_bonus') {
            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = (int)request()->input('id', 0);

            $this->db->query("DELETE FROM " . $this->dsc->table('user_bonus') . " WHERE bonus_id='$id'");

            $url = 'bonus.php?act=query_bonus&' . str_replace('act=remove_bonus', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        if ($act == 'batch') {
            /* 检查权限 */
            admin_priv('bonus_manage');

            /* 去掉参数：红包类型 */
            $bonus_type_id = (int)request()->input('bonus_type', 0);

            /* 取得选中的红包id */
            if (request()->exists('checkboxes')) {
                $bonus_id_list = request()->input('checkboxes');

                /* 删除红包 */
                if (request()->exists('drop')) {
                    $sql = "DELETE FROM " . $this->dsc->table('user_bonus') . " WHERE bonus_id " . db_create_in($bonus_id_list);
                    $this->db->query($sql);

                    admin_log(count($bonus_id_list), 'remove', 'userbonus');

                    clear_cache_files();

                    $link[] = ['text' => $GLOBALS['_LANG']['back_bonus_list'],
                        'href' => 'bonus.php?act=bonus_list&bonus_type=' . $bonus_type_id];
                    return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], count($bonus_id_list)), 0, $link);
                } /* 发邮件 */
                elseif (request()->exists('mail')) {
                    $count = $this->send_bonus_mail($bonus_type_id, $bonus_id_list);
                    $link[] = ['text' => $GLOBALS['_LANG']['back_bonus_list'],
                        'href' => 'bonus.php?act=bonus_list&bonus_type=' . $bonus_type_id];
                    return sys_msg(sprintf($GLOBALS['_LANG']['success_send_mail'], $count), 0, $link);
                }
            } else {
                return sys_msg($GLOBALS['_LANG']['no_select_bonus'], 1);
            }
        }
    }

    /**
     * 获取红包类型列表
     * @access  public
     * @return void
     */
    private function get_type_list($ru_id)
    {
        /* 获得所有红包类型的发放数量 */
        $sql = "SELECT bonus_type_id, COUNT(*) AS sent_count" .
            " FROM " . $this->dsc->table('user_bonus') .
            " GROUP BY bonus_type_id";
        $res = $this->db->query($sql);

        $sent_arr = [];
        foreach ($res as $row) {
            $sent_arr[$row['bonus_type_id']] = $row['sent_count'];
        }

        /* 获得所有红包类型的发放数量 */
        $sql = "SELECT bonus_type_id, COUNT(*) AS used_count" .
            " FROM " . $this->dsc->table('user_bonus') .
            " WHERE used_time > 0" .
            " GROUP BY bonus_type_id";
        $res = $this->db->query($sql);

        $used_arr = [];
        foreach ($res as $row) {
            $used_arr[$row['bonus_type_id']] = $row['used_count'];
        }

        $result = get_filter();
        if ($result === false) {
            /* 过滤条件 */
            $filter['keyword'] = addslashes(trim(request()->input('keyword', '')));
            if (request()->exists('is_ajax') && request()->input('is_ajax') == 1) {
                $filter['keyword'] = json_str_iconv($filter['keyword']);
            }
            $filter['sort_by'] = addslashes(trim(request()->input('sort_by', 'bt.type_id')));
            $filter['sort_order'] = addslashes(trim(request()->input('sort_order', 'DESC')));
            $filter['use_type'] = (int)request()->input('use_type', 0);
            $filter['review_status'] = (int)request()->input('review_status', 0);

            $where = " WHERE 1";

            //ecmoban模板堂 --zhuo start
            if ($filter['use_type'] == 1) { //自营
                $where .= " AND bt.user_id = 0 AND bt.usebonus_type = 0";
            } elseif ($filter['use_type'] == 2) { //商家
                $where .= " AND bt.user_id > 0 AND bt.usebonus_type = 0";
            } elseif ($filter['use_type'] == 3) { //全场
                $where .= " AND bt.usebonus_type = 1";
            } elseif ($filter['use_type'] == 4) { //商家自主使用
                $where .= " AND bt.user_id = '$ru_id' AND bt.usebonus_type = 0";
            } else {
                if ($ru_id > 0) {
                    $where .= " AND (bt.user_id = '$ru_id' OR bt.usebonus_type = 1)";
                }
            }

            if ($filter['review_status']) {
                $where .= " AND bt.review_status = '" . $filter['review_status'] . "' ";
            }

            if (!empty($filter['keyword'])) {
                $where .= " AND  bt.type_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
            }
            //ecmoban模板堂 --zhuo end

            //管理员查询的权限 -- 店铺查询 start
            $filter['store_search'] = (int)request()->input('store_search', 0);
            $filter['merchant_id'] = (int)request()->input('merchant_id', 0);
            $filter['store_keyword'] = addslashes(trim(request()->input('store_keyword', '')));

            $store_where = '';
            $store_search_where = '';
            if ($filter['store_search'] != 0) {
                if ($ru_id == 0) {
                    $store_type = (int)request()->input('store_type', 0);

                    if ($store_type) {
                        $store_search_where = "AND msi.shopNameSuffix = '$store_type'";
                    }

                    if ($filter['store_search'] == 1) {
                        $where .= " AND bt.user_id = '" . $filter['merchant_id'] . "' ";
                    } elseif ($filter['store_search'] == 2) {
                        $store_where .= " AND msi.rz_shopName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%'";
                    } elseif ($filter['store_search'] == 3) {
                        $store_where .= " AND msi.shoprz_brandName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%' " . $store_search_where;
                    }

                    if ($filter['store_search'] > 1) {
                        $where .= " AND (SELECT msi.user_id FROM " . $this->dsc->table('merchants_shop_information') . ' as msi ' .
                            " WHERE msi.user_id = bt.user_id $store_where) > 0 ";
                    }
                }
            }
            //管理员查询的权限 -- 店铺查询 end

            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('bonus_type') . " AS bt " . $where;
            $filter['record_count'] = $this->db->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            $sql = "SELECT bt.* FROM " . $this->dsc->table('bonus_type') . " AS bt " . $where . " ORDER BY $filter[sort_by] $filter[sort_order]";
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $res = $this->db->selectLimit($sql, $filter['page_size'], $filter['start'] ?? 0);

        $list = [];
        if ($res) {
            foreach ($res as $row) {
                $row['send_by'] = $GLOBALS['_LANG']['send_by'][$row['send_type']];
                $row['send_count'] = isset($sent_arr[$row['type_id']]) ? $sent_arr[$row['type_id']] : 0;
                $row['use_count'] = isset($used_arr[$row['type_id']]) ? $used_arr[$row['type_id']] : 0;

                $row['user_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1); //ecmoban模板堂 --zhuo

                $list[] = $row;
            }
        }

        $arr = ['item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 查询红包类型的商品列表
     *
     * @access  public
     * @param integer $type_id
     * @return  array
     */
    private function get_bonus_goods($type_id)
    {
        $sql = "SELECT goods_id, goods_name FROM " . $this->dsc->table('goods') .
            " WHERE bonus_type_id = '$type_id'";
        $row = $this->db->getAll($sql);

        return $row;
    }

    /**
     * 获取用户红包列表
     * @access  public
     * @param   $page_param
     * @return void
     */
    private function get_bonus_list()
    {
        /* 查询条件 */
        $filter['sort_by'] = addslashes(trim(request()->input('sort_by', 'ub.bonus_id')));
        $filter['sort_order'] = addslashes(trim(request()->input('sort_order', 'DESC')));
        $filter['bonus_type'] = (int)request()->input('bonus_type', 0);

        $where = empty($filter['bonus_type']) ? '' : " WHERE bonus_type_id='$filter[bonus_type]'";

        $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('user_bonus') . $where;
        $filter['record_count'] = $this->db->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        $sql = "SELECT ub.*, u.user_name, u.email, u.mobile_phone, o.order_sn, bt.type_name " .
            " FROM " . $this->dsc->table('user_bonus') . " AS ub " .
            " LEFT JOIN " . $this->dsc->table('bonus_type') . " AS bt ON bt.type_id=ub.bonus_type_id " .
            " LEFT JOIN " . $this->dsc->table('users') . " AS u ON u.user_id=ub.user_id " .
            " LEFT JOIN " . $this->dsc->table('order_info') . " AS o ON o.order_id=ub.order_id $where " .
            " ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order'] .
            " LIMIT " . $filter['start'] . ", $filter[page_size]";
        $row = $this->db->getAll($sql);

        foreach ($row as $key => $val) {
            $row[$key]['used_time'] = $val['used_time'] == 0 ?
                $GLOBALS['_LANG']['no_use'] : local_date($GLOBALS['_CFG']['date_format'], $val['used_time']);
            $row[$key]['emailed'] = $GLOBALS['_LANG']['mail_status'][$row[$key]['emailed']];

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $row[$key]['mobile_phone'] = $this->dscRepository->stringToStar($val['mobile_phone']);
                $row[$key]['user_name'] = $this->dscRepository->stringToStar($val['user_name']);
                $row[$key]['email'] = $this->dscRepository->stringToStar($val['email']);
            }
        }

        $arr = ['item' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 取得红包类型信息
     * @param int $bonus_type_id 红包类型id
     * @return  array
     */
    private function bonus_type_info($bonus_type_id)
    {
        $sql = "SELECT * FROM " . $this->dsc->table('bonus_type') .
            " WHERE type_id = '$bonus_type_id'";

        return $this->db->getRow($sql);
    }

    /**
     * 发送红包邮件
     * @param int $bonus_type_id 红包类型id
     * @param array $bonus_id_list 红包id数组
     * @return  int     成功发送数量
     */
    private function send_bonus_mail($bonus_type_id, $bonus_id_list)
    {
        /* 取得红包类型信息 */
        $bonus_type = $this->bonus_type_info($bonus_type_id);
        if ($bonus_type['send_type'] != SEND_BY_USER) {
            return 0;
        }

        /* 取得属于该类型的红包信息 */
        $sql = "SELECT b.bonus_id, u.user_name, u.email " .
            "FROM " . $this->dsc->table('user_bonus') . " AS b, " .
            $this->dsc->table('users') . " AS u " .
            " WHERE b.user_id = u.user_id " .
            " AND b.bonus_id " . db_create_in($bonus_id_list) .
            " AND b.order_id = 0 " .
            " AND u.email <> ''";
        $bonus_list = $this->db->getAll($sql);
        if (empty($bonus_list)) {
            return 0;
        }

        /* 初始化成功发送数量 */
        $send_count = 0;

        /* 发送邮件 */
        $tpl = get_mail_template('send_bonus');
        $today = local_date($GLOBALS['_CFG']['date_format']);
        foreach ($bonus_list as $bonus) {
            $GLOBALS['smarty']->assign('user_name', $bonus['user_name']);
            $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
            $GLOBALS['smarty']->assign('send_date', $today);
            $GLOBALS['smarty']->assign('sent_date', $today);
            $GLOBALS['smarty']->assign('count', 1);
            $GLOBALS['smarty']->assign('money', price_format($bonus_type['type_money']));

            $content = $GLOBALS['smarty']->fetch('str:' . $tpl['template_content']);
            if ($this->add_to_maillist($bonus['user_name'], $bonus['email'], $tpl['template_subject'], $content, $tpl['is_html'], false)) {
                $sql = "UPDATE " . $this->dsc->table('user_bonus') .
                    " SET emailed = '" . BONUS_MAIL_SUCCEED . "'" .
                    " WHERE bonus_id = '$bonus[bonus_id]'";
                $this->db->query($sql);
                $send_count++;
            } else {
                $sql = "UPDATE " . $this->dsc->table('user_bonus') .
                    " SET emailed = '" . BONUS_MAIL_FAIL . "'" .
                    " WHERE bonus_id = '$bonus[bonus_id]'";
                $this->db->query($sql);
            }
        }

        return $send_count;
    }

    private function add_to_maillist($username, $email, $subject, $content, $is_html)
    {
        $time = time();
        $content = addslashes($content);
        $template_id = $this->db->getOne("SELECT template_id FROM " . $this->dsc->table('mail_templates') . " WHERE template_code = 'send_bonus'");
        $sql = "INSERT INTO " . $this->dsc->table('email_sendlist') . " ( email, template_id, email_content, pri, last_send) VALUES ('$email', '$template_id', '$content', 1, '$time')";
        $this->db->query($sql);
        return true;
    }
}
