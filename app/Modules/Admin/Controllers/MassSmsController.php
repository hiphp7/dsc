<?php

namespace App\Modules\Admin\Controllers;

use App\Models\MassSmsLog;
use App\Models\MassSmsTemplate;
use App\Models\UserRank;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Sms\MassSmsManageService;

/**
 * 短信群发管理程序
 */
class MassSmsController extends InitController
{
    protected $commonRepository;
    protected $merchantCommonService;
    protected $baseRepository;
    protected $massSmsManageService;

    public function __construct(
        CommonRepository $commonRepository,
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        MassSmsManageService $massSmsManageService
    )
    {
        $this->commonRepository = $commonRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->massSmsManageService = $massSmsManageService;;
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
        $ruCat = '';
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        /*------------------------------------------------------ */
        //-- 列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('mass_sms');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['17_mass_sms']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['template_add'], 'href' => 'mass_sms.php?act=add']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sms_type', $GLOBALS['_CFG']['sms_type']);

            $list = $this->massSmsManageService->massSmsList();

            $this->smarty->assign('mass_sms', $list['list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('mass_sms_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $check_auth = check_authz_json('mass_sms');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $this->smarty->assign('sms_type', $GLOBALS['_CFG']['sms_type']);

            $list = $this->massSmsManageService->massSmsList();

            $this->smarty->assign('mass_sms', $list['list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('mass_sms_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 新增、编辑
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            admin_priv('mass_sms');

            if ($_REQUEST['act'] == 'add') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['template_add']);
                $this->smarty->assign('form_act', 'insert');
                $this->smarty->assign('action', 'add');
            } else {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['template_edit']);
                $this->smarty->assign('form_act', 'update');
                $this->smarty->assign('action', 'add');
                //获取信息
                $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
                $note = get_table_date("mass_sms_template", "id='$id'", ['*']);
                $this->smarty->assign('note', $note);
            }

            $this->smarty->assign('action_link', ['href' => 'mass_sms.php?act=list']);
            $this->smarty->assign('sms_type', $GLOBALS['_CFG']['sms_type']);
            $this->smarty->assign('ranklist', get_rank_list());


            return $this->smarty->display('mass_sms_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加、更新
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            admin_priv('mass_sms');

            $id = empty($_POST['id']) ? 0 : intval($_POST['id']);
            $signature = empty($_POST['signature']) ? 0 : intval($_POST['signature']);

            $other['temp_id'] = empty($_POST['temp_id']) ? '' : trim($_POST['temp_id']);
            $other['temp_content'] = empty($_POST['temp_content']) ? '' : trim($_POST['temp_content']);
            $other['content'] = empty($_POST['content']) ? '' : trim($_POST['content']);
            $other['set_sign'] = empty($_POST['set_sign']) ? '' : trim($_POST['set_sign']);
            $other['add_time'] = gmtime();

            if ($id) {
                MassSmsTemplate::where('id', $id)->update($other);
                $href = 'mass_sms.php?act=edit&id=' . $id;
                $lang_name = $GLOBALS['_LANG']['edit_success'];
            } else {
                MassSmsTemplate::insert($other);
                $href = 'mass_sms.php?act=list';
                $lang_name = $GLOBALS['_LANG']['add_success'];
                $id = $this->db->insert_id();
            }

            //短信记录
            $user_list = [];
            $type = empty($_POST['type']) ? 0 : intval($_POST['type']);
            if ($type == 0) {
                $user_list = $_POST['user'];
            } elseif ($type == 1) {
                $rank_id = empty($_POST['rank_id']) ? 0 : intval($_POST['rank_id']);
                if ($rank_id > 0) {
                    $res = UserRank::where('rank_id', $rank_id);
                    $row = $this->baseRepository->getToArrayFirst($res);

                    $res = Users::select('user_id')->whereRaw(1);
//                    if ($row['special_rank']) {
                    /* 特殊会员组处理 */
                    $res = $res->where('user_rank', $rank_id);
//                    } else {
//                        $res = $res->where('rank_points', '>=', intval($row['min_points']))
//                            ->where('rank_points', '<', intval($row['max_points']));
//                    }

                    $user_list = $this->baseRepository->getToArrayGet($res);
                    $user_list = $this->baseRepository->getFlatten($user_list);
                }
            } elseif ($type == 2) {
                $res = Users::select('user_id');
                $user_list = $this->baseRepository->getToArrayGet($res);
                $user_list = $this->baseRepository->getFlatten($user_list);
            }

            if ($user_list) {
                foreach ($user_list as $key => $val) {
                    $data = [];
                    $data['template_id'] = $id;
                    $data['user_id'] = $val;
                    $data['send_status'] = 0;
                    $data['last_send'] = 0;
                    MassSmsLog::insert($data);
                }
            }

            $save_count = count($user_list);
            $lang_name .= sprintf($GLOBALS['_LANG']['save_count'], $save_count);

            //处理记录
            if (isset($_POST['send'])) {
            }

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => $href];
            return sys_msg(sprintf($lang_name, htmlspecialchars(stripslashes($other['temp_id']))), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            admin_priv('mass_sms');

            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $temp_id = MassSmsTemplate::where('id', $id)->value('temp_id');
            $temp_id = $temp_id ? $temp_id : 0;

            MassSmsTemplate::where('id', $id)->delete();

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'mass_sms.php?act=list'];
            return sys_msg(sprintf($GLOBALS['_LANG']['remove_success'], $temp_id), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 搜索用户
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'search_users') {
            $check_auth = check_authz_json('mass_sms');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $keywords = json_str_iconv(trim($_GET['keywords']));

            $res = Users::where('user_name', 'LIKE', '%' . mysql_like_quote($keywords) . '%')
                ->orWhere('user_id', 'LIKE', '%' . mysql_like_quote($keywords) . '%');
            $row = $this->baseRepository->getToArrayGet($res);

            return make_json_result($row);
        }

        /*------------------------------------------------------ */
        //-- 日志列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'log_list') {
            admin_priv('mass_sms');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['log_list']);
            $this->smarty->assign('full_page', 1);

            $list = $this->massSmsManageService->massSmsLog();

            $this->smarty->assign('log', $list['list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('mass_sms_log.dwt');
        }

        /*------------------------------------------------------ */
        //-- 日志查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'log_query') {
            $check_auth = check_authz_json('mass_sms');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $list = $this->massSmsManageService->massSmsLog();

            $this->smarty->assign('log', $list['list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('mass_sms_log.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 删除日志
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_log') {
            $check_auth = check_authz_json('mass_sms');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            MassSmsLog::where('id', $id)->delete();

            $url = 'mass_sms.php?act=log_query&' . str_replace('act=remove_log', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 发送短信
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'send') {
            admin_priv('mass_sms');

            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $log_info = get_table_date("mass_sms_log", "id='$id'", ['*']);
            $user_info = get_table_date("users", "user_id='{$log_info['user_id']}'", ['user_name', 'mobile_phone']);
            $template_info = get_table_date("mass_sms_template", "id='{$log_info['template_id']}'", ['*']);
            $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'mass_sms.php?act=log_list&template_id=' . $log_info['template_id']];

            /* 如果需要，发短信 */
            $send_status = false;
            if ($user_info['mobile_phone'] != '') {
                $shop_name = $this->merchantCommonService->getShopName($adminru['ru_id'], 1);
                $smsParams = [
                    'shop_name' => $shop_name,
                    'shopname' => $shop_name,
                    'user_name' => $user_info['user_name'],
                    'username' => $user_info['user_name'],
                    'content' => $template_info['content'],
                    'mobile_phone' => $user_info['mobile_phone'],
                    'mobilephone' => $user_info['mobile_phone'],
                    'temp_id' => $template_info['temp_id'],
                    'set_sign' => $template_info['set_sign']
                ];

                $smsParams['temp_content'] = $template_info['temp_content'];
                $send_status = $this->commonRepository->smsSend($user_info['mobile_phone'], $smsParams, '', false);
            }

            if ($send_status) {
                $res_no = 0;
                $res_msg = $GLOBALS['_LANG']['send_success'];
                $data = ['send_status' => 1, 'last_send' => gmtime()];
            } else {
                $res_no = 1;
                $res_msg = $GLOBALS['_LANG']['send_failure'];
                $data = ['send_status' => 2, 'last_send' => gmtime()];
            }

            MassSmsLog::where('id', $id)->update($data);
            return sys_msg($res_msg, $res_no, $links);
        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_log') {
            admin_priv('mass_sms');

            $template_id = empty($_REQUEST['template_id']) ? 0 : intval($_REQUEST['template_id']);
            $this->smarty->assign('template_id', $template_id);
            $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'mass_sms.php?act=log_list&template_id=' . $template_id];
            if (isset($_POST['checkboxes'])) {
                if (isset($_POST['send'])) {
                    $this->smarty->assign('ur_here', $GLOBALS['_LANG']['batch_send']);

                    $record_count = count($_POST['checkboxes']);

                    $this->smarty->assign('record_count', $record_count);
                    $this->smarty->assign('page', 1);
                    $this->smarty->assign('log_list', implode(',', $_POST['checkboxes']));


                    return $this->smarty->display('mass_sms_batch_send.dwt');
                }

                if (isset($_POST['drop'])) {
                    $del_count = 0; //初始化删除数量
                    foreach ($_POST['checkboxes'] as $key => $id) {
                        MassSmsLog::where('id', $id)->delete();
                        $del_count++;
                    }
                    return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], $del_count), 0, $links);
                }
            } else {
                return sys_msg($GLOBALS['_LANG']['no_select_record'], 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- �        �部发送
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'send_all') {
            admin_priv('mass_sms');

            $template_id = empty($_REQUEST['template_id']) ? 0 : intval($_REQUEST['template_id']);
            $this->smarty->assign('template_id', $template_id);
            $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'mass_sms.php?act=log_list&template_id=' . $template_id];

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['send_all']);

