<?php

namespace App\Modules\Admin\Controllers;

use App\Models\PayCard;
use App\Models\PayCardType;
use App\Repositories\Common\BaseRepository;
use App\Services\PayCard\PayCardManageService;

/**
 * 充值卡的处理
 */
class PayCardController extends InitController
{

    protected $baseRepository;
    protected $payCardManageService;

    public function __construct(
        BaseRepository $baseRepository,
        PayCardManageService $payCardManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->payCardManageService = $payCardManageService;
    }

    public function index()
    {

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }
        /*------------------------------------------------------ */
        //-- �        �        值卡列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['pc_type_list']);


            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['pc_type_add'], 'href' => 'pay_card.php?act=add']);
            $this->smarty->assign('full_page', 1);
            $list = $this->payCardManageService->getTypeList();

            $this->smarty->assign('type_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);

            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('pc_type_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑�        �        值卡类型页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            if ($_REQUEST['act'] == 'add') {
                $this->smarty->assign('form_act', 'insert');
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['pc_type_add']);
                $next_month = local_strtotime('+1 months');
                $bonus_arr['use_end_date'] = local_date('Y-m-d', $next_month);
                $this->smarty->assign('bonus_arr', $bonus_arr);
            } else {
                /* 获取充值卡类型数据 */
                $type_id = !empty($_GET['type_id']) ? intval($_GET['type_id']) : 0;

                $res = PayCardType::where('type_id', $type_id);
                $bonus_arr = $this->baseRepository->getToArrayFirst($res);

                $bonus_arr['use_end_date'] = local_date('Y-m-d', $bonus_arr['use_end_date']);

                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['pc_type_edit']);
                $this->smarty->assign('form_act', 'update');
                $this->smarty->assign('bonus_arr', $bonus_arr);
            }

            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('action_link', ['href' => 'value_card.php?act=list', 'text' => $GLOBALS['_LANG']['vc_type_list']]);
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);

            return $this->smarty->display('pc_type_info.dwt');
        }


        /*------------------------------------------------------ */
        //-- 添加/编辑�        �        值卡类型处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            $type_name = !empty($_POST['type_name']) ? trim($_POST['type_name']) : '';
            $type_money = !empty($_POST['type_money']) ? $_POST['type_money'] : 0;
            $type_id = !empty($_POST['type_id']) ? intval($_POST['type_id']) : 0;
            $type_prefix = !empty($_POST['type_prefix']) ? trim($_POST['type_prefix']) : 0;
            $use_enddate = local_strtotime($_POST['use_end_date']);

            /* 检查类型是否有重复 */
            $res = PayCardType::where('type_name', $type_name)
                ->where('type_id', '<>', $type_id)
                ->count();

            if ($res > 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['type_name_exist'], 0, $link);
            }
            if ($type_id > 0) {
                $data = [
                    'type_name' => $type_name,
                    'type_money' => $type_money,
                    'type_prefix' => $type_prefix,
                    'use_end_date' => $use_enddate
                ];
                PayCardType::where('type_id', $type_id)->update($data);
                /* 提示信息 */
                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'pay_card.php?act=list&' . list_link_postfix()];
                return sys_msg($GLOBALS['_LANG']['edit'] . ' ' . $_POST['type_name'] . ' ' . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
            } else {
                /* 插入数据库。 */
                $data = [
                    'type_name' => $type_name,
                    'type_money' => $type_money,
                    'type_prefix' => $type_prefix,
                    'use_end_date' => $use_enddate
                ];
                PayCardType::insert($data);

                /* 提示信息 */
                $link[0]['text'] = $GLOBALS['_LANG']['continus_add'];
                $link[0]['href'] = 'pay_card.php?act=add';

                $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[1]['href'] = 'pay_card.php?act=list';
                return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['type_name'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
            }
            /* 清除缓存 */
            clear_cache_files();
        }

        /*------------------------------------------------------ */
        //-- 删除�        �        值卡类型
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            $id = intval($_GET['id']);

            /* 删除充值卡类型 */
            PayCardType::where('type_id', $id)->delete();

            $url = 'pay_card.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 翻页、排序
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'query') {
            $list = $this->payCardManageService->getTypeList();

            $this->smarty->assign('type_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('pc_type_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- �        �        值卡发送页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'send') {
            /* 取得参数 */
            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : '';

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['send_bonus']);
            $this->smarty->assign('action_link', ['href' => 'shoppingcard.php?act=list', 'text' => $GLOBALS['_LANG']['bonus_type']]);
            $this->smarty->assign('type_id', $id);
            $this->smarty->assign('type_list', get_pay_card_type($id));


            return $this->smarty->display('pay_card_send.dwt');
        }

        /*------------------------------------------------------ */
        //-- 按印刷品发放�        �        值卡
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'send_pay_card') {
            @set_time_limit(0);

            /* 储值卡类型和生成的数量的处理 */
            $tid = $_POST['type_id'] ? intval($_POST['type_id']) : 0;
            $send_sum = !empty($_POST['send_num']) ? intval($_POST['send_num']) : 1;
            $card_type = intval($_POST['card_type']);
            $password_type = intval($_POST['password_type']);

            $type_prefix = PayCardType::where('type_id', $tid)->value('type_prefix');
            $type_prefix = $type_prefix ? $type_prefix : '';

            $prefix_len = strlen($type_prefix);
            $length = $prefix_len + $card_type;

            /* 生成充值卡序列号 */
            $num = PayCard::selectRaw("MAX(SUBSTRING(card_number," . intval($prefix_len + 1) . ")) as card_number")
                ->whereRaw("c_id = '$tid' AND LENGTH(card_number) = '$length'")
                ->value('card_number');

            $num = $num ? intval($num) : 1;

            for ($i = 0, $j = 0; $i < $send_sum; $i++) {
                $card_number = $type_prefix . str_pad(mt_rand(0, 9999) + $num, $card_type, '0', STR_PAD_LEFT);
                $card_psd = strtoupper(mc_random($password_type));

                $data = [
                    'card_number' => $card_number,
                    'card_psd' => $card_psd,
                    'c_id' => $tid
                ];
                PayCard::insert($data);

                $j++;
            }

            /* 记录管理员操作 */
            admin_log($card_number, 'add', 'pay_card');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'pay_card.php?act=list';
            return sys_msg($GLOBALS['_LANG']['creat_value_card'] . $j . $GLOBALS['_LANG']['pay_card_num'], 0, $link);
        }


        /*------------------------------------------------------ */
        //-- �        �        值卡列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'pc_list') {
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['bonus_list']);
            $id = isset($_REQUEST['tid']) && !empty($_REQUEST['tid']) ? intval($_REQUEST['tid']) : 0;
            $this->smarty->assign('action_link', ['href' => 'pay_card.php?act=export_pc_list&id=' . $id, 'text' => $GLOBALS['_LANG']['export_pc_list']]);

            $list = $this->payCardManageService->getBonusList();

            /* 赋值是否显示充值卡序列号 */
            $bonus_type = $this->payCardManageService->bonusTypeInfo(intval($id));

            $this->smarty->assign('show_bonus_sn', 1);

            $this->smarty->assign('bonus_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('bonus_type', $bonus_type);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('pay_card_view.dwt');
        }
        /* ------------------------------------------------------ */
        //-- 导出�        �        值卡
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'export_pc_list') {
            $id = $_REQUEST['id'] ? intval($_REQUEST['id']) : 0;

            $arr = [];

            $res = PayCard::whereRaw(1);
            if ($id > 0) {
                $res = $res->where('c_id', $id);
            }
            $res = $res->with(['getPayCardType', 'getUsers']);
            $row = $this->baseRepository->getToArrayGet($res);

            foreach ($row as $key => $val) {
                $val['type_name'] = $val['get_pay_card_type']['type_name'] ?? '';
                $val['type_money'] = $val['get_pay_card_type']['type_money'] ?? '';
                $val['use_end_date'] = $val['get_pay_card_type']['use_end_date'] ?? '';

                $val['user_name'] = $val['get_users']['user_name'] ?? '';
                $val['email'] = $val['get_users']['email'] ?? '';

                $arr[$key]['id'] = $val['id'];
                $arr[$key]['card_number'] = $val['card_number'];
                $arr[$key]['card_psd'] = $val['card_psd'];
                $arr[$key]['type_name'] = $val['type_name'];
                $arr[$key]['type_money'] = $val['type_money'];
                $arr[$key]['use_end_date'] = $val['use_end_date'] == 0 ?
                    $GLOBALS['_LANG']['no_use'] : local_date($GLOBALS['_CFG']['date_format'], $val['use_end_date']);
                $arr[$key]['user_name'] = !empty($val['user_name']) ? $val['user_name'] : $GLOBALS['_LANG']['no_use'];
                $arr[$key]['used_time'] = $val['used_time'] == 0 ?
                    $GLOBALS['_LANG']['no_use'] : local_date($GLOBALS['_CFG']['date_format'], $val['used_time']);
            }

            $prev = [$GLOBALS['_LANG']['record_id'], $GLOBALS['_LANG']['bonus_sn'], $GLOBALS['_LANG']['bonus_psd'], $GLOBALS['_LANG']['bonus_type'], $GLOBALS['_LANG']['type_money'], $GLOBALS['_LANG']['use_enddate'], $GLOBALS['_LANG']['user_id'], $GLOBALS['_LANG']['used_time']];
            export_csv_pro($arr, 'export_vc_list', $prev);
        }
        /*------------------------------------------------------ */
        //-- �        �        值卡列表翻页、排序
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'pc_query') {
            $list = $this->payCardManageService->getBonusList();

            /* 赋值是否显示充值卡序列号 */
            $_REQUEST['bonus_type'] = isset($_REQUEST['bonus_type']) && !empty($_REQUEST['bonus_type']) ? $_REQUEST['bonus_type'] : '';
            $bonus_type = $this->payCardManageService->bonusTypeInfo(intval($_REQUEST['bonus_type']));

            $this->smarty->assign('show_bonus_sn', 1);

            $this->smarty->assign('bonus_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('pay_card_view.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 删除�        �        值卡
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove_pc') {
            $id = intval($_GET['id']);

            PayCard::where('id', $id)->delete();

            $url = 'pay_card.php?act=pc_query&' . str_replace('act=remove_pc', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }
}
