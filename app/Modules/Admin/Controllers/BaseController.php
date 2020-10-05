<?php

namespace App\Modules\Admin\Controllers;

class BaseController extends InitController
{
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
    }

    /**
     * 消息提示跳转页
     */
    public function message()
    {
        $url = null;
        $type = '1';
        $seller = false;
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
     * @param string $msg_type 返回的类型
     * @return true/false
     */
    public function admin_priv($priv_str)
    {
//        $condition['user_id'] = session()->has('admin_id') ? intval(session('admin_id')) : 0;
//        $action_list = AdminUser::where($condition)->value('action_list');
//
//        if ($action_list == 'all') {
//            return true;
//        }
//
//        if (strpos(',' . $action_list . ',', ',' . $priv_str . ',') === false) {
//            return false;//$this->message(lang('admin/common.priv_error'), null, 2);
//        } else {
//            return true;
//        }
    }
}
