<?php

namespace App\Modules\Admin\Controllers;

use App\Models\ShopConfig;
use App\Models\SmsTemplate;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Sms\SmsManageService;
use App\Services\Sms\SmsTemplatesService;

/**
 * 短信模板管理程序
 */
class DscsmsConfigureController extends InitController
{
    protected $smsTemplatesService;
    protected $smsManageService;
    protected $commonRepository;
    protected $timeRepository;
    protected $config;
    protected $dscRepository;

    public function __construct(
        SmsTemplatesService $smsTemplatesService,
        SmsManageService $smsManageService,
        CommonRepository $commonRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository
    )
    {
        $this->smsTemplatesService = $smsTemplatesService;
        $this->smsManageService = $smsManageService;
        $this->commonRepository = $commonRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    public function index()
    {
        /* act操作项的初始化 */
        $act = request()->input('act', 'list');

        /* 检查权限 */
        admin_priv('sms_setting');

        $send_time = $this->smsTemplatesService->SendTime();
        $actname = $this->smsTemplatesService->ActName();
        $test = $actname['test'];
        $template = $actname['template'];

        $is_temp = $this->smsManageService->isShowSmsTempSign();
        $this->smarty->assign('is_temp', $is_temp);

        $default_code = $this->smsManageService->getDefaultSmsCode();
        $this->smarty->assign('default_code', $default_code);

        $this->smarty->assign('config', $this->config);

        $this->smarty->assign('menu_select', ['action' => '01_system', 'current' => 'dscsms_configure']);
        /*------------------------------------------------------ */
        //-- 阿里大鱼短信模板列表
        /*------------------------------------------------------ */
        if ($act == 'list') {
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['alidayu_add'], 'href' => 'dscsms_configure.php?act=add']);
            $this->smarty->assign('action_link2', ['text2' => $GLOBALS['_LANG']['alidayu_set'], 'href' => 'dscsms_configure.php?act=set_up']);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['dscsms_configure']);
            $this->smarty->assign('form_act', 'insert');

            $sms_list = $this->smsManageService->getSmsList($send_time);

            $this->smarty->assign('smslist', $sms_list['smslist']);
            $this->smarty->assign('filter', $sms_list['filter']);
            $this->smarty->assign('record_count', $sms_list['record_count']);
            $this->smarty->assign('page_count', $sms_list['page_count']);
            $this->smarty->assign('full_page', 1);

            /* 列表页面 */
            return $this->smarty->display('dscsms_configure_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- ajax返回短信模板列表
        /*------------------------------------------------------ */
        elseif ($act == 'query') {
            $sms_list = $this->smsManageService->getSmsList($send_time);

            $this->smarty->assign('smslist', $sms_list['smslist']);
            $this->smarty->assign('filter', $sms_list['filter']);
            $this->smarty->assign('record_count', $sms_list['record_count']);
            $this->smarty->assign('page_count', $sms_list['page_count']);

            $sort_flag = sort_flag($sms_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('dscsms_configure_list.dwt'), '', ['filter' => $sms_list['filter'], 'page_count' => $sms_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 添加短信模板
        /*------------------------------------------------------ */
        if ($act == 'add') {
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['alidayu_list'], 'href' => 'dscsms_configure.php?act=list']);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['dscsms_configure']);
            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('send_time', $send_time);

            /* 列表页面 */
            return $this->smarty->display('dscsms_configure_info.dwt');
        }

        if ($act == 'set_update') {
            $other['value'] = isset($_REQUEST['alayu_type']) && !empty($_REQUEST['alayu_type']) ? intval($_REQUEST['alayu_type']) : 0;

            ShopConfig::where('code', 'alidayu_type')->update($other);

            cache()->forget('shop_config');

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'dscsms_configure.php?act=list'];
            return sys_msg('', 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑短信模板
        /*------------------------------------------------------ */
        if ($act == 'edit') {
            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['alidayu_list'], 'href' => 'dscsms_configure.php?act=list']);

            $info = $this->smsManageService->getSmsInfo($id);

            $this->smarty->assign('info', $info);
            $this->smarty->assign('send_time', $send_time);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['dscsms_configure']);
            $this->smarty->assign('form_act', 'update');

