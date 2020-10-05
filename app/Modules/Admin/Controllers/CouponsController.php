<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Exchange;
use App\Models\BonusType;
use App\Models\Coupons;
use App\Models\CouponsRegion;
use App\Models\CouponsUser;
use App\Repositories\Common\BaseRepository;
use App\Services\Activity\CouponsService;
use App\Services\Store\StoreCommonService;

/**
 * 优惠券类型的处理
 */
class CouponsController extends InitController
{
    protected $couponsService;
    protected $baseRepository;
    protected $storeCommonService;

    public function __construct(
        CouponsService $couponsService,
        BaseRepository $baseRepository,
        StoreCommonService $storeCommonService
    )
    {
        $this->couponsService = $couponsService;
        $this->baseRepository = $baseRepository;
        $this->storeCommonService = $storeCommonService;
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
        $exc = new Exchange($this->dsc->table('bonus_type'), $this->db, 'type_id', 'type_name');
        $cou = new Exchange($this->dsc->table('coupons'), $this->db, 'cou_id', 'cou_name');
        $adminru = get_admin_ru_id();
        //ecmoban模板堂 --zhuo start
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end
        /*------------------------------------------------------ */
        //-- 优惠券列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('coupons_manage');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['cou_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['continus_add'], 'href' => 'javascript:;']);
            $this->smarty->assign('full_page', 1);

            $list = get_coupons_type_info();

            $this->smarty->assign('url', $this->dsc->url());
            $this->smarty->assign('cou_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $store_list = $this->storeCommonService->getCommonStoreList();

            $this->smarty->assign('store_list', $store_list);
            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()));


