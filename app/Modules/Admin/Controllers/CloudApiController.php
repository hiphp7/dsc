<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Form;
use App\Models\ShopConfig;

/**
 *  贡云
 */
class CloudApiController extends BaseController
{
    protected $page_num;

    protected function initialize()
    {
        parent::initialize();

        L(require(resource_path('lang/' . config('shop.lang') . '/admin/cloud_api.php')));
        $this->assign('lang', array_change_key_case(L()));

        // 初始化 每页分页数量
        $this->init_params();
    }

    /**
     * 处理公共参数
     */
    private function init_params()
    {
        $page_num = request()->cookie('page_size');
        $this->page_num = is_null($page_num) ? 10 : $page_num;
        $this->assign('page_num', $this->page_num);
    }


    public function index()
    {
        // 检查权限
        $this->admin_priv('shop_config');

        $this->assign('ur_here', L('cloud_api'));
        $this->assign('form_act', 'cloud_update');

        $api_config = [
            'client_id' => ShopConfig::where('code', 'cloud_client_id')->value('value'),
            'appkey' => ShopConfig::where('code', 'cloud_appkey')->value('value'),
            'cloud_dsc_appkey' => ShopConfig::where('code', 'cloud_dsc_appkey')->value('value'),
            'cloud_is_open' => ShopConfig::where('code', 'cloud_is_open')->value('value')
        ];

        $this->assign('api_config', $api_config);
        return $this->display();
    }

    public function update()
    {
        // 检查权限
        $this->admin_priv('shop_config');

        $data = request()->input('data');

        // 验证数据
        $form = new Form();
        if (!$form->isEmpty($data['cloud_client_id'], 1)) {
            return $this->message(lang('admin/cloud_api.user_id_not_null'), null, 2);
        }
        if (!$form->isEmpty($data['cloud_appkey'], 1)) {
            return $this->message(lang('admin/cloud_api.api_key_not_null'), null, 2);
        }
        if (!$form->isEmpty($data['cloud_dsc_appkey'], 1)) {
            return $this->message(lang('admin/cloud_api.appkey_not_null'), null, 2);
        }

        foreach ($data as $key => $val) {
            $arr = ['value' => $val];
            ShopConfig::UpdateOrCreate(['code' => $key], $arr);
        }

        return redirect()->route('cloudapi.index');
    }
}
