<?php

namespace App\Modules\Admin\Controllers;

use App\Repositories\Common\DscRepository;
use App\Services\Common\ConfigManageService;

/**
 * 短信设置程序
 */
class SmsSettingController extends InitController
{
    protected $configManageService;
    protected $dscRepository;

    public function __construct(
        ConfigManageService $configManageService,
        DscRepository $dscRepository
    )
    {
        $this->configManageService = $configManageService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {

        /* 检查权限 */
        admin_priv('sms_setting');

        $act = request()->input('act', 'step_up');

        /* ------------------------------------------------------ */
        //-- 短信设置
        /* ------------------------------------------------------ */
        if ($act == 'step_up') {

            $this->dscRepository->helpersLang('shop_config', 'admin');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_sms_setting']);

            $this->smarty->assign('menu_select', ['action' => '24_sms', 'current' => '01_sms_setting']);

            $group_list = $this->configManageService->getUpSettings('sms');

            $this->smarty->assign('group_list', $group_list);


            return $this->smarty->display('sms_step_up.dwt');

        }

    }
}