            return $this->smarty->display('coupons_type_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页、排序
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'query') {
            if ($_POST['cou_type']) {
                $list = get_coupons_type_info($_POST['cou_type']);
            } else {
                $list = get_coupons_type_info();
            }
            $this->smarty->assign('cou_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('coupons_type_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 编辑优惠券类型名称
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit_type_name') {
            $check_auth = check_authz_json('coupons_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = json_str_iconv(trim($_POST['val']));

            $coupons = Coupons::where('cou_id', $id);
            $coupons = $this->baseRepository->getToArrayFirst($coupons);

            /* 检查优惠券类型名称是否重复 */
            $is_only = Coupons::where('cou_name', $val)->where('ru_id', $coupons['ru_id'])->where('cou_id', $id)->count();
            if ($is_only > 0) {
                return make_json_error($GLOBALS['_LANG']['title_exist']);
            } else {
                Coupons::where('cou_id', $id)->update(['cou_name' => $val]);
                return make_json_result(stripslashes($val));
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑优惠券金额
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_type_money') {
            $check_auth = check_authz_json('coupons_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            /* 检查优惠券金额是否大于0 */
            if ($val <= 0) {
                return make_json_error($GLOBALS['_LANG']['type_money_error']);
            } else {
                BonusType::where('type_id', $id)->update(['type_money' => $val]);
                return make_json_result(number_format($val, 2));
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑订单下限
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_min_amount') {
            $check_auth = check_authz_json('coupons_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            if ($val < 0) {
                return make_json_error($GLOBALS['_LANG']['min_amount_empty']);
            } else {
                BonusType::where('type_id', $id)->update(['min_amount' => $val]);
                return make_json_result(number_format($val, 2));
            }
        }
        /*------------------------------------------------------ */
        //-- 优惠券添加页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            admin_priv('coupons_manage');

            $_REQUEST['type'] = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
            $this->smarty->assign('type', $_REQUEST['type']);

            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', lang('admin/coupons.coupon_add'));
            $this->smarty->assign('action_link', ['href' => 'coupons.php?act=list', 'text' => lang('admin/coupons.coupon_list')]);
            $this->smarty->assign('action', 'add');

            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);

            $next_month = local_strtotime('+1 months');
            $bonus_arr['send_start_date'] = local_date('Y-m-d H:i:s');
            $bonus_arr['use_start_date'] = local_date('Y-m-d H:i:s');
            $bonus_arr['send_end_date'] = local_date('Y-m-d H:i:s', $next_month);
            $bonus_arr['use_end_date'] = local_date('Y-m-d H:i:s', $next_month);
            $cou_arr = [
                'ru_id' => $adminru['ru_id']
            ];
            $this->smarty->assign('cou', $cou_arr);

            $rank_list = get_rank_list();
            $rank_list = $this->couponsService->get_rank_arr($rank_list);
            $this->smarty->assign('rank_list', $rank_list);

            set_default_filter(); //设置默认筛选


            return $this->smarty->display('coupons_type_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 优惠券添加的处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'insert') {
            $cou_name = isset($_POST['cou_name']) ? addslashes($_POST['cou_name']) : '';
            $type_name = isset($_POST['type_name']) ? addslashes($_POST['type_name']) : '';

            /* 获得日期信息 */
            $cou_start_time = local_strtotime($_POST['cou_start_time']);//优惠券有效期起始时间;
            $cou_end_time = local_strtotime($_POST['cou_end_time']);//优惠券有效期结束时间;
            $cou_add_time = gmtime();//添加时间;

            $cou_user_num = empty($_POST['cou_user_num']) ? 1 : $_POST['cou_user_num'];
            $cou_get_man = isset($_POST['cou_get_man']) && !empty($_POST['cou_get_man']) ? $_POST['cou_get_man'] : 0;

            $cou_goods = '';
            $spec_cat = '';
            $usableCouponGoods = empty($_POST['usableCouponGoods']) ? 1 : $_POST['usableCouponGoods'];
            if (!empty($_POST['cou_goods']) && $usableCouponGoods == 2) {
                $cou_goods = implode(',', array_unique($_POST['cou_goods']));
            } elseif (!empty($_POST['vc_cat']) && $usableCouponGoods == 3) {
                $spec_cat = implode(',', array_unique($_POST['vc_cat']));
            }

            $cou_ok_goods = '';
            $cou_ok_cat = '';
            $buyableCouponGoods = empty($_POST['buyableCouponGoods']) ? 1 : $_POST['buyableCouponGoods'];
            if (!empty($_POST['cou_ok_goods']) && $buyableCouponGoods == 2) {
                $cou_ok_goods = implode(',', array_unique($_POST['cou_ok_goods']));
            } elseif (!empty($_POST['vc_ok_cat']) && $buyableCouponGoods == 3) {
                $cou_ok_cat = implode(',', array_unique($_POST['vc_ok_cat']));
            } else {
                $cou_ok_goods = 0;
                $cou_ok_cat = '';
            }
            /*检查名称是否重复*/
            if ($_POST['cou_type'] == VOUCHER_SHOP_CONLLENT) {
                $count = Coupons::where('cou_type', VOUCHER_SHOP_CONLLENT)->where('ru_id', $adminru['ru_id'])->count();

                /*检查名称是否重复*/
                if ($count > 1) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['coupon_store_exist'], stripslashes($cou_name)), 1);
                }
            }

            /*检查名称是否重复*/
            $is_only = Coupons::where('cou_name', $cou_name)->where('ru_id', $adminru['ru_id'])->count();
            if ($is_only > 0) {
                return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($cou_name)), 1);
            }

            $cou_type = isset($_POST['cou_type']) ? intval($_POST['cou_type']) : 0;

            //注册送,全场送 默认所有会员等级可参加;
            if ($cou_type == VOUCHER_LOGIN || $cou_type == VOUCHER_ALL) {
                $rank_list = get_rank_list();
                $cou_ok_user = '';
                foreach ($rank_list as $k => $v) {
                    $cou_ok_user .= $k . ',';
                }
                $cou_ok_user = substr($cou_ok_user, 0, -1);
            } else {
                if ($cou_type == 4) {
                    $cou_ok_user = empty($_POST['cou_user_four']) ? '0' : implode(',', array_unique($_POST['cou_user_four']));
                } else {
                    $cou_ok_user = empty($_POST['cou_ok_user']) ? '0' : implode(',', array_unique($_POST['cou_ok_user']));
                }
            }

