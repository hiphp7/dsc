<?php

namespace App\Modules\Admin\Controllers;

use App\Models\MerchantsStepsFieldsCentent;
use App\Models\MerchantsStepsProcess;
use App\Models\MerchantsStepsTitle;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Common\ConfigManageService;
use App\Services\Merchant\MerchantsStepsManageService;

/**
 * 会员管理程序
 */
class MerchantsStepsController extends InitController
{
    protected $configManageService;
    protected $dscRepository;
    protected $baseRepository;
    protected $merchantsStepsManageService;

    public function __construct(
        ConfigManageService $configManageService,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        MerchantsStepsManageService $merchantsStepsManageService
    )
    {
        $this->configManageService = $configManageService;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->merchantsStepsManageService = $merchantsStepsManageService;
    }

    public function index()
    {
        /* ------------------------------------------------------ */
        //-- 申请流程列表
        /* ------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('merchants_setps');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => '01_merchants_steps_list']);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_merchants_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['02_merchants_add'], 'href' => 'merchants_steps.php?act=add']);

            $process_list = $this->merchantsStepsManageService->stepsProcessList();

            $this->smarty->assign('process_list', $process_list['process_list']);
            $this->smarty->assign('filter', $process_list['filter']);
            $this->smarty->assign('record_count', $process_list['record_count']);
            $this->smarty->assign('page_count', $process_list['page_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sort_user_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');


            return $this->smarty->display('merchants_steps_list.dwt');
        }
        /* ------------------------------------------------------ */
        //-- 编辑排序序号
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('merchants_setps');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $order = intval($_POST['val']);
            $name = MerchantsStepsProcess::where('id', $id)->value('process_title');
            $name = $name ? $name : '';

            $data = ['steps_sort' => $order];
            $res = MerchantsStepsProcess::where('id', $id)->update($data);

            if ($res >= 0) {
                return make_json_result($order);
            } else {
                return make_json_error(sprintf($GLOBALS['_LANG']['brandedit_fail'], $name));
            }
        }
        /* ------------------------------------------------------ */
        //-- ajax返回申请流程列表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $process_list = $this->merchantsStepsManageService->stepsProcessList();

            $this->smarty->assign('process_list', $process_list['process_list']);
            $this->smarty->assign('filter', $process_list['filter']);
            $this->smarty->assign('record_count', $process_list['record_count']);
            $this->smarty->assign('page_count', $process_list['page_count']);