            /* 列表页面 */
            return $this->smarty->display('dscsms_configure_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑短信模板
        /*------------------------------------------------------ */
        elseif ($act == 'insert' || $act == 'update') {
            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $other['set_sign'] = empty($_POST['set_sign']) ? '' : trim($_POST['set_sign']);
            $other['temp_id'] = empty($_POST['temp_id']) ? '' : trim($_POST['temp_id']);
            $other['sender'] = empty($_POST['sender']) ? '' : trim($_POST['sender']);
            $other['temp_content'] = empty($_POST['temp_content']) ? '' : trim($_POST['temp_content']);
            $other['send_time'] = empty($_POST['send_time']) ? '' : trim($_POST['send_time']);
            $other['add_time'] = $this->timeRepository->getGmTime();

            if ($id) {
                SmsTemplate::where('id', $id)->update($other);

                $href = 'dscsms_configure.php?act=edit&id=' . $id;

                $lang_name = $GLOBALS['_LANG']['edit_success'];
                $temp_id = $id;
            } else {
                $temp_id = SmsTemplate::insertGetId($other);

                $href = 'dscsms_configure.php?act=list';
                $lang_name = $GLOBALS['_LANG']['add_success'];
            }

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => $href];
            return sys_msg(sprintf($lang_name, htmlspecialchars(stripslashes($temp_id))), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除短信模板
        /*------------------------------------------------------ */
        elseif ($act == 'remove') {
            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $temp_id = SmsTemplate::where('id', $id)->value('temp_id');
            $temp_id = $temp_id ? $temp_id : 0;

            SmsTemplate::where('id', $id)->delete();

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'dscsms_configure.php?act=list'];
            return sys_msg(sprintf($GLOBALS['_LANG']['remove_success'], $temp_id), 0, $link);
        }

        /* ------------------------------------------------------ */
        //-- 读取默认模板信息
        /* ------------------------------------------------------ */
        if ($act == 'loat_template') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $tpl = !empty($_REQUEST['tpl']) ? trim($_REQUEST['tpl']) : '';

            if ($id) {
                $info = $this->smsManageService->getSmsInfo($id);
                $content = $info['temp_content'] ?? '';
            } else {
                $content = $template[$tpl];
            }

            $result['content'] = $content;

            $result['tpl'] = $tpl;

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 读取默认模板信息
        /* ------------------------------------------------------ */
        if ($act == 'sms_template') {
            load_lang('sms', 'admin');

            $res = ['error' => 0, 'message' => '', 'content' => ''];

            $set_sign = isset($_REQUEST['set_sign']) && !empty($_REQUEST['set_sign']) ? trim($_REQUEST['set_sign']) : '';
            $temp_id = isset($_REQUEST['temp_id']) && !empty($_REQUEST['temp_id']) ? trim($_REQUEST['temp_id']) : '';
            $temp_content = isset($_REQUEST['temp_content']) && !empty($_REQUEST['temp_content']) ? trim($_REQUEST['temp_content']) : '';
            $send_time = isset($_REQUEST['send_time']) && !empty($_REQUEST['send_time']) ? trim($_REQUEST['send_time']) : '';

            if ($GLOBALS['_CFG']['sms_shop_mobile']) {
                $smsParams = $this->smsManageService->getTestSmsParams($send_time, $test);

                $smsParams['set_sign'] = $set_sign;
                $smsParams['temp_id'] = $temp_id;
                $smsParams['temp_content'] = $temp_content;

                $result = $this->commonRepository->smsSend($GLOBALS['_CFG']['sms_shop_mobile'], $smsParams, $send_time);

                $res['error'] = 0;
                $res['msg'] = $result ? $GLOBALS['_LANG']['send_ok'] : $GLOBALS['_LANG']['send_error'];
            } else {
                $res['error'] = 1;
            }

            $res['set_sign'] = $set_sign;
            $res['temp_id'] = $temp_id;
            $res['temp_content'] = $temp_content;
            $res['send_time'] = $send_time;

            return response()->json($res);
        }
    }
}
