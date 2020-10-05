<?php

namespace App\Modules\Seller\Controllers;

use App\Models\AdminUser;
use App\Services\Common\CommonManageService;
use App\Services\Wechat\WechatHelperService;

class BaseController extends InitController
{
    protected $ru_id = 0;
    protected $seller = [];
    protected $privilege_seller = [];
    protected $menu = [];
    protected $menu_select = [];
    protected $seller_info = [];

    protected function initialize()
    {
        parent::initialize();

        load_helper(['base', 'mobile']);

        $config = cache('shop_config');
        if (is_null($config)) {
            $config = app(\App\Services\Common\ConfigService::class)->getConfig();
        }

        config(['shop' => $config]);

        // 加载公共语言包
        L(lang('admin/common'));
        L(lang('admin/wechat'));
        $this->assign('lang', L());

        // 查询商家管理员
        $seller = app(WechatHelperService::class)->get_admin_seller();
        if (!empty($seller) && $seller['ru_id'] > 0) {
            $this->ru_id = $seller['ru_id'];
        }
        $this->seller = $seller; // 用于插件
        $this->assign('admin_info', $seller);
        $this->assign('ru_id', $seller['ru_id']);
        $this->assign('seller_name', $seller['user_name']);

        //判断编辑个人资料权限
        $privilege_seller = 0;
        if (isset($seller['action_list']) && in_array('privilege_seller', explode(',', $seller['action_list']))) {
            $privilege_seller = 1;
        }
        $this->privilege_seller = $privilege_seller;
        $this->assign('privilege_seller', $privilege_seller);

        $menu = cache('seller_menu');
        if (is_null($menu)) {
            // 商家菜单列表
            $menu = set_seller_menu();
            foreach ($menu as $k => $v) {
                $menu[$k]['url'] = '../' . $v['url'];
                foreach ($v['children'] as $j => $val) {
                    $menu[$k]['children'][$j]['url'] = '../' . $val['url'];
                }
            }
            cache()->forever('seller_menu', $menu);
        }

        $this->menu = $menu;
        $this->assign('seller_menu', $menu);
        // 当前选择菜单
        $menu_select = app(WechatHelperService::class)->get_select_menu();
        $this->menu_select = $menu_select;
        $this->assign('menu_select', $menu_select);

        $seller_info = app(CommonManageService::class)->getSellerInfo();
        $this->seller_info = $seller_info;
        $this->assign('seller_info', $seller_info);
    }

    /**
     * 消息提示跳转页
     */
    public function message()
    {
        $url = null;
        $type = '1';
        $seller = true;
        $waitSecond = 2;
        if (func_num_args() === 0) {
            $msg = request()->session()->get('msg', '');
            $type = request()->session()->get('type', 1);
            $url = request()->session()->get('url', null);
        } else {
            $argments = func_get_args();

            $msg = isset($argments['0']) ? $argments['0'] : '';
            $url = isset($argments['1']) ? $argments['1'] : $url;
            $type = isset($argments['2']) ? $argments['2'] : $type;
            $seller = isset($argments['3']) ? $argments['3'] : $seller;
            $waitSecond = isset($argments['4']) ? $argments['4'] : $waitSecond;
        }

        if (is_null($url)) {
            $url = 'javascript:history.back();';
        }
        if ($type == '2') {
            $title = lang('error_information');
        } else {
            $title = lang('prompt_information');
        }

        $data = [
            'title' => $title,
            'message' => $msg,
            'type' => $type,
            'url' => $url,
            'second' => $waitSecond,
        ];
        $this->assign('data', $data);

        $tpl = ($seller == true) ? 'seller/base.seller_message' : 'admin/base.message';
        return $this->display($tpl);
        exit();
    }

    /**
     * 判断管理员对某一个操作是否有权限。
     *
     * 根据当前对应的action_code，然后再和用户session里面的action_list做匹配，以此来决定是否可以继续执行。
     * @param string $priv_str 操作对应的priv_str
     * @return true/false
     */
    public function seller_admin_priv($priv_str)
    {
        $seller_id = request()->session()->get('seller_id', 0);
        $action_list = AdminUser::where('user_id', $seller_id)->value('action_list');

        if ($action_list == 'all') {
            return true;
        }

        if (strpos(',' . $action_list . ',', ',' . $priv_str . ',') === false) {
            return false;//$this->message(lang('admin/common.priv_error'), null, 2, true);
        } else {
            return true;
        }
    }
}
