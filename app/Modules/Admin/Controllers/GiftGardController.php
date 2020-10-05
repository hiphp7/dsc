<?php

namespace App\Modules\Admin\Controllers;

use App\Models\BonusType;
use App\Models\GiftGardLog;
use App\Models\GiftGardType;
use App\Models\Goods;
use App\Models\UserBonus;
use App\Models\UserGiftGard;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Services\Common\CommonManageService;
use App\Services\Common\OfficeService;
use App\Services\Gift\GiftGardManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;

/**
 * 礼品卡处理
 */
class GiftGardController extends InitController
{
    protected $commonManageService;
    protected $merchantCommonService;
    protected $baseRepository;
    protected $giftGardManageService;
    protected $storeCommonService;

    public function __construct(
        CommonManageService $commonManageService,
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        GiftGardManageService $giftGardManageService,
        StoreCommonService $storeCommonService
    )
    {
        $this->commonManageService = $commonManageService;
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->giftGardManageService = $giftGardManageService;
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

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        /*------------------------------------------------------ */
        //-- 礼品卡类型列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['gift_gard_type_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['gift_gard_type_add'], 'href' => 'gift_gard.php?act=add']);
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['take_list'], 'href' => 'gift_gard.php?act=take_list']);
            $this->smarty->assign('full_page', 1);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $list = $this->giftGardManageService->getGiftGardTypeList($adminru['ru_id']);

            $this->smarty->assign('type_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()));


            return $this->smarty->display('gift_gard_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页、排序
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'query') {
            $list = $this->giftGardManageService->getGiftGardTypeList($adminru['ru_id']);

            $this->smarty->assign('type_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('gift_gard_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 编辑礼品卡类型名称
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_type_name') {
            $check_auth = check_authz_json('gift_gard_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = json_str_iconv(trim($_POST['val']));

            /* 检查红包类型名称是否重复 */
            $res = GiftGardType::where('gift_name', $val)->where('gift_id', '<>', $id)->count();
            if ($res > 0) {
                return make_json_error($GLOBALS['_LANG']['type_name_exist']);
            } else {
                $data = ['gift_name' => $val];
                GiftGardType::where('gift_id', $id)->update($data);

                return make_json_result(stripslashes($val));
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑订单号
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_type_money') {
            $check_auth = check_authz_json('gift_gard_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            $data = ['express_no' => $val];
            UserGiftGard::where('gift_gard_id', $id)->update($data);

            return make_json_result(stripslashes($val));
        }

        if ($_REQUEST['act'] == 'confirm_ship') {
            $check_auth = check_authz_json('gift_gard_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = isset($_GET['id']) && !empty($_GET['id']) ? intval($_GET['id']) : 0;
            $val = isset($_GET['val']) && !empty($_GET['val']) ? floatval($_GET['val']) : 0;

            if (!empty($id)) {
                $status = UserGiftGard::where('gift_gard_id', $id)->value('status');
                $status = $status ?? 0;
                if ($status == 3) {
                    // 			return make_json_error('此订单已经完成，不能取消发货');
                } elseif ($status == 1) {
                    $data = ['status' => 2];
                    UserGiftGard::where('gift_gard_id', $id)->update($data);

                    $val = 2;
                } elseif ($status == 2) {
                    $data = ['status' => 1];
                    UserGiftGard::where('gift_gard_id', $id)->update($data);

                    $val = 1;
                } else {
                    return make_json_error($GLOBALS['_LANG']['delivery_fail']);
                }
            } else {
                return make_json_error($GLOBALS['_LANG']['order_not_existent']);
            }

            if ($val) {
                $gitLog = [
                    'admin_id' => session('admin_id'),
                    'gift_gard_id' => $id,
                    'delivery_status' => $val,
                    'addtime' => gmtime(),
                    'handle_type' => 'gift_gard'
                ];
                GiftGardLog::insert($gitLog);
            }

            $url = 'gift_gard.php?act=query_take&' . str_replace('act=confirm_ship', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*操作记录日志列表   by kong start*/
        if ($_REQUEST['act'] == 'handle_log') {
            /* 权限判断 */
            admin_priv('gift_gard_manage');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['handle_log']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['take_list'], 'href' => 'gift_gard.php?act=take_list']);

            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $gift_gard_log = $this->giftGardManageService->getAdminGiftGardLog($id);

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('gift_gard_log', $gift_gard_log['pzd_list']);
            $this->smarty->assign('filter', $gift_gard_log['filter']);
            $this->smarty->assign('record_count', $gift_gard_log['record_count']);
            $this->smarty->assign('page_count', $gift_gard_log['page_count']);

            return $this->smarty->display("gift_gard_log.dwt");
        }

        if ($_REQUEST['act'] == 'Ajax_handle_log') {
            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $gift_gard_log = $this->commonManageService->getGiftGardLog($id);

            $this->smarty->assign('gift_gard_log', $gift_gard_log['pzd_list']);
            $this->smarty->assign('filter', $gift_gard_log['filter']);
            $this->smarty->assign('record_count', $gift_gard_log['record_count']);
            $this->smarty->assign('page_count', $gift_gard_log['page_count']);


            return make_json_result(
                $this->smarty->fetch('gift_gard_log.htm'),
                '',
                ['filter' => $gift_gard_log['filter'], 'page_count' => $gift_gard_log['page_count']]
            );
        }
        /*------------------------------------------------------ */
        //-- 编辑订单下限
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_min_amount') {
            $check_auth = check_authz_json('gift_gard_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            if ($val < 0) {
                return make_json_error($GLOBALS['_LANG']['min_amount_empty']);
            } else {
                $data = ['min_amount' => $val];
                GiftGardType::where('gift_id', $id)->update($data);

                return make_json_result(number_format($val, 2));
            }
        }

        /*------------------------------------------------------ */
        //-- 删除礼品卡类型
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('gift_gard_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            GiftGardType::where('gift_id', $id)->delete();

            /* 删除礼品卡 */
            UserGiftGard::where('gift_id', $id)->delete();
            $url = 'gift_gard.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }



        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            $check_auth = check_authz_json('gift_gard_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg(lang('admin/gift_gard.empty_data'), 1);
            }
            $ids = !empty($_POST['checkboxes']) ? $_POST['checkboxes'] : 0;

            if (isset($_POST['type'])) {
                // 删除
                if ($_POST['type'] == 'batch_remove') {
                    /* 删除记录 */
                    $ids = $this->baseRepository->getExplode($ids);
                    $res = GiftGardType::whereIn('gift_id', $ids)->delete();
                    if ($res) {
                        UserGiftGard::whereIn('gift_id', $ids)->delete();
                    }

                    /* 记日志 */
                    admin_log('', 'batch_remove', 'gift_gard_manage');

                    /* 清除缓存 */
                    clear_cache_files();

                    $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'gift_gard.php?act=list&' . list_link_postfix()];
                    return sys_msg($GLOBALS['_LANG']['batch_drop_ok'], 0, $links);
                } // 审核
                elseif ($_POST['type'] == 'review_to') {
                    // review_status = 3审核通过 2审核未通过
                    $review_status = $_POST['review_status'];

                    $ids = $this->baseRepository->getExplode($ids);
                    $data = ['review_status' => $review_status];
                    $res = GiftGardType::whereIn('gift_id', $ids)->update($data);

                    if ($res > 0) {
                        $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'gift_gard.php?act=list&seller_list=1&' . list_link_postfix()];
                        return sys_msg($GLOBALS['_LANG']['gift_card_success'], 0, $lnk);
                    }
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 礼品卡类型添加页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            admin_priv('gift_gard_manage');

            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['gift_gard_type_add']);
            $this->smarty->assign('action_link', ['href' => 'gift_gard.php?act=list', 'text' => $GLOBALS['_LANG']['gift_gard_type_list']]);
            $this->smarty->assign('action', 'add');

            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);

            $next_month = local_strtotime('+1 months');
            $bonus_arr['send_start_date'] = local_date($GLOBALS['_CFG']['time_format']);
            $bonus_arr['use_start_date'] = local_date($GLOBALS['_CFG']['time_format']);
            $bonus_arr['send_end_date'] = local_date($GLOBALS['_CFG']['time_format'], $next_month);
            $bonus_arr['use_end_date'] = local_date($GLOBALS['_CFG']['time_format'], $next_month);

            $this->smarty->assign('bonus_arr', $bonus_arr);


            return $this->smarty->display('gift_gard_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 礼品卡添加页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'gift_add') {
            admin_priv('gift_gard_manage');

            $gift_id = $_GET['bonus_type'];

            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['gift_gard_add']);
            $this->smarty->assign('action_link', ['href' => 'gift_gard.php?act=bonus_list&bonus_type=' . $gift_id, 'text' => $GLOBALS['_LANG']['gift_gard_list']]);
            $this->smarty->assign('action', 'add');

            $this->smarty->assign('form_act', 'gift_insert');
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
            $this->smarty->assign('gift_id', $gift_id);


            return $this->smarty->display('gift_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 礼品卡添加的处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'gift_insert') {
            /* 去掉红包类型名称前后的空格 */
            $gift_sn = !empty($_POST['gift_sn']) ? trim($_POST['gift_sn']) : '';
            $gift_pwd = !empty($_POST['gift_pwd']) ? trim($_POST['gift_pwd']) : '';
            $gift_id = !empty($_POST['type_id']) ? trim($_POST['type_id']) : '0';

            /* 检查礼品卡是否有重复 */
            $res = UserGiftGard::where('gift_sn', $gift_sn)->count();
            if ($res > 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['gift_name_exist'], 0, $link);
            }

            $res = UserGiftGard::where('gift_password', $gift_pwd)->count();
            if ($res > 0) {
                $link[0] = ['text' => $GLOBALS['_LANG']['back_gift_add'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['gift_pwd_exist'], 0, $link);
            }

            /* 插入数据库。 */
            $data = [
                'gift_sn' => $gift_sn,
                'gift_password' => $gift_pwd,
                'gift_id' => $gift_id
            ];
            UserGiftGard::insert($data);

            /* 记录管理员操作 */
            admin_log($_POST['gift_sn'], 'add', 'giftgardtype');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[0]['text'] = $GLOBALS['_LANG']['back_continue_add'];
            $link[0]['href'] = 'gift_gard.php?act=gift_add&bonus_type=' . $gift_id;

            $link[1]['text'] = $GLOBALS['_LANG']['gift_list'];
            $link[1]['href'] = 'gift_gard.php?act=bonus_list&bonus_type=' . $gift_id;

            return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['gift_sn'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 礼品卡类型添加的处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'insert') {
            /* 去掉红包类型名称前后的空格 */
            $type_name = !empty($_POST['type_name']) ? trim($_POST['type_name']) : '';
            $gift_number = !empty($_POST['gift_number']) ? trim($_POST['gift_number']) : '';
            $type_money = !empty($_POST['type_money']) ? trim($_POST['type_money']) : '';

            /* 检查类型是否有重复 */
            $res = GiftGardType::where('gift_name', $type_name)->count();
            if ($res > 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['type_name_exist'], 0, $link);
            }

            /* 获得日期信息 */
            $gift_startdate = local_strtotime($_POST['use_start_date']);
            $gift_enddate = local_strtotime($_POST['use_end_date']);

            $_POST['type_money'] = isset($_POST['type_money']) && !empty($_POST['type_money']) ? $_POST['type_money'] : '';

            /* 插入数据库。 */

            $data = [
                'ru_id' => $adminru['ru_id'],
                'gift_name' => $type_name,
                'gift_menory' => $type_money,
                'gift_start_date' => $gift_startdate,
                'gift_end_date' => $gift_enddate,
                'gift_number' => $gift_number,
                'review_status' => 3
            ];
            $gift_id = GiftGardType::insertGetId($data);
            /* 生成红包序列号 */

            $num = UserGiftGard::selectRaw('MAX(gift_sn) as gift_sn')->value('gift_sn');
            $num = $num ? floor($num / 1) : 8000000;

            for ($i = 1, $j = 0; $i <= $gift_number; $i++) {
                $gift_sn = ($num + $i);
                $gift_pwd = $this->giftGardManageService->generatePassword(6);

                $data = [
                    'gift_sn' => $gift_sn,
                    'gift_password' => $gift_pwd,
                    'gift_id' => $gift_id
                ];
                UserGiftGard::insert($data);
                $j++;
            }

            /* 记录管理员操作 */
            admin_log($_POST['type_name'], 'add', 'giftgardtype');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[0]['text'] = $GLOBALS['_LANG']['back_continue_add'];
            $link[0]['href'] = 'gift_gard.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['gift_list'];
            $link[1]['href'] = 'gift_gard.php?act=list';

            return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['type_name'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 礼品卡类型编辑页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            admin_priv('gift_gard_manage');

            /* 获取红包类型数据 */
            $type_id = !empty($_GET['type_id']) ? intval($_GET['type_id']) : 0;

            $res = GiftGardType::where('gift_id', $type_id);
            $bonus_arr = $this->baseRepository->getToArrayFirst($res);

            $bonus_arr['use_start_date'] = local_date($GLOBALS['_CFG']['time_format'], $bonus_arr['gift_start_date']);
            $bonus_arr['use_end_date'] = local_date($GLOBALS['_CFG']['time_format'], $bonus_arr['gift_end_date']);

            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', lang('admin/gift_gard.edit_type'));
            $this->smarty->assign('action_link', ['href' => 'gift_gard.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['gift_gard_type_list']]);
            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('bonus_arr', $bonus_arr);


            return $this->smarty->display('gift_gard_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 礼品卡类型编辑的处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'update') {
            /* 获得日期信息 */
            $use_startdate = local_strtotime($_POST['use_start_date']);
            $use_enddate = local_strtotime($_POST['use_end_date']);

            /* 对数据的处理 */
            $type_name = !empty($_POST['type_name']) ? trim($_POST['type_name']) : '';
            $type_id = !empty($_POST['type_id']) ? intval($_POST['type_id']) : 0;
            $gift_number = !empty($_POST['gift_number']) ? intval($_POST['gift_number']) : 0;

            $_POST['type_money'] = isset($_POST['type_money']) && empty($_POST['type_money']) ? $_POST['type_money'] : '';

            /* 更新数据 */
            $record = [
                'gift_name' => $type_name,
                'gift_menory' => $_POST['type_money'],
                'gift_start_date' => $use_startdate,
                'gift_end_date' => $use_enddate,
            ];

            if (isset($_POST['review_status'])) {
                $review_status = !empty($_POST['review_status']) ? intval($_POST['review_status']) : 1;
                $review_content = !empty($_POST['review_content']) ? addslashes(trim($_POST['review_content'])) : '';

                $record['review_status'] = $review_status;
                $record['review_content'] = $review_content;
            }

            GiftGardType::where('gift_id', $type_id)->update($record);

            //再增加的数量
            /* 生成红包序列号 */
            $num = UserGiftGard::selectRaw('MAX(gift_sn) as gift_sn')->value('gift_sn');
            $num = $num ? floor($num / 1) : 8000000;

            for ($i = 1, $j = 0; $i <= $gift_number; $i++) {
                $gift_sn = ($num + $i);
                $gift_pwd = $this->giftGardManageService->generatePassword(6);
                $data = [
                    'gift_sn' => $gift_sn,
                    'gift_password' => $gift_pwd,
                    'gift_id' => $type_id
                ];
                UserGiftGard::insert($data);

                $j++;
            }

            /* 记录管理员操作 */
            admin_log($_POST['type_name'], 'edit', 'giftgrad');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['gift_list'], 'href' => 'gift_gard.php?act=list&' . list_link_postfix()];
            return sys_msg($GLOBALS['_LANG']['edit'] . ' ' . $_POST['type_name'] . ' ' . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /* ------------------------------------------------------ */
        //-- �        �送商品页面
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'send') {
            admin_priv('gift_gard_manage');

            /* 取得参数 */
            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;


            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['gift_from_goods']);
            $this->smarty->assign('action_link', ['href' => 'gift_gard.php?act=list', 'text' => $GLOBALS['_LANG']['gift_gard_type_list']]);

            /* 查询此红包类型信息 */
            $res = GiftGardType::where('gift_id', $id);
            $bonus_type = $this->baseRepository->getToArrayFirst($res);

            /* 查询礼品卡列表 */
            $goods_list = $this->giftGardManageService->getGiftGardList($id);

            /* 查询其他红包类型的商品 */

            $res = Goods::select('goods_id')->where('bonus_type_id', '>', 0)->where('bonus_type_id', '<>', $id);
            $other_goods_list = $this->baseRepository->getToArrayGet($res);
            $other_goods_list = $this->baseRepository->getFlatten($other_goods_list);

            $this->smarty->assign('other_goods', ($other_goods_list === false) ? '' : join(',', $other_goods_list));

            /* 模板赋值 */
            set_default_filter(); //设置默认筛选

            $this->smarty->assign('bonus_type', $bonus_type);
            $this->smarty->assign('goods_list', $goods_list);

            return $this->smarty->display('gift_gard_by_goods.dwt');
        }

        /*------------------------------------------------------ */
        //-- 处理礼品卡的发送页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'send_by_user') {
            $user_list = [];
            $start = empty($_REQUEST['start']) ? 0 : intval($_REQUEST['start']);
            $limit = empty($_REQUEST['limit']) ? 10 : intval($_REQUEST['limit']);
            $validated_email = empty($_REQUEST['validated_email']) ? 0 : intval($_REQUEST['validated_email']);
            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $send_count = 0;

            if (isset($_REQUEST['send_rank'])) {
                /* 按会员等级来发放红包 */
                $rank_id = intval($_REQUEST['rank_id']);

                if ($rank_id > 0) {
//                    $res = UserRank::select('min_points', 'max_points', 'special_rank')->where('rank_id', $rank_id);
//                    $row = $this->baseRepository->getToArrayFirst($res);

//                    if ($row['special_rank']) {
                    /* 特殊会员组处理 */
                    $send_count = Users::where('user_rank', $rank_id)->count();

                    if ($validated_email) {
                        $res = Users::select('user_id', 'email', 'user_name')
                            ->where('user_rank', $rank_id)->where('is_validated', 1)
                            ->offset($start)->limit($limit);
                    } else {
                        $res = Users::select('user_id', 'email', 'user_name')
                            ->where('user_rank', $rank_id)
                            ->offset($start)->limit($limit);
                    }
//                    } else {
//                        $send_count = Users::where('rank_points', '>=', intval($row['min_points']))
//                            ->where('rank_points', '<', intval($row['max_points']))->count();
//
//                        if ($validated_email) {
//                            $res = Users::select('user_id', 'email', 'user_name')
//                                ->where('rank_points', '>=', intval($row['min_points']))
//                                ->where('rank_points', '<', intval($row['max_points']))
//                                ->where('is_validated', 1)
//                                ->offset($start)->limit($limit);
//                        } else {
//                            $res = Users::select('user_id', 'email', 'user_name')
//                                ->where('rank_points', '>=', intval($row['min_points']))
//                                ->where('rank_points', '<', intval($row['max_points']))
//                                ->offset($start)->limit($limit);
//                        }
//                    }

                    $user_list = $this->baseRepository->getToArrayGet($res);

                    $count = count($user_list);
                }
            } elseif (isset($_REQUEST['send_user'])) {
                /* 按会员列表发放红包 */
                /* 如果是空数组，直接返回 */
                if (empty($_REQUEST['user'])) {
                    return sys_msg($GLOBALS['_LANG']['send_user_empty'], 1);
                }

                $user_array = (is_array($_REQUEST['user'])) ? $_REQUEST['user'] : explode(',', $_REQUEST['user']);
                $send_count = count($user_array);

                $id_array = array_slice($user_array, $start, $limit);

                /* 根据会员ID取得用户名和邮件地址 */
                $res = Users::select('user_id', 'email', 'user_name')->whereIn('user_id', $id_array);
                $user_list = $this->baseRepository->getToArrayGet($res);

                $count = count($user_list);
            }

            /* 发送红包 */
            $loop = 0;
            $bonus_type = $this->giftGardManageService->GiftGardTypeInfo($id);

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

                if ($this->giftGardManageService->addToMailList($val['user_name'], $val['email'], $tpl['template_subject'], $content, $tpl['is_html'])) {
                    /* 向会员红包表录入数据 */
                    $data = [
                        'bonus_type_id' => $id,
                        'bonus_sn' => 0,
                        'user_id' => $val['user_id'],
                        'used_time' => 0,
                        'order_id' => 0,
                        'emailed' => BONUS_MAIL_SUCCEED
                    ];
                    UserBonus::insert($data);
                } else {
                    /* 邮件发送失败，更新数据库 */

                    $data = [
                        'bonus_type_id' => $id,
                        'bonus_sn' => 0,
                        'user_id' => $val['user_id'],
                        'used_time' => 0,
                        'order_id' => 0,
                        'emailed' => BONUS_MAIL_FAIL
                    ];
                    UserBonus::insert($data);
                }

                if ($loop >= $limit) {
                    break;
                } else {
                    $loop++;
                }
            }

            //admin_log(addslashes($GLOBALS['_LANG']['send_bonus']), 'add', 'bonustype');
            if ($send_count > ($start + $limit)) {
                /*  */
                $href = "bonus.php?act=send_by_user&start=" . ($start + $limit) . "&limit=$limit&id=$_REQUEST[id]&";

                if (isset($_REQUEST['send_rank'])) {
                    $href .= "send_rank=1&rank_id=$rank_id";
                }

                if (isset($_REQUEST['send_user'])) {
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

        if ($_REQUEST['act'] == 'send_mail') {
            /* 取得参数：红包id */
            $bonus_id = intval($_REQUEST['bonus_id']);
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
            $count = $this->giftGardManageService->sendBonusMail($bonus['bonus_type_id'], [$bonus_id]);

            $link[0]['text'] = $GLOBALS['_LANG']['back_bonus_list'];
            $link[0]['href'] = 'bonus.php?act=bonus_list&bonus_type=' . $bonus['bonus_type_id'];

            return sys_msg(sprintf($GLOBALS['_LANG']['success_send_mail'], $count), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 按印刷品发放红包
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'send_by_print') {
            @set_time_limit(0);

            /* 红下红包的类型ID和生成的数量的处理 */
            $bonus_typeid = !empty($_POST['bonus_type_id']) ? $_POST['bonus_type_id'] : 0;
            $bonus_sum = !empty($_POST['bonus_sum']) ? $_POST['bonus_sum'] : 1;

            /* 生成红包序列号 */
            $num = UserBonus::selectRaw('MAX(bonus_sn) as bonus_sn')->value('bonus_sn');
            $num = $num ? floor($num / 10000) : 100000;

            for ($i = 0, $j = 0; $i < $bonus_sum; $i++) {
                $bonus_sn = ($num + $i) . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

                $data = [
                    'bonus_type_id' => $bonus_typeid,
                    'bonus_sn' => $bonus_sn
                ];
                UserBonus::insert($data);

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
        if ($_REQUEST['act'] == 'gen_excel') {
            @set_time_limit(0);

            /* 获得此线下红包类型的ID */
            $tid = !empty($_GET['tid']) ? intval($_GET['tid']) : 0;
            $type_name = BonusType::where('type_id', $tid)->value('type_name');

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
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['record_id']) . "\t";
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['bonus_sn']) . "\t";
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['address']) . "\t";
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['consignee_name']) . "\t";
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['mobile']) . "\t";
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['gift_user_name']) . "\t";
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['gift_goods_name']) . "\t";
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['gift_user_time']) . "\t";
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['confirm_ship']) . "\t";
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['express_no']) . "\t";
                echo dsc_iconv('UTF8', 'GB2312', $GLOBALS['_LANG']['status']) . "\t\n";
            } else {
                echo $GLOBALS['_LANG']['bonus_excel_file'] . "\t\n";
                /* 红包序列号, 红包金额, 类型名称(红包名称), 使用结束日期 */
                echo $GLOBALS['_LANG']['record_id'] . "\t";
                echo $GLOBALS['_LANG']['bonus_sn'] . "\t";
                echo $GLOBALS['_LANG']['address'] . "\t";
                echo $GLOBALS['_LANG']['consignee_name'] . "\t";
                echo $GLOBALS['_LANG']['mobile'] . "\t";
                echo $GLOBALS['_LANG']['gift_user_name'] . "\t";
                echo $GLOBALS['_LANG']['gift_goods_name'] . "\t";
                echo $GLOBALS['_LANG']['gift_user_time'] . "\t";
                echo $GLOBALS['_LANG']['confirm_ship'] . "\t";
                echo $GLOBALS['_LANG']['express_no'] . "\t";
                echo $GLOBALS['_LANG']['status'] . "\t\n";
            }

            $val = [];

            $res = UserBonus::select('bonus_id', 'bonus_type_id', 'bonus_sn');
            $res = $res->where('bonus_type_id', $tid);
            $res = $res->with(['getBonusType' => function ($query) {
                $query->select('type_id', 'type_name', 'type_money', 'use_end_date');
            }]);
            $res = $this->baseRepository->getToArrayGet($res);

            $code_table = [];
            foreach ($res as $val) {
                $val['type_name'] = '';
                $val['type_money'] = 0;
                $val['use_end_date'] = 0;
                if (isset($val['get_bonus_type']) && !empty($val['get_bonus_type'])) {
                    $val['type_name'] = $val['get_bonus_type']['type_name'];
                    $val['type_money'] = $val['get_bonus_type']['type_money'];
                    $val['use_end_date'] = $val['get_bonus_type']['use_end_date'];
                }

                echo $val['bonus_sn'] . "\t";
                echo $val['type_money'] . "\t";
                if (!isset($code_table[$val['type_name']])) {
                    if (EC_CHARSET != 'gbk') {
                        $code_table[$val['type_name']] = dsc_iconv('UTF8', 'GB2312', $val['type_name']);
                    } else {
                        $code_table[$val['type_name']] = $val['type_name'];
                    }
                }
                echo $code_table[$val['type_name']] . "\t";
                echo local_date('Y-m-d', $val['use_end_date']);
                echo "\t\n";
            }
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'get_goods_list') {

            $filters = dsc_decode($_GET['JSON']);

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
        //-- 添加发放礼品卡的商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add_bonus_goods') {
            $check_auth = check_authz_json('gift_gard_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $add_ids = dsc_decode($_GET['add_ids'], true);
            $args = dsc_decode($_GET['JSON'], true);

            $type_id = explode(',', trim($args));
            foreach ($type_id as $key => $val) {
                $add_ids_str = implode(',', $add_ids);

                $res = UserGiftGard::where('gift_gard_id', $val);
                $config_goods = $this->baseRepository->getToArrayFirst($res);

                if ($config_goods['config_goods_id'] && $add_ids_str) {
                    $add_ids_str .= ',' . $config_goods['config_goods_id'];
                }

                $data = ['config_goods_id' => $add_ids_str];
                UserGiftGard::where('gift_gard_id', $val)->update($data);
            }

            clear_all_files();
            /* 重新载入 */
        }

        /*------------------------------------------------------ */
        //-- 删除发放礼品卡的商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'drop_bonus_goods') {

            $check_auth = check_authz_json('gift_gard_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $drop_ids = dsc_decode($_GET['drop_ids'], true);
            $args = dsc_decode($_GET['JSON'], true);

            $type_id = explode(',', trim($args));

            foreach ($type_id as $key => $val) {

                $res = UserGiftGard::where('gift_gard_id', $val);
                $config_goods = $this->baseRepository->getToArrayFirst($res);

                $config_goods_arr = explode(',', $config_goods['config_goods_id']);
                $config_goods_id = array_diff($config_goods_arr, $drop_ids);
                $config_goods_id = implode(',', $config_goods_id);

                $data = ['config_goods_id' => $config_goods_id];
                UserGiftGard::where('gift_gard_id', $val)->update($data);
            }

            clear_all_files();
            /* 重新载入 */
        }

        /*------------------------------------------------------ */
        //-- 搜索用户
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'search_users') {
            $keywords = json_str_iconv(trim($_GET['keywords']));

            $res = Users::select('user_id', 'user_name');
            $res = $res->where('user_name', 'LIKE', '%' . mysql_like_quote($keywords) . '%')
                ->orWhere('user_id', 'LIKE', '%' . mysql_like_quote($keywords) . '%');
            $row = $this->baseRepository->getToArrayGet($res);

            return make_json_result($row);
        }

        /*------------------------------------------------------ */
        //--礼品卡列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'bonus_list') {
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['gift_gard_list']);
            $this->smarty->assign('action_link', ['href' => 'gift_gard.php?act=list', 'text' => $GLOBALS['_LANG']['gift_gard_type_list']]);

            $list = $this->giftGardManageService->getUserGiftGardList($adminru['ru_id']);

            $this->smarty->assign('bonus_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('action_link2', ['href' => 'gift_gard.php?act=gift_add&bonus_type=' . intval($_REQUEST['bonus_type']), 'text' => $GLOBALS['_LANG']['gift_gard_add']]);
            $this->smarty->assign('action_link3', ['href' => 'gift_gard.php?act=export_gift_gard&bonus_type=' . intval($_REQUEST['bonus_type']), 'text' => $GLOBALS['_LANG']['gift_gard_export']]);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('gift_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 礼品卡提货列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'take_list') {
            admin_priv('take_manage');
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['gift_gard_list']);
            $this->smarty->assign('action_link', ['href' => 'gift_gard.php?act=take_excel', 'text' => lang('admin/gift_gard.export_order')]);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $list = $this->giftGardManageService->getUserGiftGardList($adminru['ru_id']);

            /* 赋值是否显示红包序列号 */
            $_REQUEST['bonus_type'] = isset($_REQUEST['bonus_type']) && !empty($_REQUEST['bonus_type']) ? $_REQUEST['bonus_type'] : '';
            $bonus_type = $this->giftGardManageService->GiftGardTypeInfo(intval($_REQUEST['bonus_type']));

            if (!empty($bonus_type)) {
                if (isset($bonus_type['send_type']) && $bonus_type['send_type'] == SEND_BY_PRINT) {
                    $this->smarty->assign('show_bonus_sn', 1);
                } elseif (isset($bonus_type['send_type']) && $bonus_type['send_type'] == SEND_BY_USER) {
                    /* 赋值是否显示发邮件操作和是否发过邮件 */
                    $this->smarty->assign('show_mail', 1);
                }
            }

            $this->smarty->assign('bonus_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()), 'take_list');


            return $this->smarty->display('take_list.dwt');
        }

        if ($_REQUEST['act'] == 'query_take') {
            $list = $this->giftGardManageService->getUserGiftGardList($adminru['ru_id']);
            $this->smarty->assign('bonus_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('take_list.dwt'), '', ['filter' => $list['filter'], 'page_count' => $list['page_count']]);
        }


        /*------------------------------------------------------ */
        //-- 礼品卡列表翻页、排序
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'query_bonus') {
            $list = $this->giftGardManageService->getUserGiftGardList($adminru['ru_id']);

            $this->smarty->assign('bonus_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('gift_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 导出已完成订单excel下载
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'take_excel') {
            $file_name = lang('admin/gift_gard.order_complete') . local_date('YmdHis', gmtime());
            $gift_gard_list = $this->giftGardManageService->getUserGiftGardList($adminru['ru_id']);

            $list = $gift_gard_list['item'] ?? [];
            if ($list) {
                foreach ($list as $key => $value) {
                    $list[$key]['status_format'] = $GLOBALS['_LANG']['status_ok'];
                }

                // 设置 表头标题、列宽 默认10, true 是否自动换行  格式：列名|10|true
                $head = [
                    $GLOBALS['_LANG']['record_id'],
                    $GLOBALS['_LANG']['gift_card_sn'] . '|25',
                    $GLOBALS['_LANG']['address'] . '|25',
                    $GLOBALS['_LANG']['consignee_name'],
                    $GLOBALS['_LANG']['mobile'],
                    $GLOBALS['_LANG']['gift_user_name'] . '|20',
                    $GLOBALS['_LANG']['gift_goods_name'] . '|30',
                    $GLOBALS['_LANG']['gift_user_time'],
                    $GLOBALS['_LANG']['express_no'],
                    $GLOBALS['_LANG']['status']
                ];

                // 导出字段
                $fields = [
                    'gift_gard_id',
                    'gift_sn',
                    'address',
                    'consignee_name',
                    'mobile',
                    'user_name',
                    'goods_name',
                    'user_time',
                    'express_no',
                    'status_format'
                ];
                // 文件名
                $title = $file_name;

                $spreadsheet = new OfficeService();

                $spreadsheet->outdata($title, $head, $fields, $list);
                return;
            }
        }


        /*------------------------------------------------------ */
        //-- 导出礼品卡excel下载
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'export_gift_gard') {
            $file_name = lang('admin/gift_gard.gift_card') . local_date('YmdHis', gmtime());
            $gift_list = $this->giftGardManageService->getUserGiftGardList($adminru['ru_id']);

            $list = $gift_list['item'] ?? [];
            if ($list) {
                // 设置 表头标题、列宽 默认10, true 是否自动换行  格式：列名|10|true
                $head = [
                    $GLOBALS['_LANG']['record_id'],
                    $GLOBALS['_LANG']['card_number'] . '|25',
                    $GLOBALS['_LANG']['pwd'] . '|25',
                    $GLOBALS['_LANG']['money'],
                    $GLOBALS['_LANG']['card_name'] . '|25'
                ];

                // 导出字段
                $fields = [
                    'gift_gard_id',
                    'gift_sn',
                    'gift_password',
                    'gift_menory',
                    'gift_name',
                ];
                // 文件名
                $title = $file_name;

                $spreadsheet = new OfficeService();

                $spreadsheet->outdata($title, $head, $fields, $list);
                return;
            }
        }

        /*------------------------------------------------------ */
        //--礼品提货列表翻页、排序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query_take') {
            $list = $this->giftGardManageService->getUserGiftGardList($adminru['ru_id']);

            /* 赋值是否显示红包序列号 */
            $bonus_type = $this->giftGardManageService->GiftGardTypeInfo(intval($_REQUEST['bonus_type']));

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

            return make_json_result(
                $this->smarty->fetch('take_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 删除礼品卡
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_bonus') {
            $check_auth = check_authz_json('gift_gard_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            $num = UserGiftGard::where('gift_gard_id', $id)->where('user_id', '>', 0)->count();
            if ($num > 0) {
                $data = ['is_delete' => 0];
                UserGiftGard::where('gift_gard_id', $id)->update($data);
            } else {
                UserGiftGard::where('gift_gard_id', $id)->delete();
            }

            $url = 'gift_gard.php?act=query_bonus&' . str_replace('act=remove_bonus', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 删除提货
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_take') {
            $check_auth = check_authz_json('gift_gard_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            $data = [
                'goods_id' => 0,
                'user_id' => 0,
                'address' => '',
                'consignee_name' => '',
                'mobile' => '',
                'status' => 0,
                'express_no' => '',
            ];
            UserGiftGard::where('gift_gard_id', $id)->update($data);

            //删除操作记录  bu kong
            GiftGardLog::where('gift_gard_id', $id)->where('handle_type', 'gift_gard')->delete();
            $url = 'gift_gard.php?act=query_take&' . str_replace('act=remove_take', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            admin_priv('gift_gard_manage');

            /* 去掉参数：红包类型 */
            $bonus_type_id = intval($_REQUEST['bonus_type']);

            /* 取得选中的红包id */
            if (isset($_POST['checkboxes'])) {
                $bonus_id_list = $_POST['checkboxes'];

                /* 删除红包 */
                if (isset($_POST['drop'])) {
                    foreach ($bonus_id_list as $key => $val) {
                        $num = UserGiftGard::where('gift_gard_id', $val)->where('user_id', '>', 0)->count();
                        if ($num > 0) {
                            $data = ['is_delete' => 0];
                            UserGiftGard::where('gift_gard_id', $val)->update($data);
                        } else {
                            UserGiftGard::where('gift_gard_id', $val)->delete();
                        }
                    }

                    admin_log(count($bonus_id_list), 'remove', 'userbonus');

                    clear_cache_files();

                    $link[] = ['text' => $GLOBALS['_LANG']['back_gift_list'],
                        'href' => 'gift_gard.php?act=bonus_list&bonus_type=' . $bonus_type_id];
                    return sys_msg(sprintf($GLOBALS['_LANG']['batch_gift_success'], count($bonus_id_list)), 0, $link);
                } /* 配置商品 */
                elseif (isset($_POST['configure_goods'])) {
                    $this->smarty->assign('ur_here', $GLOBALS['_LANG']['gift_from_goods']);
                    $this->smarty->assign('action_link', ['href' => 'gift_gard.php?act=bonus_list&bonus_type=' . $bonus_type_id, 'text' => $GLOBALS['_LANG']['gift_gard_list']]);

                    $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

                    /* 查询此红包类型信息 */
                    $res = GiftGardType::where('gift_id', $id);
                    $bonus_type = $this->baseRepository->getToArrayFirst($res);

                    $bonus_type = $bonus_type ? $bonus_type : $_REQUEST['bonus_type'];

                    /* 查询礼品卡列表 */
                    if (count($bonus_id_list) == 1) {
                        $goods_list = $this->giftGardManageService->getGiftGardList($bonus_id_list[0]);
                        $this->smarty->assign('goods_list', $goods_list);
                    }

                    /* 查询其他红包类型的商品 */

                    $res = Goods::select('goods_id')->where('bonus_type_id', '>', 0)->where('bonus_type_id', '<>', $id);
                    $other_goods_list = $this->baseRepository->getToArrayGet($res);
                    $other_goods_list = $this->baseRepository->getFlatten($other_goods_list);

                    $this->smarty->assign('other_goods', ($other_goods_list === false) ? '' : join(',', $other_goods_list));

                    /* 模板赋值 */
                    $gift = implode(',', $_POST['checkboxes']);
                    $this->smarty->assign('gift_ids', $gift);

                    set_default_filter(); //设置默认筛选

                    $this->smarty->assign('bonus_type', $bonus_type);
                    $this->smarty->assign('gift_goods_id', implode(',', $bonus_id_list));

                    return $this->smarty->display('gift_gard_by_goods.dwt');
                }
            } else {
                return sys_msg($GLOBALS['_LANG']['no_select_bonus'], 1);
            }
        }
    }
}
