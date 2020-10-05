<?php

namespace App\Modules\Admin\Controllers;

use App\Models\BonusType;
use App\Models\Goods;
use App\Models\UserBonus;
use App\Models\UserRank;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Bonus\BonusManageService;
use App\Services\Store\StoreCommonService;

/**
 * 红包类型的处理
 */
class BonusController extends InitController
{
    protected $bonusManageService;
    protected $baseRepository;
    protected $timeRepository;
    protected $storeCommonService;
    protected $dscRepository;

    public function __construct(
        BonusManageService $bonusManageService,
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        StoreCommonService $storeCommonService,
        DscRepository $dscRepository
    )
    {
        $this->bonusManageService = $bonusManageService;
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->storeCommonService = $storeCommonService;
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

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $this->smarty->assign('url', $this->dsc->url());
        /*------------------------------------------------------ */
        //-- 红包类型列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_bonustype_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['bonustype_add'], 'href' => 'bonus.php?act=add']);
            $this->smarty->assign('full_page', 1);

            $list = $this->bonusManageService->getTypeList();

            $this->smarty->assign('type_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()));

            return $this->smarty->display('bonus_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页、排序
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'query') {
            $list = $this->bonusManageService->getTypeList();

            $this->smarty->assign('type_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('bonus_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 编辑红包类型名称
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit_type_name') {
            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = json_str_iconv(trim($_POST['val']));

            $is_only = BonusType::where('type_name', $val)
                ->where('type_id', '<>', $id)
                ->count();

            /* 检查红包类型名称是否重复 */
            if ($is_only > 0) {
                return make_json_error($GLOBALS['_LANG']['type_name_exist']);
            } else {
                BonusType::where('type_id', $id)->update([
                    'type_name' => $val
                ]);

                return make_json_result(stripslashes($val));
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑红包金额
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit_type_money') {
            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            /* 检查红包类型名称是否重复 */
            if ($val <= 0) {
                return make_json_error($GLOBALS['_LANG']['type_money_error']);
            } else {
                BonusType::where('type_id', $id)->update([
                    'type_money' => $val
                ]);

                return make_json_result(number_format($val, 2));
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑订单下限
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_min_amount') {
            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            if ($val < 0) {
                return make_json_error($GLOBALS['_LANG']['min_amount_empty']);
            } else {
                BonusType::where('type_id', $id)->update([
                    'min_amount' => $val
                ]);

                return make_json_result(number_format($val, 2));
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑订单下限
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit_min_goods_amount') {
            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            if ($val < 0) {
                return make_json_error($GLOBALS['_LANG']['min_amount_empty']);
            } else {
                BonusType::where('type_id', $id)->update([
                    'min_goods_amount' => $val
                ]);

                return make_json_result(number_format($val, 2));
            }
        }

        /*------------------------------------------------------ */
        //-- 删除红包类型
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);
            BonusType::where('type_id', $id)->delete();

            /* 更新商品信息 */
            Goods::where('bonus_type_id', $id)->update([
                'bonus_type_id' => 0
            ]);

            /* 删除用户的红包 */
            UserBonus::where('bonus_type_id', $id)->delete();

            $url = 'bonus.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['not_select_any_data'], 1);
            }

            $ids = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;
            $ids = $this->baseRepository->getExplode($ids);

            if (isset($_POST['type'])) {
                // 删除
                if ($_POST['type'] == 'batch_remove') {
                    if ($ids) {
                        $res = BonusType::whereIn('type_id', $ids)->delete();

                        Goods::whereIn('bonus_type_id', $ids)->update([
                            'bonus_type_id' => 0
                        ]);

                        UserBonus::whereIn('bonus_type_id', $ids)->delete();

                        if ($res) {
                            /* 记日志 */
                            admin_log('', 'batch_remove', 'bonus_manage');
                        }
                    }

                    /* 清除缓存 */
                    clear_cache_files();

                    $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'bonus.php?act=list&' . list_link_postfix()];
                    return sys_msg($GLOBALS['_LANG']['batch_drop_ok'], 0, $links);
                } // 审核
                elseif ($_POST['type'] == 'review_to') {

                    // review_status = 3审核通过 2审核未通过
                    $review_status = intval($_POST['review_status']);

                    BonusType::whereIn('type_id', $ids)->update([
                        'review_status' => $review_status
                    ]);

                    $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'bonus.php?act=list&seller_list=1&' . list_link_postfix()];
                    return sys_msg($GLOBALS['_LANG']['bonus_audited_type_set_ok'], 0, $lnk);
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 红包类型添加页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            admin_priv('bonus_manage');

            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['bonustype_add']);
            $this->smarty->assign('action_link', ['href' => 'bonus.php?act=list', 'text' => $GLOBALS['_LANG']['04_bonustype_list']]);
            $this->smarty->assign('action', 'add');

            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);

            $next_month = $this->timeRepository->getLocalStrtoTime('+1 months');
            $bonus_arr['send_start_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s');
            $bonus_arr['use_start_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s');
            $bonus_arr['send_end_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $next_month);
            $bonus_arr['use_end_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $next_month);

            $bonus_arr['bonus_send_time'] = 8;
            $this->smarty->assign('bonus_arr', $bonus_arr);

            return $this->smarty->display('bonus_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 红包类型添加的处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'insert') {

            /* 去掉红包类型名称前后的空格 */
            $type_name = !empty($_POST['type_name']) ? trim($_POST['type_name']) : '';

            /* 初始化变量 */
            $min_amount = !empty($_POST['min_amount']) ? intval($_POST['min_amount']) : 0;
            $max_amount = !empty($_POST['max_amount']) ? intval($_POST['max_amount']) : 0;
            $valid_period = !empty($_POST['valid_period']) ? intval($_POST['valid_period']) : 0; //有效期限

            $usebonus_type = isset($_POST['usebonus_type']) ? intval($_POST['usebonus_type']) : 0; //ecmoban模板堂 --zhuo

            /* 检查类型是否有重复 */
            $is_only = BonusType::where('type_name', $type_name)->count();

            if ($is_only > 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['type_name_exist'], 0, $link);
            }

            /* 检查红包金额是否大于最小订单金额 */
            if ($_POST['type_money'] > $_POST['min_goods_amount']) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['type_money_beyond'], 0, $link);
            }

            /*
           *红包使用时间机制
         */
            if ($_POST['bonus_send_time'] == 7 && $valid_period == 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['bonus_send_time_beyond'], 0, $link);
            }

            if ($_POST['bonus_send_time'] == 8) {
                $data_type = 0;//使用时间
            } else {
                $data_type = 1;//有效期限
            }

            /* 获得日期信息 */
            $send_startdate = $this->timeRepository->getLocalStrtoTime($_POST['send_start_date']);
            $send_enddate = $this->timeRepository->getLocalStrtoTime($_POST['send_end_date']);
            $use_startdate = $this->timeRepository->getLocalStrtoTime($_POST['use_start_date']);
            $use_enddate = $this->timeRepository->getLocalStrtoTime($_POST['use_end_date']);

            /* 插入数据库。 */
            $other = [
                'type_name' => $type_name,
                'date_type' => $data_type,
                'valid_period' => $valid_period,
                'type_money' => $_POST['type_money'],
                'send_start_date' => $send_startdate,
                'send_end_date' => $send_enddate,
                'use_start_date' => $use_startdate,
                'use_end_date' => $use_enddate,
                'send_type' => $_POST['send_type'],
                'usebonus_type' => $usebonus_type,
                'user_id' => $adminru['ru_id'],
                'min_amount' => $min_amount,
                'max_amount' => $max_amount,
                'min_goods_amount' => floatval($_POST['min_goods_amount']),
                'review_status' => 3
            ];
            BonusType::insert($other);

            /* 记录管理员操作 */
            admin_log($_POST['type_name'], 'add', 'bonustype');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[0]['text'] = $GLOBALS['_LANG']['continus_add'];
            $link[0]['href'] = 'bonus.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'bonus.php?act=list';

            return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['type_name'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 红包类型编辑页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            admin_priv('bonus_manage');

            /* 获取红包类型数据 */
            $type_id = !empty($_GET['type_id']) ? intval($_GET['type_id']) : 0;

            $bonus_arr = BonusType::where('type_id', $type_id);
            $bonus_arr = $this->baseRepository->getToArrayFirst($bonus_arr);

            if ($bonus_arr) {
                $bonus_arr['send_start_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $bonus_arr['send_start_date']);
                $bonus_arr['send_end_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $bonus_arr['send_end_date']);
                $bonus_arr['use_start_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $bonus_arr['use_start_date']);
                $bonus_arr['use_end_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $bonus_arr['use_end_date']);
            }

            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['bonustype_edit']);
            $this->smarty->assign('action_link', ['href' => 'bonus.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['04_bonustype_list']]);
            $this->smarty->assign('form_act', 'update');
            if ($bonus_arr['date_type'] == 1) {
                $bonus_arr['bonus_send_time'] = 7;
            }
            if ($bonus_arr['date_type'] == 0) {
                $bonus_arr['bonus_send_time'] = 8;
            }

            $this->smarty->assign('bonus_arr', $bonus_arr);


            return $this->smarty->display('bonus_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 红包类型编辑的处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'update') {
            /* 对数据的处理 */
            $type_name = !empty($_POST['type_name']) ? trim($_POST['type_name']) : '';
            $type_id = !empty($_POST['type_id']) ? intval($_POST['type_id']) : 0;
            $review_status = isset($_POST['review_status']) && !empty($_POST['review_status']) ? intval($_POST['review_status']) : 3;

            /* 更新数据 */
            $record = [
                'type_name' => $type_name,
                'review_status' => $review_status
            ];

            BonusType::where('type_id', $type_id)->update($record);

            /* 记录管理员操作 */
            admin_log($_POST['type_name'], 'edit', 'bonustype');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'bonus.php?act=list&' . list_link_postfix()];
            return sys_msg($GLOBALS['_LANG']['edit'] . ' ' . $_POST['type_name'] . ' ' . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 红包发送页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'send') {

            admin_priv('bonus_manage');

            $user_list = $this->user_list();

            $this->smarty->assign('user_list', $user_list['user_list']);
            $this->smarty->assign('filter', $user_list['filter']);
            $this->smarty->assign('record_count', $user_list['record_count']);
            $this->smarty->assign('page_count', $user_list['page_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sort_user_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');

            /* 取得参数 */
            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['send_bonus']);
            $this->smarty->assign('action_link', ['href' => 'bonus.php?act=list', 'text' => $GLOBALS['_LANG']['04_bonustype_list']]);
            $this->smarty->assign('id', $id);


            if ($_REQUEST['send_by'] == SEND_BY_USER) {
                $this->smarty->assign('ranklist', get_rank_list());

                return $this->smarty->display('bonus_by_user.dwt');
            } elseif ($_REQUEST['send_by'] == SEND_BY_GOODS) {
                set_default_filter(); //设置默认筛选

                /* 查询此红包类型信息 */
                $bonus_type = BonusType::where('type_id', $id);
                $this->baseRepository->getToArrayFirst($bonus_type);

                /* 查询红包类型的商品列表 */
                $goods_list = $this->bonusManageService->getBonusGoods($id);

                /* 模板赋值 */
                $this->smarty->assign('bonus_type', $bonus_type);
                $this->smarty->assign('goods_list', $goods_list);

                return $this->smarty->display('bonus_by_goods.dwt');
            } elseif ($_REQUEST['send_by'] == SEND_BY_PRINT) {
                $this->smarty->assign('type_list', get_bonus_type());

                return $this->smarty->display('bonus_by_print.dwt');
            } elseif ($_REQUEST['send_by'] == SEND_BY_GET) {
                $res = BonusType::where('send_type', SEND_BY_GET);
                $res = $this->baseRepository->getToArrayGet($res);

                $bonus = [];
                if ($res) {
                    foreach ($res as $row) {
                        $bonus[$row['type_id']] = $row['type_name'] . ' [' . sprintf($GLOBALS['_CFG']['currency_format'], $row['type_money']) . ']';
                    }
                }

                $this->smarty->assign('type_list', $bonus);

                return $this->smarty->display('bonus_by_print.dwt');
            }
        }
        /*------------------------------------------------------ */
        //-- 处理红包的发送页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'send_by_user') {

            $user_list = [];
            $start = empty($_REQUEST['start']) ? 0 : intval($_REQUEST['start']);
            $limit = empty($_REQUEST['limit']) ? 1000 : intval($_REQUEST['limit']);//默认一次最多发1000
            $validated_email = empty($_REQUEST['validated_email']) ? 0 : intval($_REQUEST['validated_email']);
            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $send_count = 0;
            $count = 0;

            if (isset($_REQUEST['send_rank'])) {
                /* 按会员等级来发放红包 */
                $rank_id = intval($_REQUEST['rank_id']);

                if ($rank_id > 0) {
                    $row = UserRank::where('rank_id', $rank_id);
                    $row = $this->baseRepository->getToArrayFirst($row);

                    $user_list = [];
                    if ($row) {
//                        if ($row['special_rank']) {

                        /* 特殊会员组处理 */
                        $send_count = Users::where('user_rank', $rank_id)->count();

                        $user_list = Users::where('user_rank', $rank_id);
//                        } else {
//                            $send_count = Users::where('rank_points', '>=', $row['min_points'])
//                                ->where('rank_points', '<', $row['max_points'])
//                                ->count();
//
//                            $user_list = Users::where('rank_points', '>=', $row['min_points'])
//                                ->where('rank_points', '<', $row['max_points']);
//                        }

                        if ($validated_email) {
                            $user_list = $user_list->where('is_validated', 1);
                        }

                        if ($start > 0) {
                            $user_list = $user_list->skip($start);
                        }

                        if ($limit > 0) {
                            $user_list = $user_list->take($limit);
                        }

                        $user_list = $this->baseRepository->getToArrayGet($user_list);
                    }

                    $count = $user_list ? count($user_list) : 0;
                }
            } elseif (isset($_REQUEST['send_user'])) {

                /* 按会员列表发放红包 */
                /* 如果是空数组，直接返回 */
                if (empty($_REQUEST['checkboxes'])) {
                    return sys_msg($GLOBALS['_LANG']['send_user_empty'], 1);
                }

                $user_array = (is_array($_REQUEST['checkboxes'])) ? $_REQUEST['checkboxes'] : explode(',', $_REQUEST['checkboxes']);
                $send_count = count($user_array);

                $id_array = array_slice($user_array, $start, $limit);

                /* 根据会员ID取得用户名和邮件地址 */
                $user_list = [];
                if ($id_array) {
                    $user_list = Users::whereIn('user_id', $id_array);
                    $user_list = $this->baseRepository->getToArrayGet($user_list);
                }

                $count = $user_list ? count($user_list) : 0;
            }

            /* 发送红包 */
            $loop = 0;
            $bonus_type = $this->bonusManageService->bonusTypeInfo($id);

            $tpl = get_mail_template('send_bonus');
            $today = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], gmtime());

            if ($user_list) {
                $send_user = [];
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

                    if ($this->bonusManageService->addToMaillist($val['user_name'], $val['email'], $tpl['template_subject'], $content, $tpl['is_html'])) {
                        /* 向会员红包表录入数据 */
                        $other['emailed'] = BONUS_MAIL_SUCCEED;
                    } else {
                        /* 邮件发送失败，更新数据库 */
                        $other['emailed'] = BONUS_MAIL_FAIL;
                    }

                    $send_user[] = $other;

                    if ($loop >= $limit) {
                        break;
                    } else {
                        $loop++;
                    }
                }
                UserBonus::insert($send_user);
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
            $count = $this->bonusManageService->sendBonusMail($bonus['bonus_type_id'], [$bonus_id]);

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
            $num = UserBonus::max('bonus_sn');
            $num = $num ? $num : 0;
            $num = $num ? floor($num / 10000) : 100000;

            for ($i = 0, $j = 0; $i < $bonus_sum; $i++) {
                $bonus_sn = ($num + $i) . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $bonus_password = strtoupper(mc_random(10));

                $other = [
                    'bonus_type_id' => $bonus_typeid,
                    'bonus_sn' => $bonus_sn,
                    'bonus_password' => $bonus_password
                ];
                UserBonus::insert($other);

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
            $type_name = $type_name ? $type_name : '';

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

            $res = UserBonus::where('bonus_type_id', $tid);
            $res = $res->with([
                'getBonusType'
            ]);

            $res = $res->orderBy('bonus_id', 'desc');
            $res = $this->baseRepository->getToArrayGet($res);

            $code_table = [];
            if ($res) {
                foreach ($res as $val) {
                    $bonus_type = $val['get_bonus_type'];
                    $val['type_name'] = $bonus_type['type_name'] ?? '';
                    $val['type_money'] = $bonus_type['type_money'] ?? 0;
                    $val['use_end_date'] = $bonus_type['use_end_date'] ?? 0;

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
                    echo $this->timeRepository->getLocalDate('Y-m-d H:i:s', $val['use_end_date']);
                    echo "\t\n";
                }
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
        //-- 添加发放红包的商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add_bonus_goods') {
            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $add_ids = isset($_GET['add_ids']) && !empty($_GET['add_ids']) ? explode(',', trim($_GET['add_ids'])) : [];
            $type_id = intval($_GET['bid']);

            if ($add_ids) {
                Goods::whereIn('goods_id', $add_ids)->update([
                    'bonus_type_id' => $type_id
                ]);
            }

            /* 重新载入 */
            $arr = $this->bonusManageService->getBonusGoods($type_id);
            $opt = [];
            if ($arr) {
                foreach ($arr as $key => $val) {
                    $opt[] = ['value' => $val['goods_id'],
                        'text' => $val['goods_name'],
                        'data' => ''];
                }
            }

            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 删除发放红包        的商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'drop_bonus_goods') {
            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $drop_goods = isset($_GET['drop_ids']) && !empty($_GET['drop_ids']) ? explode(',', trim($_GET['drop_ids'])) : [];
            $type_id = intval($_GET['bid']);

            if ($drop_goods) {
                Goods::where('bonus_type_id', $type_id)
                    ->whereIn('goods_id', $drop_goods)
                    ->update([
                        'bonus_type_id' => 0
                    ]);
            }

            /* 重新载入 */
            $arr = $this->bonusManageService->getBonusGoods($type_id);
            $opt = [];

            if ($arr) {
                foreach ($arr as $key => $val) {
                    $opt[] = ['value' => $val['goods_id'],
                        'text' => $val['goods_name'],
                        'data' => ''];
                }
            }

            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 搜索用户
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'search_users') {

            $row = $this->user_list();

            foreach ($row['user_list'] as $key => $val) {
                $row['user_list'][$key]['last_login_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $val['last_login']);
            }

            $this->smarty->assign('user_list', $row['user_list']);
            $this->smarty->assign('filter', $row['filter']);
            $this->smarty->assign('record_count', $row['record_count']);
            $this->smarty->assign('page_count', $row['page_count']);
            $this->smarty->assign('id', intval($_REQUEST['id']));
            $sort_flag = sort_flag($row['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('bonus_by_user.dwt'),
                '',
                ['filter' => $row['filter'], 'page_count' => $row['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 红包        列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'bonus_list') {
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['bonus_list']);
            $this->smarty->assign('action_link', ['href' => 'bonus.php?act=list', 'text' => $GLOBALS['_LANG']['04_bonustype_list']]);

            $list = $this->bonusManageService->getBonusList();

            /* 赋值是否显示红包序列号 */
            $bonus_type = $this->bonusManageService->bonusTypeInfo(intval($_REQUEST['bonus_type']));
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


            return $this->smarty->display('bonus_view.dwt');
        }

        /*------------------------------------------------------ */
        //-- 红包列表翻页、排序
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'query_bonus') {
            $list = $this->bonusManageService->getBonusList();

            /* 赋值是否显示红包序列号 */
            $bonus_type = $this->bonusManageService->bonusTypeInfo(intval($_REQUEST['bonus_type']));
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
                $this->smarty->fetch('bonus_view.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 删除红包
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_bonus') {
            $check_auth = check_authz_json('bonus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);
            UserBonus::where('bonus_id', $id)->delete();

            $url = 'bonus.php?act=query_bonus&' . str_replace('act=remove_bonus', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            admin_priv('bonus_manage');

            /* 去掉参数：红包类型 */
            $bonus_type_id = !empty($_REQUEST['bonus_type']) ? intval($_REQUEST['bonus_type']) : 0;

            //过滤未选择操作方式
            if ($bonus_type_id == 0) {
                return sys_msg($GLOBALS['_LANG']['not_select_any_data'], 1);
            }

            /* 取得选中的红包id */
            if (isset($_POST['checkboxes'])) {
                $bonus_id_list = $_POST['checkboxes'];

                /* 删除红包 */
                if (isset($_POST['drop'])) {
                    $bonus_id_list = $this->baseRepository->getExplode($bonus_id_list);
                    if ($bonus_id_list) {
                        UserBonus::whereIn('bonus_id', $bonus_id_list)->delete();
                    }

                    admin_log(count($bonus_id_list), 'remove', 'userbonus');

                    clear_cache_files();

                    $link[] = ['text' => $GLOBALS['_LANG']['back_bonus_list'],
                        'href' => 'bonus.php?act=bonus_list&bonus_type=' . $bonus_type_id];
                    return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], count($bonus_id_list)), 0, $link);
                } /* 发邮件 */
                elseif (isset($_POST['mail'])) {
                    $count = $this->bonusManageService->sendBonusMail($bonus_type_id, $bonus_id_list);
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
     * 返回用户列表数据
     *
     * @return array
     */
    private function user_list()
    {
        /* 过滤条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);

        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'user_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $keywords = $filter['keywords'];
        if (!empty($keywords)) {
            $row = Users::where('user_name', 'like', '%' . $keywords . '%')
                ->orWhere('user_id', 'like', '%' . $keywords . '%')
                ->orWhere('mobile_phone', 'like', '%' . $keywords . '%');

            $filter['record_count'] = $row->count();

            /* 分页大小 */
            $filter = page_and_size($filter);

            if ($filter['start'] > 0) {
                $row = $row->skip($filter['start']);
            }

            if ($filter['page_size'] > 0) {
                $row = $row->take($filter['page_size']);
            }

        } else {
            $row = [];
            $filter['record_count'] = 0;
            $filter['page_count'] = 0;
        }

        $row = $this->baseRepository->getToArrayGet($row);

        if ($row) {
            foreach ($row as $key => $val) {
                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $row[$key]['mobile_phone'] = $this->dscRepository->stringToStar($val['mobile_phone']);
                    $row[$key]['user_name'] = $this->dscRepository->stringToStar($val['user_name']);
                    $row[$key]['email'] = $this->dscRepository->stringToStar($val['email']);
                }
            }
        }

        return ['user_list' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

}
