<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Crons;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Cron\CronManageService;
use Illuminate\Support\Str;

/**
 * 计划任务
 */
class CronController extends InitController
{
    protected $dscRepository;
    protected $baseRepository;
    protected $timeRepository;
    protected $cronManageService;

    public function __construct(
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        CronManageService $cronManageService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->cronManageService = $cronManageService;
    }

    public function index()
    {
        admin_priv('cron');

        $act = request()->input('act', 'list');
        $act = trim($act);

        /*------------------------------------------------------ */
        //-- 计划任务列表
        /*------------------------------------------------------ */
        if ($act == 'list') {
            $cron_list = [];
            $res = $this->baseRepository->getToArrayGet(Crons::whereRaw('1'));
            foreach ($res as $row) {
                $cron_list[$row['cron_code']] = $row;
            }
            $modules = $this->dscRepository->readModules(plugin_path('Cron'));
            if ($modules) {
                for ($i = 0; $i < count($modules); $i++) {

                    $code = $modules[$i]['code'];

                    /* 如果数据库中有，取数据库中的名称和描述 */
                    if (isset($cron_list[$code])) {
                        $modules[$i]['name'] = $cron_list[$code]['cron_name'];
                        $modules[$i]['desc'] = $cron_list[$code]['cron_desc'];
                        $modules[$i]['cron_order'] = $cron_list[$code]['cron_order'];
                        $modules[$i]['enable'] = $cron_list[$code]['enable'];
                        $modules[$i]['nextime'] = $this->timeRepository->getLocalDate('Y-m-d/H:i:s', $cron_list[$code]['nextime']);
                        $modules[$i]['thistime'] = $cron_list[$code]['thistime'] ? $this->timeRepository->getLocalDate('Y-m-d/H:i:s', $cron_list[$code]['thistime']) : '-';
                        $modules[$i]['install'] = '1';
                    } else {
                        $modules[$i]['name'] = $GLOBALS['_LANG'][$modules[$i]['code']];
                        $modules[$i]['desc'] = $GLOBALS['_LANG'][$modules[$i]['desc']];
                        $modules[$i]['nextime'] = '-';
                        $modules[$i]['thistime'] = '-';
                        $modules[$i]['install'] = '0';
                    }
                }
            }

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['07_cron_schcron']);
            $this->smarty->assign('modules', $modules);
            return $this->smarty->display('cron_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 安装计划任务
        /*------------------------------------------------------ */
        elseif ($act == 'install') {
            if (empty($_POST['step'])) {

                /* 调用相应的支付方式文件 */
                $modules = plugin_path('Cron/' . Str::studly($_REQUEST['code']) . '/config.php');

                if (file_exists($modules)) {
                    $data = include_once($modules);

                    $cron['cron_code'] = $data['code'];
                    $cron['cron_act'] = 'install';
                    $cron['cron_name'] = $GLOBALS['_LANG'][$data['code']];
                    $cron['cron_desc'] = $GLOBALS['_LANG'][$data['desc']];
                    $cron['cron_config'] = [];

                    if (!empty($data['config'])) {
                        foreach ($data['config'] as $key => $value) {
                            $cron['cron_config'][$key] = $value + ['label' => $GLOBALS['_LANG'][$value['name']], 'value' => $value['value']];
                            if ($cron['cron_config'][$key]['type'] == 'select') {
                                $cron['cron_config'][$key]['range'] = $GLOBALS['_LANG'][$cron['cron_config'][$key]['name'] . '_range'];
                            }
                        }
                    }

                    $this->smarty->assign('cron', $cron);
                }

                list($day, $week, $hours) = $this->cronManageService->get_dwh();

                $page_list = ['index' => 0,
                    'user' => 0,
                    'pick_out' => 0,
                    'flow' => 0,
                    'group_buy' => 0,
                    'snatch' => 0,
                    'tag_cloud' => 0,

                    'category' => 0,
                    'goods' => 0,
                    'article_cat' => 0,
                    'article' => 0,
                    'brand' => 0,
                    'search' => 0,
                ];

                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['install'] . $GLOBALS['_LANG']['cron_code']);
                $this->smarty->assign('days', $day);
                $this->smarty->assign('page_list', $page_list);
                $this->smarty->assign('week', $week);
                $this->smarty->assign('hours', $hours);

                return $this->smarty->display('cron_edit.dwt');
            }

            /*------------------------------------------------------ */
            //-- 提交安装
            /*------------------------------------------------------ */
            elseif ($_POST['step'] == 2) {
                $cron_code = trim($_POST['cron_code'] ?? '');
                $cron_name = trim($_POST['cron_name'] ?? '');
                $cron_minute = trim($_POST['cron_minute'] ?? '');
                $cron_desc = trim($_POST['cron_desc'] ?? '');
                $cron_run_once = intval($_POST['cron_run_once'] ?? 0);
                $allow_ip = trim($_POST['allow_ip'] ?? '');
                $ttype = trim($_POST['ttype'] ?? '');
                $cron_day = trim($_POST['cron_day'] ?? '');
                $cron_week = trim($_POST['cron_week'] ?? '');
                $cron_hour = trim($_POST['cron_hour'] ?? '');
                $alow_files = isset($_POST['alow_files']) ? implode(' ', $_POST['alow_files']) : "";

                $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'cron.php?act=list'];
                if (empty($cron_name)) {
                    return sys_msg($GLOBALS['_LANG']['cron_name'] . $GLOBALS['_LANG']['empty']);
                }
                $res = Crons::where('cron_code', $cron_code)->count();
                if ($res > 0) {
                    return sys_msg($GLOBALS['_LANG']['cron_code'] . $GLOBALS['_LANG']['repeat'], 1);
                }

                /* 取得配置信息 */
                $cron_config = [];
                if (isset($_POST['cfg_value']) && is_array($_POST['cfg_value'])) {
                    $temp = count($_POST['cfg_value']);
                    for ($i = 0; $i < $temp; $i++) {
                        $cron_config[] = ['name' => trim($_POST['cfg_name'][$i]),
                            'type' => trim($_POST['cfg_type'][$i]),
                            'value' => trim($_POST['cfg_value'][$i])
                        ];
                    }
                }
                $cron_config = serialize($cron_config);
                $cron_minute = $this->cronManageService->get_minute($cron_minute);
                if ($ttype == 'day') {
                    $cron_day = $cron_day;
                    $cron_week = '';
                } elseif ($ttype == 'week') {
                    $cron_day = '';
                    $cron_week = $cron_week;
                } else {
                    $cron_day = $cron_week = '';
                }
                if ($cron_week == 7) {
                    $cron_week = 0;
                }
                $cron = ['day' => $cron_day, 'week' => $cron_week, 'm' => $cron_minute, 'hour' => $cron_hour];
                $next = $this->cronManageService->get_next_time($cron);
                $other = [
                    'cron_code' => $cron_code,
                    'cron_name' => $cron_name,
                    'cron_desc' => $cron_desc,
                    'cron_config' => $cron_config,
                    'nextime' => $next,
                    'day' => $cron_day,
                    'week' => $cron_week,
                    'hour' => $cron_hour,
                    'minute' => $cron_minute,
                    'run_once' => $cron_run_once,
                    'allow_ip' => $allow_ip,
                    'alow_files' => $alow_files
                ];
                Crons::insert($other);
                return sys_msg($GLOBALS['_LANG']['install_ok'], 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑计划任务
        /*------------------------------------------------------ */
        elseif ($act == 'edit') {
            if (empty($_POST['step'])) {
                if (isset($_REQUEST['code'])) {
                    $_REQUEST['code'] = trim($_REQUEST['code']);
                } else {
                    return 'invalid cron';
                }
                $cron = Crons::where('cron_code', $_REQUEST['code'])->first();
                $cron = $cron ? $cron->toArray() : [];
                if (empty($cron)) {
                    $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'cron.php?act=list'];
                    return sys_msg($GLOBALS['_LANG']['cron_not_available'], 0, $links);
                }

                $this->dscRepository->helpersLang($_REQUEST['code'], 'Cron/' . Str::studly($_REQUEST['code']) . '/Languages/' . $GLOBALS['_CFG']['lang'], 1);

                /* 取得配置信息 */
                $cron['cron_config'] = unserialize($cron['cron_config']);
                if (!empty($cron['cron_config'])) {
                    foreach ($cron['cron_config'] as $key => $value) {
                        $cron['cron_config'][$key]['label'] = $GLOBALS['_LANG'][$value['name']];
                        if ($cron['cron_config'][$key]['type'] == 'select') {
                            $cron['cron_config'][$key]['range'] = $GLOBALS['_LANG'][$cron['cron_config'][$key]['name'] . '_range'];
                        }
                    }
                }
                $cron['cron_act'] = 'edit';
                $cron['cronweek'] = $cron['week'] == '0' ? 7 : $cron['week'];
                $cron['cronday'] = $cron['day'];
                $cron['cronhour'] = $cron['hour'];
                $cron['cronminute'] = $cron['minute'];
                $cron['run_once'] && $cron['autoclose'] = 'checked';
                list($day, $week, $hours) = $this->cronManageService->get_dwh();
                $page_list = ['index' => 0,
                    'user' => 0,
                    'pick_out' => 0,
                    'flow' => 0,
                    'group_buy' => 0,
                    'snatch' => 0,
                    'tag_cloud' => 0,
                    'category' => 0,
                    'goods' => 0,
                    'article_cat' => 0,
                    'article' => 0,
                    'brand' => 0,
                    'search' => 0,
                ];
                $cron['alow_files'] .= " ";
                foreach (explode(' ', $cron['alow_files']) as $k => $v) {
                    $v = str_replace('.php', '', $v);
                    if (!empty($v)) {
                        $page_list[$v] = 1;
                    }
                }

                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit'] . $GLOBALS['_LANG']['cron_code']);
                $this->smarty->assign('cron', $cron);
                $this->smarty->assign('days', $day);
                $this->smarty->assign('week', $week);
                $this->smarty->assign('hours', $hours);
                $this->smarty->assign('page_list', $page_list);
                return $this->smarty->display('cron_edit.dwt');
            } elseif ($_POST['step'] == 2) {
                $cron_code = trim($_POST['cron_code'] ?? '');
                $cron_name = trim($_POST['cron_name'] ?? '');
                $cron_minute = trim($_POST['cron_minute'] ?? '');
                $cron_desc = trim($_POST['cron_desc'] ?? '');
                $cron_run_once = intval($_POST['cron_run_once'] ?? 0);
                $allow_ip = trim($_POST['allow_ip'] ?? '');
                $ttype = trim($_POST['ttype'] ?? '');
                $cron_day = trim($_POST['cron_day'] ?? '');
                $cron_week = trim($_POST['cron_week'] ?? '');
                $cron_hour = trim($_POST['cron_hour'] ?? '');
                $alow_files = isset($_POST['alow_files']) ? implode(' ', $_POST['alow_files']) : "";
                $cron_id = intval($_POST['cron_id'] ?? 0);

                $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'cron.php?act=list'];
                if (empty($cron_id)) {
                    return sys_msg($GLOBALS['_LANG']['cron_not_available'], 0, $links);
                }
                $cron_config = [];
                if (isset($_POST['cfg_value']) && is_array($_POST['cfg_value'])) {
                    $temp = count($_POST['cfg_value']);
                    for ($i = 0; $i < $temp; $i++) {
                        $cron_config[] = ['name' => trim($_POST['cfg_name'][$i]),
                            'type' => trim($_POST['cfg_type'][$i]),
                            'value' => trim($_POST['cfg_value'][$i])
                        ];
                    }
                }
                $cron_config = serialize($cron_config);
                $cron_minute = $this->cronManageService->get_minute($cron_minute);
                if ($ttype == 'day') {
                    $cron_day = $cron_day;
                    $cron_week = '';
                } elseif ($ttype == 'week') {
                    $cron_day = '';
                    $cron_week = $cron_week;
                } else {
                    $cron_day = $cron_week = '';
                }
                if ($cron_week == 7) {
                    $cron_week = 0;
                }

                $cron = ['day' => $cron_day, 'week' => $cron_week, 'm' => $cron_minute, 'hour' => $cron_hour];
                $next = $this->cronManageService->get_next_time($cron);
                $other = [
                    'cron_code' => $cron_code,
                    'cron_name' => $cron_name,
                    'cron_desc' => $cron_desc,
                    'cron_config' => $cron_config,
                    'nextime' => $next,
                    'day' => $cron_day,
                    'week' => $cron_week,
                    'hour' => $cron_hour,
                    'minute' => $cron_minute,
                    'run_once' => $cron_run_once,
                    'allow_ip' => $allow_ip,
                    'alow_files' => $alow_files
                ];
                Crons::where('cron_id', $cron_id)->update($other);
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 卸载计划任务
        /*------------------------------------------------------ */
        elseif ($act == 'uninstall') {
            Crons::where('cron_code', $_REQUEST['code'])->delete();
            $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'cron.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['uninstall_ok'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 开启计划任务
        /*------------------------------------------------------ */
        elseif ($act == 'toggle_show') {
            $id = trim($_POST['id']);
            $val = intval($_POST['val']);
            Crons::where('cron_code', $id)->update(['enable' => $val]);
            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 执行计划任务
        /*------------------------------------------------------ */
        elseif ($act == 'do') {
            $code = trim($_REQUEST['code'] ?? '');
            if (isset($set_modules)) {
                $set_modules = false;
                unset($set_modules);
            }

            $this->dscRepository->helpersLang($code, 'Cron/' . Str::studly($code) . '/Languages/' . $GLOBALS['_CFG']['lang'], 1);

            /* 调用相应的支付方式文件 */
            $modules = plugin_path('Cron/' . Str::studly($_REQUEST['code']) . '/' . Str::studly($_REQUEST['code']) . '.php');

            if (file_exists($modules)) {
                include_once($modules);

                $cron = [];
                $temp = Crons::where('cron_code', $code)->value('cron_config');
                $temp = $temp ? $temp : '';
                $temp = unserialize($temp);
                if (!empty($temp)) {
                    foreach ($temp as $key => $val) {
                        $cron[$val['name']] = $val['value'];
                    }
                }
                $timestamp = $this->timeRepository->getGmTime();
                Crons::where('cron_code', $code)->update(['thistime' => $timestamp]);
            }

            $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'cron.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['do_ok'], 0, $links);
        }
    }
}