            /* 插入数据库。 */
            $other = [
                'cou_name' => $cou_name,
                'cou_total' => $_POST['cou_total'],
                'cou_man' => $_POST['cou_man'],
                'cou_money' => floatval($_POST['cou_money']),
                'cou_user_num' => $cou_user_num,
                'cou_goods' => $cou_goods,
                'spec_cat' => $spec_cat,
                'cou_start_time' => $cou_start_time,
                'cou_end_time' => $cou_end_time,
                'cou_type' => $cou_type,
                'cou_get_man' => $cou_get_man,
                'cou_ok_user' => $cou_ok_user,
                'cou_ok_goods' => $cou_ok_goods,
                'cou_ok_cat' => $cou_ok_cat,
                'cou_intro' => $_POST['cou_intro'] ?? '',
                'cou_add_time' => $cou_add_time,
                'ru_id' => $adminru['ru_id'],
                'cou_title' => $_POST['cou_title'],
                'review_status' => 3,
            ];
            $cou_id = Coupons::insertGetId($other);
            //录入包邮券，不包邮地区
            if ($cou_type == 5 && $cou_id > 0) {
                $region_list = !empty($_REQUEST['free_value']) ? trim($_REQUEST['free_value']) : '';
                $other = [
                    'cou_id' => $cou_id,
                    'region_list' => $region_list
                ];
                CouponsRegion::insert($other);
            }
            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'coupons.php?act=list';

            return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $cou_name . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 优惠券编辑页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            admin_priv('coupons_manage');

            /* 获取优惠券类型数据 bulu */
            $cou_id = !empty($_GET['cou_id']) ? intval($_GET['cou_id']) : 1;
            $cou_arr = Coupons::where('cou_id', $cou_id)->first();

            $cou_arr['cou_start_time'] = local_date('Y-m-d H:i:s', $cou_arr['cou_start_time']);
            $cou_arr['cou_end_time'] = local_date('Y-m-d H:i:s', $cou_arr['cou_end_time']);

