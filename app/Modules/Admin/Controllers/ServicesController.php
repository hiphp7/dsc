<?php

namespace App\Modules\Admin\Controllers;

use App\Models\AdminUser;
use App\Models\ImDialog;
use App\Models\ImService;
use App\Repositories\Common\BaseRepository;
use App\Services\Services\ServicesManageService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ServicesController extends InitController
{
    protected $baseRepository;
    protected $servicesManageService;

    public function __construct(
        BaseRepository $baseRepository,
        ServicesManageService $servicesManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->servicesManageService = $servicesManageService;
    }

    public function index()
    {
        $act = !empty($_REQUEST['act']) ? $_REQUEST['act'] : 'index';

        $extension_code = isset($extension_code) ? trim($extension_code) : '';
        $is_ajax = isset($_GET['is_ajax']) ? trim($_GET['is_ajax']) : '';

        if ($act == 'list') {
            $extension_arr = ['platform', 'seller', 'deleted'];
            if (in_array($extension_code, $extension_arr)) {
                $pix = $extension_code;
            } else {
                $pix = '';
            }

            $services = $this->servicesManageService->servicesList();

            $this->smarty->assign('services_list', $services['list']);
            $this->smarty->assign('filter', $services['filter']);
            $this->smarty->assign('record_count', $services['record_count']);
            $this->smarty->assign('page_count', $services['page_count']);
            $this->smarty->assign('extension_code', isset($services['extension_code']) ? $services['extension_code'] : '');
            $this->smarty->assign('removed', ($services['filter']['extension_code'] == 'deleted') ? 1 : 0);
            $this->smarty->assign('pix', $pix);

            if ($is_ajax === '1') {
                return make_json_result(
                    $this->smarty->fetch('services_list.dwt'),
                    '',
                    ['filter' => $services['filter'], 'page_count' => $services['page_count']]
                );
            }
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['services_list']); // 当前导航
            $this->smarty->assign('full_page', 1);

            /** 接待统计 */
            $times['times'] = $this->servicesManageService->statisticsReception();
            $times['today_times'] = $this->servicesManageService->statisticsReception(1);
            $times['people'] = $this->servicesManageService->statisticsReceptionCustomer();
            $times['today_people'] = $this->servicesManageService->statisticsReceptionCustomer(1);

            $this->smarty->assign('times', $times); //

            return $this->smarty->display('services_list.dwt');
        } //已删除客服列表
        elseif ($act == 'removed' || $act == 'backservice') {
            if ($act == 'backservice') {
                //恢复客服
                $id = empty($_GET['id']) ? 0 : strip_tags($_GET['id']);
                $this->servicesManageService->backToService($id);
            }

            return dsc_header("Location: services.php?act=list&extension_code=deleted\n");
        } //添加客服页面
        elseif ($act == 'add') {
            $extension_arr = ['platform', 'seller', 'deleted'];
            if (in_array($extension_code, $extension_arr)) {
                $pix = '&extension_code=' . $extension_code;
            } else {
                $pix = '';
            }

            $services = $this->servicesManageService->servicesList();
            $this->smarty->assign('services_list', $services['list']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['services_manage']); // 当前导航
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['services_list'], 'href' => 'services.php?act=list' . $pix]);

            $admins = $this->servicesManageService->adminList();

            foreach ($admins as $k => $v) {
                if ($v['user_id'] == session('admin_id')) {
                    unset($admins[$k]);
                }
            }

            if (empty($admins)) {
                return sys_msg($GLOBALS['_LANG']['not_select_manage'], 1);
            }

            $this->smarty->assign('admin_list', $admins); //
            $this->smarty->assign('form_action', 'insert'); //

            return $this->smarty->display('services_add.dwt');
        } //添加、更新客服功能
        elseif ($act == 'insert' || $act == 'update') {
            $services_name = empty($_POST['services_name']) ? 0 : strip_tags($_POST['services_name']);
            $services_desc = empty($_POST['services_desc']) ? 0 : strip_tags($_POST['services_desc']);
            $services = empty($_POST['services']) ? 0 : intval($_POST['services']);

            if (empty($services_name)) {
                return sys_msg($GLOBALS['_LANG']['please_enter_nickname'], 1);
            } elseif (empty($services_desc)) {
                return sys_msg($GLOBALS['_LANG']['please_enter_desc'], 1);
            } elseif (empty($services)) {
                return sys_msg($GLOBALS['_LANG']['please_enter_manage'], 1);
            }

            $userName = AdminUser::where('user_id', $services)->value('user_name');
            $userName = $userName ? $userName : '';

            if (!$userName) {
                return sys_msg($GLOBALS['_LANG']['not_manage'], 1);
            }

            if ($act == 'insert') {
                $res = ImService::where('user_id', $services);
                $res = $this->baseRepository->getToArrayFirst($res);

                if (!empty($res) && $res['status'] == 1) {
                    return sys_msg($GLOBALS['_LANG']['is_manage_services'], 1);
                } elseif (!empty($res) && ($res['status'] === 0 || $res['status'] === '0')) {
                    $data = [
                        'nick_name' => $services_name,
                        'post_desc' => $services_desc,
                        'status' => 1
                    ];
                    $res = ImService::where('user_id', $services)->update($data);

                } else {
                    $data = [
                        'user_id' => $services,
                        'user_name' => $userName,
                        'nick_name' => $services_name,
                        'post_desc' => $services_desc,
                        'chat_status' => 0,
                        'status' => 1
                    ];
                    $res = ImService::insert($data);
                }
                if ($res < 1) {
                    return sys_msg($GLOBALS['_LANG']['add_services_fail'], 1);
                }
            } elseif ($act == 'update') {
                $id = empty($_POST['id']) ? 0 : intval($_POST['id']);
                $data = [
                    'user_id' => $services,
                    'nick_name' => $services_name,
                    'post_desc' => $services_desc
                ];
                $res = ImService::where('id', $id)->update($data);

                if (!$res) {
                    return sys_msg($GLOBALS['_LANG']['update_services_success'], 1);
                }
            }
            admin_log('', 'service', 'insert'); // 记录日志

            return dsc_header("Location: services.php?act=list\n");
        } //删除客服
        elseif ($act == 'remove') {
            $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
            if (!$is_ajax) {
                return make_json_error("invalid method");
            }
            if (!$id) {
                return make_json_error("invalid params");
            }
            $res = ImService::where('id', $id)->where('status', 1);
            $res = $this->baseRepository->getToArrayFirst($res);

            if (!$res) {
                return make_json_error($GLOBALS['_LANG']['services_existent']);
            }

            //删除操作
            $data = ['status' => 0];
            $res = ImService::where('id', $id)->update($data);

            if ($res < 1) {
                return make_json_error($GLOBALS['_LANG']['services_existent']);
            }

            //退出会话
            $data = ['services_id' => 0];
            ImDialog::where('services_id', $id)->update($data);

            $services = $this->servicesManageService->servicesList();
            $this->smarty->assign('services_list', $services['list']);
            $this->smarty->assign('filter', $services['filter']);
            $this->smarty->assign('record_count', $services['record_count']);
            $this->smarty->assign('page_count', $services['page_count']);
            return make_json_result($this->smarty->fetch('services_list.dwt'), $GLOBALS['_LANG']['carddrop_succeed']);
        }
        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($act == 'batch') {
            /* 取得要操作的记录编号 */
            if (empty($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_record_selected']);
            } else {
                /* 检查权限 */
                admin_priv('services');

                $ids = $_POST['checkboxes'];

                if (isset($_POST['remove'])) {
                    /* 删除记录 */
                    $ids = $this->baseRepository->getExplode($ids);
                    $data = ['status' => 0];
                    $res = ImService::whereIn('id', $ids)->update($data);

                    /* 记日志 */
                    admin_log('', 'service_batch_remove', 'service');

                    /* 清除缓存 */
                    clear_cache_files();
                    $link[] = ['text' => $GLOBALS['_LANG']['back'], 'href' => 'services.php?act=list'];
                    return sys_msg($GLOBALS['_LANG']['batch_drop_ok'], '', $link);
                }
            }
        }
        /*------------------------------------------------------ */
        //-- 编辑
        /*------------------------------------------------------ */
        elseif ($act == 'edit') {
            $id = empty($_GET['id']) ? 0 : intval($_GET['id']);

            $res = ImService::where('id', $id);
            $customer = $this->baseRepository->getToArrayFirst($res);

            // 验证客服管理员账号状态
            $res = AdminUser::where('user_id', $customer['user_id']);
            $admin = $this->baseRepository->getToArrayFirst($res);

            if (empty($admin)) {
                sys_msg($GLOBALS['_LANG']['not_manage'], 1);
            }

            $admins = $this->servicesManageService->adminList($id);
            $this->smarty->assign('form_action', 'update'); //
            $this->smarty->assign('id', $id); //
            $this->smarty->assign('admin_list', $admins); //
            $this->smarty->assign('customer', $customer); //
            return $this->smarty->display('services_add.dwt');
        } elseif ($act == 'dialog_list') {
            //会话记录
            $id = empty($_GET['id']) ? 0 : intval($_GET['id']);

            $list = $this->servicesManageService->dialogList($id, 0);

            $this->smarty->assign('id', $id); //
            $this->smarty->assign('dialog_list', $list); //
            $this->smarty->assign('full_page', 1);

            return $this->smarty->display('services_dialog_list.dwt');
        } elseif ($act == 'dialog_list_ajax') {
            //异步获取会话列表
            $id = empty($_POST['id']) ? 0 : intval($_POST['id']);
            $val = empty($_POST['val']) ? 0 : intval($_POST['val']);

            $list = $this->servicesManageService->dialogList($id, $val);

            $this->smarty->assign('dialog_list', $list); //
            return make_json_result($this->smarty->fetch('services_dialog_list.dwt'));
        } elseif ($act == 'message_list_ajax') {
            //消息列表
            $id = empty($_POST['id']) ? 0 : intval($_POST['id']);
            $customer_id = empty($_POST['customer_id']) ? 0 : intval($_POST['customer_id']);
            $service_id = empty($_POST['service_id']) ? 0 : intval($_POST['service_id']);
            $page = empty($_POST['page']) ? 0 : intval($_POST['page']);
            $keyword = empty($_POST['keyword']) ? 0 : strip_tags(trim($_POST['keyword']));

            $dialog = $this->servicesManageService->dialog($id);

            $message = $this->servicesManageService->messageList($customer_id, $service_id, $page, $keyword);
            $list = $message['list'];
            $count = $message['count'];
            $this->smarty->assign('message_page', 1); //
            $this->smarty->assign('dialog', $dialog); //
            $this->smarty->assign('message_list', $list); //
            return make_json_result($this->smarty->fetch('services_dialog_list.dwt'), $count);
        } /** 生成word */
        elseif ($act == 'generage_word') {
            $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
            $customer_id = empty($_GET['customer_id']) ? 0 : intval($_GET['customer_id']);
            $service_id = empty($_GET['service_id']) ? 0 : intval($_GET['service_id']);

            $message = $this->servicesManageService->messageList($customer_id, $service_id, -1);
            $list = $message['list'];
            $dialog = $this->servicesManageService->dialog($id);

            if ($list) {
                $excel = new Spreadsheet();
                //设置单元格宽度
                $excel->getActiveSheet()->getDefaultColumnDimension()->setAutoSize(true);
                //设置表格的宽度  手动
                $excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                //设置标题
                $rowVal = [
                    0 => lang('admin/services.user_name'),
                    1 => lang('admin/services.message'),
                    2 => lang('admin/services.add_time'),
                ];
                foreach ($rowVal as $k => $r) {
                    $excel->getActiveSheet()->getStyleByColumnAndRow($k + 1, 1)->getFont()->setBold(true);//字体加粗
                    $excel->getActiveSheet()->getStyleByColumnAndRow($k + 1, 1)->getAlignment(); //文字居中
                    $excel->getActiveSheet()->setCellValueByColumnAndRow($k + 1, 1, $r);
                }
                //设置当前的sheet索引 用于后续内容操作
                $excel->setActiveSheetIndex(0);
                $objActSheet = $excel->getActiveSheet();
                //设置当前活动的sheet的名称
                $title = lang('admin/services.customer_service_message');
                $objActSheet->setTitle($title);
                //设置单元格内容
                foreach ($list as $k => $v) {
                    $num = $k + 2;
                    if ($v['user_type'] == 1) {
                        $userName = $dialog['user_name'];
                    } elseif ($v['user_type'] == 2) {
                        $userName = $dialog['nick_name'];
                    }
                    $excel->setActiveSheetIndex(0)
                        //Excel的第A列，uid是你查出数组的键值，下面以此类推
                        ->setCellValue('A' . $num, $userName)
                        ->setCellValue('B' . $num, strip_tags($v['message']))
                        ->setCellValue('C' . $num, $v['add_time']);
                }
                $name = local_date('Y-m-d'); //设置文件名
                header("Content-Type: application/force-download");
                header("Content-Type: application/octet-stream");
                header("Content-Type: application/download");
                header("Content-Transfer-Encoding:utf-8");
                header("Pragma: no-cache");
                header('Content-Type: application/vnd.ms-e xcel');
                header('Content-Disposition: attachment;filename="' . $title . '_' . urlencode($name) . '.xls"');
                header('Cache-Control: max-age=0');
                $objWriter = IOFactory::createWriter($excel, 'Xls');
                $objWriter->save('php://output');
                exit;
            }
        }
        /*--------------------------------------------------*/
        //--根据日期找出记录
        /*--------------------------------------------------*/
        elseif ($act == 'get_message_by_date') {
            //消息列表
            $id = empty($_POST['id']) ? 0 : intval($_POST['id']);
            $customer_id = empty($_POST['customer_id']) ? 0 : intval($_POST['customer_id']);
            $service_id = empty($_POST['service_id']) ? 0 : intval($_POST['service_id']);
            $page = empty($_POST['page']) ? 0 : intval($_POST['page']);
            $date = empty($_POST['date']) ? 0 : strip_tags(trim($_POST['date']));
            $dialog = $this->servicesManageService->dialog($id);

            $this->smarty->assign('dialog', $dialog); //

            $message = $this->servicesManageService->messageList($customer_id, $service_id, $page, '', strtotime($date));

            $list = $message['list'];
            $count = $message['count'];
            $this->smarty->assign('message_page', 1); //
            $this->smarty->assign('message_list', $list); //
            return make_json_result($this->smarty->fetch('services_dialog_list.dwt'), $count);
        }
    }
}