            $sort_flag = sort_flag($process_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('merchants_steps_list.dwt'), '', ['filter' => $process_list['filter'], 'page_count' => $process_list['page_count']]);
        }

        /* ------------------------------------------------------ */
        //-- 申请流程信息列表
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'title_list') {
            /* 检查权限 */
            admin_priv('merchants_setps');

            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['05_merchants_title_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['04_merchants_add_info'], 'href' => 'merchants_steps.php?act=title_add&id=' . $id]);
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['01_merchants_list'], 'href' => 'merchants_steps.php?act=list']);

            session([
                'title_id' => $id
            ]);

            $title_list = $this->merchantsStepsManageService->stepsProcessTitleList($id);

            $this->smarty->assign('title_list', $title_list['title_list']);
            $this->smarty->assign('filter', $title_list['filter']);
            $this->smarty->assign('record_count', $title_list['record_count']);
            $this->smarty->assign('page_count', $title_list['page_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sort_user_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');
            $this->smarty->assign('tid', $id);


            return $this->smarty->display('merchants_steps_title_list.dwt');
        }

        /* ------------------------------------------------------ */
        //-- ajax返回申请流程信息列表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query_title') {
            $title_list = $this->merchantsStepsManageService->stepsProcessTitleList(session('title_id'));

            $this->smarty->assign('title_list', $title_list['title_list']);
            $this->smarty->assign('filter', $title_list['filter']);
            $this->smarty->assign('record_count', $title_list['record_count']);
            $this->smarty->assign('page_count', $title_list['page_count']);

            $sort_flag = sort_flag($title_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('merchants_steps_title_list.dwt'), '', ['filter' => $title_list['filter'], 'page_count' => $title_list['page_count']]);
        }

        /* ------------------------------------------------------ */
        //-- 修改显示状态
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_steps_show') {
            $check_auth = check_authz_json('merchants_setps');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $is_show = intval($_POST['val']);

            $data = ['is_show' => $is_show];
            $res = MerchantsStepsProcess::where('id', $id)->update($data);

            if ($res >= 0) {
                clear_cache_files();
                return make_json_result($is_show);
            }
        }

        /* ------------------------------------------------------ */
        //-- 添加流程步骤
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 检查权限 */
            admin_priv('merchants_setps');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_merchants_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_merchants_list'], 'href' => 'merchants_steps.php?act=list']);
            $this->smarty->assign('form_action', 'insert');


            return $this->smarty->display('merchants_steps_process.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 添加流程步骤，插�        �数据
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            /* 检查权限 */
            admin_priv('merchants_setps');

            $process_steps = isset($_POST['process_steps']) ? trim($_POST['process_steps']) : 1;
            $process_title = isset($_POST['process_title']) ? trim($_POST['process_title']) : '';
            $process_article = isset($_POST['process_article']) ? trim($_POST['process_article']) : 0;
            $steps_sort = isset($_POST['steps_sort']) ? trim($_POST['steps_sort']) + 0 : 0;
            $fields_next = isset($_POST['fields_next']) ? trim($_POST['fields_next']) : '';

            if (empty($fields_next)) {
                $add = $GLOBALS['_LANG']['fields_next_null'];
            } else {
                $res = MerchantsStepsProcess::where('process_title', $process_title)
                    ->orWhere('fields_next', $fields_next)
                    ->value('id');
                $res = $res ? $res : 0;
                if ($res > 0) {
                    $add = $GLOBALS['_LANG']['add_failure'];
                } else {
                    $parent = [
                        'process_steps' => $process_steps,
                        'process_title' => $process_title,
                        'process_article' => $process_article,
                        'steps_sort' => $steps_sort,
                        'fields_next' => $fields_next
                    ];

                    MerchantsStepsProcess::insert($parent);

                    $add = $GLOBALS['_LANG']['add_success_process'];

                    /* 记录管理员操作 */
                    admin_log($process_title, 'add', 'merchants_steps_process');
                }
            }

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'merchants_steps.php?act=list'];
            return sys_msg($add, 0, $link);
        }

        /* ------------------------------------------------------ */
        //-- 添加流程信息
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'title_add') {
            /* 检查权限 */
            admin_priv('merchants_setps');

            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_merchants_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_merchants_list'], 'href' => 'merchants_steps.php?act=title_list&id=' . $id]);
            $this->smarty->assign('form_action', 'title_insert');

            $res = MerchantsStepsProcess::whereRaw(1);
            $process_list = $this->baseRepository->getToArrayGet($res);

            $this->smarty->assign('process_list', $process_list);

            $this->smarty->assign('fields_steps', $id);


            return $this->smarty->display('merchants_steps_info.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 添加流程信息，插�        �数据
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'title_insert') {
            /* 检查权限 */
            admin_priv('merchants_setps');

            $tid = 0;
            $fields_steps = isset($_POST['fields_steps']) ? intval($_POST['fields_steps']) : 1; //所属流程
            $fields_titles = isset($_POST['fields_titles']) ? trim($_POST['fields_titles']) : ''; //内容标题
            $titles_annotation = isset($_POST['titles_annotation']) ? stripslashes(trim($_POST['titles_annotation'])) : ''; //标题注释
            $steps_style = isset($_POST['steps_style']) ? trim($_POST['steps_style']) : ''; //前端显示样式：表格、纯表单
            $fields_special = isset($_POST['fields_special']) ? stripslashes(trim($_POST['fields_special'])) : ''; //特殊说明
            $special_type = isset($_POST['special_type']) ? intval($_POST['special_type']) : 1; //特殊说明显示位置

            $date = isset($_POST['merchants_date']) ? $_POST['merchants_date'] : []; //表单字段
            $dateType = isset($_POST['merchants_dateType']) ? $_POST['merchants_dateType'] : []; //数据类型
            $length = isset($_POST['merchants_length']) ? $_POST['merchants_length'] : []; //数据长度
            $notnull = isset($_POST['merchants_notnull']) ? $_POST['merchants_notnull'] : []; //是否为空
            $coding = isset($_POST['merchants_coding']) ? $_POST['merchants_coding'] : []; //数据编码
            $formName = isset($_POST['merchants_formName']) ? $_POST['merchants_formName'] : []; //表单标题

            $form = isset($_POST['merchants_form']) ? $_POST['merchants_form'] : []; //表单类型
            $formOther = isset($_POST['merchants_formOther']) ? $_POST['merchants_formOther'] : []; //选择类型
            $formSize = isset($_POST['merchants_formSize']) ? $_POST['merchants_formSize'] : []; //表单长度
            $rows = isset($_POST['merchants_rows']) ? $_POST['merchants_rows'] : []; //行数以及宽度  --- 行数
            $cols = isset($_POST['merchants_cols']) ? $_POST['merchants_cols'] : []; //行数以及宽度  --- 宽度
            $formOtherSize = isset($_POST['merchants_formOtherSize']) ? $_POST['merchants_formOtherSize'] : []; //日期表单长度
            $formName_special = isset($_POST['formName_special']) ? $_POST['formName_special'] : []; //表单注释
            $fields_sort = isset($_POST['fields_sort']) ? $_POST['fields_sort'] : []; //显示排序

            $form_array = [
                'form' => $form,
                'formOther' => $formOther,
                'formSize' => $formSize,
                'rows' => $rows,
                'cols' => $cols,
                'formOtherSize' => $formOtherSize,
                'formName_special' => $formName_special,
                'date' => $date //字段
            ];

            $form_choose = get_steps_form_choose($form_array); //表单
            //添加所属步骤基本信息
            $res = get_merchants_steps_title_insert_update($fields_steps, $fields_titles, $titles_annotation, $steps_style, $fields_special, $special_type, 'insert', $tid);

            $fields_steps = MerchantsStepsTitle::where('tid', $res['tid'])->value('fields_steps');
            $fields_steps = $fields_steps ? $fields_steps : 0;

            if ($res['true']) {
                $steps = get_merchants_steps_fields_admin('merchants_steps_fields', $date, $dateType, $length, $notnull, $coding, $formName, $fields_sort, $res['tid']); //数据表字段

                get_merchants_steps_fields_centent_insert_update($steps['textFields'], $steps['fieldsDateType'], $steps['fieldsLength'], $steps['fieldsNotnull'], $steps['fieldsFormName'], $steps['fieldsCoding'], $steps['fields_sort'], $steps['will_choose'], $form_choose['chooseForm'], $res['tid']);

                $update = $GLOBALS['_LANG']['insert_success'];
            } else {
                $update = $GLOBALS['_LANG']['insert_failure'];
            }

            // 记录管理员操作
            admin_log($GLOBALS['_LANG']['merchants_fields_add'], 'add', 'merchants_steps');

            // 提示信息
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'merchants_steps.php?act=title_list&id=' . $fields_steps];
            return sys_msg($GLOBALS['_LANG']['add_success'], 0, $link);
        }

        /* ------------------------------------------------------ */
        //-- 编辑申请流程
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('merchants_setps');

            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $res = MerchantsStepsProcess::where('id', $id);
            $process_info = $this->baseRepository->getToArrayFirst($res);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_merchants_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_merchants_list'], 'href' => 'merchants_steps.php?act=list']);


            $this->smarty->assign('process_info', $process_info);
            $this->smarty->assign('form_action', 'update');
            return $this->smarty->display('merchants_steps_process.dwt');
        }


        /* ------------------------------------------------------ */
        //-- 编辑申请流程信息
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'title_edit') {
            /* 检查权限 */
            admin_priv('merchants_setps');

            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $res = MerchantsStepsTitle::where('tid', $id);
            $res = $res->with(['getMerchantsStepsProcess' => function ($query) {
                $query->select('id', 'process_title');
            }]);
            $title_info = $this->baseRepository->getToArrayFirst($res);

            $title_info['process_title'] = '';
            if (isset($title_info['get_merchants_steps_process']) && !empty($title_info['get_merchants_steps_process'])) {
                $title_info['process_title'] = $title_info['get_merchants_steps_process']['process_title'];
            }

            $this->smarty->assign('title_info', $title_info);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_merchants_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_merchants_list'], 'href' => 'merchants_steps.php?act=title_list&id=' . $title_info['fields_steps']]);

            $res = MerchantsStepsProcess::whereRaw(1);
            $process_list = $this->baseRepository->getToArrayGet($res);

            $this->smarty->assign('process_list', $process_list);

            $res = MerchantsStepsFieldsCentent::where('tid', $id);
            $centent = $this->baseRepository->getToArrayFirst($res);

            $cententFields = get_fields_centent_info($centent['id'], $centent['textFields'], $centent['fieldsDateType'], $centent['fieldsLength'], $centent['fieldsNotnull'], $centent['fieldsFormName'], $centent['fieldsCoding'], $centent['fieldsForm'], $centent['fields_sort'], $centent['will_choose']);

            $this->smarty->assign('cententFields', $cententFields);
            $this->smarty->assign('fieldsCount', count($cententFields) + 1);
            $this->smarty->assign('fields_steps', $title_info['fields_steps']);


            $this->smarty->assign('form_action', 'title_update');
            $this->smarty->assign('tid', $id);
            return $this->smarty->display('merchants_steps_info.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 更新申请流程
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('merchants_setps');

            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $process_steps = isset($_POST['process_steps']) ? trim($_POST['process_steps']) : 1;
            $process_title = isset($_POST['process_title']) ? trim($_POST['process_title']) : '';
            $process_article = isset($_POST['process_article']) ? trim($_POST['process_article']) : 0;
            $steps_sort = isset($_POST['steps_sort']) ? trim($_POST['steps_sort']) + 0 : 0;
            $fields_next = isset($_POST['fields_next']) ? trim($_POST['fields_next']) : '';

            $res = MerchantsStepsProcess::where(function ($query) use ($process_title, $fields_next, $id) {
                $query->where('process_title', $process_title)
                    ->orWhere('fields_next', $fields_next);
            })->where('id', '<>', $id)
                ->value('id');
            $res = $res ? $res : 0;

            if ($res > 0) {
                $update = $GLOBALS['_LANG']['update_failure'];
            } else {
                $parent = [
                    'process_steps' => $process_steps,
                    'process_title' => $process_title,
                    'process_article' => $process_article,
                    'steps_sort' => $steps_sort,
                    'fields_next' => $fields_next
                ];

                MerchantsStepsProcess::where('id', $id)->update($parent);

                $update = $GLOBALS['_LANG']['update_success'];
            }

            /* 提示信息 */
            $links[0]['text'] = $GLOBALS['_LANG']['goto_list'];
            $links[0]['href'] = 'merchants_steps.php?act=list';
            $links[1]['text'] = $GLOBALS['_LANG']['go_back'];
            $links[1]['href'] = 'merchants_steps.php?act=edit&id=' . $id;

            return sys_msg($update, 0, $links);
        }

        /* ------------------------------------------------------ */
        //-- 更新申请流程信息
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'title_update') {
            /* 检查权限 */
            admin_priv('merchants_setps');

            $tid = isset($_REQUEST['tid']) ? intval($_REQUEST['tid']) : 0;
            $fields_steps = isset($_POST['fields_steps']) ? intval($_POST['fields_steps']) : 1; //所属流程
            $fields_titles = isset($_POST['fields_titles']) ? trim($_POST['fields_titles']) : ''; //内容标题
            $titles_annotation = isset($_POST['titles_annotation']) ? trim($_POST['titles_annotation']) : ''; //标题注释
            $steps_style = isset($_POST['steps_style']) ? trim($_POST['steps_style']) : ''; //前端显示样式：表格、纯表单
            $fields_special = isset($_POST['fields_special']) ? stripslashes(trim($_POST['fields_special'])) : ''; //特殊说明
            $special_type = isset($_POST['special_type']) ? intval($_POST['special_type']) : 1; //特殊说明显示位置

            $date = isset($_POST['merchants_date']) ? $_POST['merchants_date'] : []; //表单字段
            $dateType = isset($_POST['merchants_dateType']) ? $_POST['merchants_dateType'] : []; //数据类型
            $length = isset($_POST['merchants_length']) ? $_POST['merchants_length'] : []; //数据长度
            $notnull = isset($_POST['merchants_notnull']) ? $_POST['merchants_notnull'] : []; //是否为空
            $coding = isset($_POST['merchants_coding']) ? $_POST['merchants_coding'] : []; //数据编码
            $formName = isset($_POST['merchants_formName']) ? $_POST['merchants_formName'] : []; //表单标题

            $form = isset($_POST['merchants_form']) ? $_POST['merchants_form'] : []; //表单类型
            $formOther = isset($_POST['merchants_formOther']) ? $_POST['merchants_formOther'] : []; //选择类型
            $formSize = isset($_POST['merchants_formSize']) ? $_POST['merchants_formSize'] : []; //表单长度
            $rows = isset($_POST['merchants_rows']) ? $_POST['merchants_rows'] : []; //行数以及宽度  --- 行数
            $cols = isset($_POST['merchants_cols']) ? $_POST['merchants_cols'] : []; //行数以及宽度  --- 宽度
            $formOtherSize = isset($_POST['merchants_formOtherSize']) ? $_POST['merchants_formOtherSize'] : []; //日期表单长度
            $formName_special = isset($_POST['formName_special']) ? $_POST['formName_special'] : []; //表单注释
            $fields_sort = isset($_POST['fields_sort']) ? $_POST['fields_sort'] : []; //显示排序

            $form_array = [
                'form' => $form,
                'formOther' => $formOther,
                'formSize' => $formSize,
                'rows' => $rows,
                'cols' => $cols,
                'formOtherSize' => $formOtherSize,
                'formName_special' => $formName_special,
                'date' => $date //字段
            ];
            $form_choose = get_steps_form_choose($form_array); //表单

            $steps = get_merchants_steps_fields_admin('merchants_steps_fields', $date, $dateType, $length, $notnull, $coding, $formName, $fields_sort, $tid); //数据表字段

            get_merchants_steps_fields_centent_insert_update($steps['textFields'], $steps['fieldsDateType'], $steps['fieldsLength'], $steps['fieldsNotnull'], $steps['fieldsFormName'], $steps['fieldsCoding'], $steps['fields_sort'], $steps['will_choose'], $form_choose['chooseForm'], $tid);

            //添加所属步骤基本信息
            $res = get_merchants_steps_title_insert_update($fields_steps, $fields_titles, $titles_annotation, $steps_style, $fields_special, $special_type, 'update', $tid);

            if ($res) {
                $update = $GLOBALS['_LANG']['update_success'];
            } else {
                $update = $GLOBALS['_LANG']['update_failure'];
            }

            $pid = MerchantsStepsTitle::where('tid', $tid)->value('fields_steps');
            $pid = $pid ? $pid : '';

            /* 提示信息 */
            $links[0]['text'] = $GLOBALS['_LANG']['goto_list'];
            $links[0]['href'] = 'merchants_steps.php?act=title_list&id=' . $pid;
            $links[1]['text'] = $GLOBALS['_LANG']['go_back'];
            $links[1]['href'] = 'merchants_steps.php?act=title_edit&id=' . $tid;

            return sys_msg($update, 0, $links);
        }

        /* ------------------------------------------------------ */
        //-- 删除申请流程步骤
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            /* 检查权限 */
            admin_priv('merchants_setps_drop');

            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            MerchantsStepsProcess::where('id', $id)->delete();

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'merchants_steps.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['remove_success'], 0, $link);
        }

        /* ------------------------------------------------------ */
        //-- 删除申请流程信息
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'titleList_remove') {
            /* 检查权限 */
            admin_priv('merchants_setps_drop');

            $tid = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $fields_steps = MerchantsStepsTitle::where('tid', $tid)->value('fields_steps');
            $fields_steps = $fields_steps ? $fields_steps : 0;

            MerchantsStepsTitle::where('tid', $tid)->delete();

            MerchantsStepsFieldsCentent::where('tid', $tid)->delete();

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'merchants_steps.php?act=title_list&id=' . $fields_steps];
            return sys_msg($GLOBALS['_LANG']['remove_success'], 0, $link);
        }

        /* ------------------------------------------------------ */
        //-- 删除申请流程信息
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'title_remove') {
            /* 检查权限 */
            admin_priv('merchants_setps_drop');

            $tid = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $objName = isset($_REQUEST['objName']) ? $_REQUEST['objName'] : '';

            $fields_date_all = get_fields_date_title_remove($tid, $objName);

            if (count($fields_date_all) == 1) {
                MerchantsStepsFieldsCentent::where('tid', $tid)->delete();

                get_Add_Drop_fields($objName, '', 'merchants_steps_fields', 'delete');
            } else {
                $fields_date = get_fields_date_title_remove($tid, $objName, 1);
                get_title_remove($tid, $fields_date, $objName);
            }

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'merchants_steps.php?act=title_edit&id=' . $tid];
            return sys_msg($GLOBALS['_LANG']['remove_success'], 0, $link);
        }

        /* ------------------------------------------------------ */
        //-- 店铺设置
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'step_up') {
            /* 检查权限 */
            admin_priv('merchants_setps');

            $this->dscRepository->helpersLang('shop_config', 'admin');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_seller_stepup']);

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => '01_seller_stepup']);

            $group_list = $this->configManageService->getUpSettings('seller');
            $this->smarty->assign('group_list', $group_list);

            return $this->smarty->display('merchants_step_up.dwt');
        }
    }
}