            //允许领取优惠券的会员级别; bylu
            $rank_list = get_rank_list();
            $rank_list = $this->couponsService->get_rank_arr($rank_list, $cou_arr['cou_ok_user']);
            $this->smarty->assign('rank_list', $rank_list); //等级列表 bylu
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['cou_edit']);
            $this->smarty->assign('action_link', ['href' => 'coupons.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['cou_list']]);
            $this->smarty->assign('form_act', 'update');

            //可使用优惠券的条件 start
            //指定分类
            if ($cou_arr['spec_cat']) {
                $cou_arr['cats'] = explode(',', $cou_arr['spec_cat']);
                $cou_arr['cats'] = get_choose_cat($cou_arr['spec_cat']);
            } //指定商品
            elseif ($cou_arr['cou_goods']) {
                $cou_arr['cou_goods'] = explode(',', $cou_arr['cou_goods']);
                $cou_arr['goods'] = get_choose_goods($cou_arr['cou_goods']);
            }
            //可使用优惠券的条件 end

            //可获得优惠券的条件 start
            //指定分类
            if ($cou_arr['cou_ok_cat']) {
                $cou_arr['ok_cat'] = get_choose_cat($cou_arr['cou_ok_cat']);
            } //指定商品
            elseif ($cou_arr['cou_ok_goods']) {
                $cou_arr['ok_goods'] = get_choose_goods($cou_arr['cou_ok_goods']);
            }
            //可获得优惠券的条件 end

            if ($cou_arr['cou_type'] == VOUCHER_SHIPPING) {
                $region_arr = $this->couponsService->getCouponsRegionList($cou_id);
                $cou_arr['free_value'] = $region_arr['free_value'];
                $cou_arr['free_value_name'] = $region_arr['free_value_name'];
            }

            $this->smarty->assign('cou', $cou_arr);
            $this->smarty->assign('cou_act', 'edit');
            $this->smarty->assign('type', $cou_arr['cou_type'] == VOUCHER_LOGIN ? 'register' : ($cou_arr['cou_type'] == VOUCHER_SHOPING ? 'buy' : ($cou_arr['cou_type'] == VOUCHER_ALL ? 'all' : ($cou_arr['cou_type'] == VOUCHER_USER ? 'member' : ''))));
            $this->smarty->assign('cou_type', $cou_arr['cou_type']);

            set_default_filter(); //设置默认筛选


            return $this->smarty->display('coupons_type_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 优惠券编辑的处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'update') {
            $cou_name = isset($_POST['cou_name']) ? addslashes($_POST['cou_name']) : '';
            $cou_intro = isset($_POST['cou_intro']) ? addslashes($_POST['cou_intro']) : '';
            $type_name = isset($_POST['type_name']) ? addslashes($_POST['type_name']) : '';
            $cou_id = empty($_REQUEST['cou_id']) ? '' : intval($_REQUEST['cou_id']);
            /* 获得日期信息 */
            $cou_start_time = local_strtotime($_POST['cou_start_time']);//优惠券有效期起始时间;
            $cou_end_time = local_strtotime($_POST['cou_end_time']);//优惠券有效期结束时间;
            $cou_add_time = gmtime();//添加时间;

            $cou_user_num = empty($_POST['cou_user_num']) ? 1 : $_POST['cou_user_num'];
            $cou_get_man = isset($_POST['cou_get_man']) && !empty($_POST['cou_get_man']) ? $_POST['cou_get_man'] : 0;

            $cou_goods = '';
            $spec_cat = '';
            $usableCouponGoods = empty($_POST['usableCouponGoods']) ? 1 : $_POST['usableCouponGoods'];
            if (!empty($_POST['cou_goods']) && $usableCouponGoods == 2) {
                $cou_goods = implode(',', array_unique($_POST['cou_goods']));
            } elseif (!empty($_POST['vc_cat']) && $usableCouponGoods == 3) {
                $spec_cat = implode(',', array_unique($_POST['vc_cat']));
            }

            $cou_ok_goods = '';
            $cou_ok_cat = '';
            $buyableCouponGoods = empty($_POST['buyableCouponGoods']) ? 1 : $_POST['buyableCouponGoods'];
            if (!empty($_POST['cou_ok_goods']) && $buyableCouponGoods == 2) {
                $cou_ok_goods = implode(',', array_unique($_POST['cou_ok_goods']));
            } elseif (!empty($_POST['vc_ok_cat']) && $buyableCouponGoods == 3) {
                $cou_ok_cat = implode(',', array_unique($_POST['vc_ok_cat']));
            } else {
                $cou_ok_goods = 0;
                $cou_ok_cat = '';
            }

            $coupons = Coupons::where('cou_id', $cou_id);
            $coupons = $this->baseRepository->getToArrayFirst($coupons);

            /*检查名称是否重复*/
            $is_only = Coupons::where('cou_name', $cou_name)->where('ru_id', $coupons['ru_id'])->where('cou_id', '<>', $cou_id)->count();
            if ($is_only > 0) {
                return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($cou_name)), 1);
            }

            $cou_type = isset($_POST['cou_type']) ? intval($_POST['cou_type']) : 0;

            //注册送,全场送 默认所有会员等级可参加;
            if ($cou_type == VOUCHER_LOGIN || $cou_type == VOUCHER_ALL) {
                $rank_list = get_rank_list();
                $cou_ok_user = '';
                foreach ($rank_list as $k => $v) {
                    $cou_ok_user .= $k . ',';
                }
                $cou_ok_user = substr($cou_ok_user, 0, -1);
            } else {
                if ($cou_type == VOUCHER_USER) {
                    $cou_ok_user = empty($_POST['cou_user_four']) ? 0 : implode(',', array_unique($_POST['cou_user_four']));
                } else {
                    $cou_ok_user = empty($_POST['cou_ok_user']) ? 0 : implode(',', array_unique($_POST['cou_ok_user']));
                }
            }

            /* 更新数据 */
            $record = [
                'cou_name' => $cou_name,
                'cou_title' => $_POST['cou_title'],
                'cou_total' => $_POST['cou_total'],
                'cou_man' => $_POST['cou_man'],
                'cou_money' => $_POST['cou_money'],
                'cou_user_num' => $cou_user_num,
                'cou_goods' => $cou_goods,
                'spec_cat' => $spec_cat,
                'cou_start_time' => $cou_start_time,
                'cou_end_time' => $cou_end_time,
                'cou_type' => $cou_type,
                'cou_get_man' => $cou_get_man,
                'cou_ok_user' => $cou_ok_user,
                'cou_ok_goods' => $cou_ok_goods,
                'cou_ok_cat' => $cou_ok_cat,
                'cou_intro' => $cou_intro,
                'cou_add_time' => $cou_add_time
            ];

            if (isset($_POST['review_status'])) {
                $review_status = !empty($_POST['review_status']) ? intval($_POST['review_status']) : 1;
                $review_content = !empty($_POST['review_content']) ? addslashes(trim($_POST['review_content'])) : '';

                $record['review_status'] = $review_status;
                $record['review_content'] = $review_content;
            }

            Coupons::where('cou_id', $cou_id)->update($record);
            //录入包邮券，不包邮地区
            if ($cou_type == 5) {
                $region_list = !empty($_REQUEST['free_value']) ? trim($_REQUEST['free_value']) : '';
                $count_free = CouponsRegion::where('cou_id', $cou_id)->count();
                if ($count_free > 0) {
                    CouponsRegion::where('cou_id', $cou_id)->update(['region_list' => $region_list]);
                } else {
                    $other = [
                        'cou_id' => $cou_id,
                        'region_list' => $region_list
                    ];
                    CouponsRegion::insert($other);
                }
            }
            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'coupons.php?act=list';

            return sys_msg($GLOBALS['_LANG']['edit'] . "&nbsp;" . $cou_name . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }
        /*------------------------------------------------------ */
        //-- 修改秒杀优惠券排序 bylu
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'change_order') {
            $cou_id = $_REQUEST['cou_id'];
            $cou_order = $_REQUEST['cou_order'];
            $res = Coupons::where('cou_id', $cou_id)->update(['cou_order' => $cou_order]);
            if ($res > 0) {
                echo $data = 'ok';
            }
        }
        /*------------------------------------------------------ */
        //-- 优惠券列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'coupons_list') {
            $cou_id = intval($_GET['cou_id']);

            $list = $this->couponsService->get_coupons_info2($cou_id);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['coupons_list']);
            $this->smarty->assign('action_link', ['href' => 'coupons.php?act=list', 'text' => $GLOBALS['_LANG']['coupons_list']]);
            $this->smarty->assign('coupons_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('cou_id', $cou_id);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('coupons_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 会员优惠券列表翻页、排序(用于ajax返回)
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'user_query_coupons') {
            $cou_id = intval($_REQUEST['cou_id']);
            $list = $this->couponsService->get_coupons_info2($cou_id);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['coupons_list']);
            $this->smarty->assign('action_link', ['href' => 'coupons.php?act=list', 'text' => $GLOBALS['_LANG']['coupons_list']]);

            $this->smarty->assign('coupons_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('coupons_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }
        /*------------------------------------------------------ */
        //-- 删除优惠券
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_coupons') {
            $cou_id = isset($_GET['cou_id']) ? intval($_GET['cou_id']) : 0;
            if ($cou_id) {
                $res = Coupons::where('cou_id', $cou_id)->delete();
                if ($res) {
                    return 'ok';
                }
            }
            $uc_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($uc_id) {
                $cou_id = CouponsUser::where('uc_id', $uc_id)->value('cou_id');
                $res = CouponsUser::where('uc_id', $uc_id)->delete();
                $url = "coupons.php?act=user_query_coupons&cou_id=" . $cou_id . str_replace('act=remove_coupons', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            }
        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            check_authz_json('presale');

            $checkboxes = request()->input('checkboxes', 0);
            $type = request()->input('type', '');

            if (!is_array($checkboxes)) {
                return sys_msg($GLOBALS['_LANG']['not_select_data'], 1);
            }

            $ids = !empty($checkboxes) ? $checkboxes : 0;

            if ($type) {
                // 删除
                if ($type == 'batch_remove') {
                    Coupons::whereIn('cou_id', $ids)->delete();
                    clear_cache_files();
                    /* 提示信息 */
                    $link[0]['text'] = $GLOBALS['_LANG']['back_coupons_list'];
                    $link[0]['href'] = 'coupons.php?act=list';

                    return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], count($ids)), 0, $link);
                } // 审核
                elseif ($type == 'review_to') {
                    $review_status = request()->input('review_status', 1);
                    $res = Coupons::whereIn('cou_id', $ids)->update(['review_status' => $review_status]);
                    if ($res) {
                        /* 提示信息 */
                        $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                        $link[0]['href'] = 'coupons.php?act=list&seller_list=1';

                        return sys_msg($GLOBALS['_LANG']['coupons_adopt_status_set_success'], 0, $link);

                    }
                }
            }
        }

    }

}