            $res = MassSmsLog::select('id')
                ->where('template_id', $template_id)
                ->where('send_status', '<>', 1);
            $id_list = $this->baseRepository->getToArrayGet($res);
            $id_list = $this->baseRepository->getFlatten($id_list);


            $record_count = count($id_list);

            $this->smarty->assign('record_count', $record_count);
            $this->smarty->assign('page', 1);
            $this->smarty->assign('log_list', implode(',', $id_list));


            return $this->smarty->display('mass_sms_batch_send.dwt');
        }

        /*------------------------------------------------------ */
        //-- 批量发短信
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_send') {
            $check_auth = check_authz_json('mass_sms');
            if ($check_auth !== true) {
                return $check_auth;
            }


            $list = !empty($_REQUEST['list']) ? trim($_REQUEST['list']) : '';

            if ($list) {
                //过滤已发送短信
                $list = $this->baseRepository->getExplode($list);
                $res = MassSmsLog::select('id')
                    ->whereIn('id', $list)
                    ->where('send_status', '<>', 1);
                $list_array = $this->baseRepository->getToArrayGet($res);
                $list_array = $this->baseRepository->getFlatten($list_array);

                $list_count = count($list_array);
                //$list_array = explode(',', $list);
                if (!empty($list_array)) {
                    $first = array_shift($list_array);
                    //获取数据
                    $log_info = get_table_date("mass_sms_log", "id='$first'", ['*']);
                    $user_info = get_table_date("users", "user_id='{$log_info['user_id']}'", ['user_name', 'mobile_phone']);
                    $template_info = get_table_date("mass_sms_template", "id='{$log_info['template_id']}'", ['*']);
                    //判断手机号码
                    $data = [];
                    $data['last_send'] = gmtime();
                    if (empty($user_info['mobile_phone'])) {
                        $data['send_status'] = 2;
                    } else {
                        $shop_name = $this->merchantCommonService->getShopName($adminru['ru_id'], 1);
                        $smsParams = [
                            'shop_name' => $shop_name,
                            'shopname' => $shop_name,
                            'user_name' => $user_info['user_name'],
                            'username' => $user_info['user_name'],
                            'content' => $template_info['content'],
                            'mobile_phone' => $user_info['mobile_phone'],
                            'mobilephone' => $user_info['mobile_phone']
                        ];

                        $smsParams['temp_content'] = $template_info['temp_content'];
                        $send_status = $this->commonRepository->smsSend($user_info['mobile_phone'], $smsParams, '', false);

                        if ($send_status) {
                            $data['send_status'] = 1;
                        } else {
                            $data['send_status'] = 2;
                        }
                    }
                    MassSmsLog::where('id', $first)->update($data);
                    $result['data'] = array_merge($log_info, $user_info, $data);
                    $result['data']['last_send'] = local_date($GLOBALS['_CFG']['time_format'], $result['data']['last_send']);
                    $result['data']['send_status'] = $GLOBALS['_LANG']['send_status'][$result['data']['send_status']];
                }
            }

            if (isset($list_count) && !empty($list_count)) {
                $result['list'] = isset($list_array) && $list_array ? implode(',', $list_array) : '';
                $result['is_stop'] = 1;
            } else {
                $result['is_stop'] = 0;
            }

            return response()->json($result);
        }
    }
}
